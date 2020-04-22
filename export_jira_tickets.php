<?php
/**
 * Github to Jira Migration
 *
 * Step 2: Export all tickets from Jira into JSON file(s) on disk.
 *
 * We don't want to require both Jira and Github uptime, so we use an intermediate
 * format for all issues, where we export Jira issues into the format that the Github
 * bulk import API needs. This script is written in a way so that it can be "continued"
 * after abort.
 *
 * @example
 *  $ php export_jira_tickets <Project> <StartAt>
 */

require_once 'vendor/autoload.php';
require_once 'jira_markdown.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

if (!isset($argv[1])) {
    printf("Missing argument: Project Key\n");
    exit(1);
}

$project = $argv[1];
$projects = require 'projects.php';

if (!isset($projects[$project])) {
    printf("Unknown project: $project\n");
    exit(2);
}

$startAt = isset($argv[2]) ? (int)($argv[2]) : 0;
$githubRepository = $projects[$project];
$githubHeaders = ['User-Agent: ' . getenv('GITHUB_ORG') . ' Jira Migration', 'Authorization: token ' . getenv('GITHUB_TOKEN')];
$jiraHeaders = ['Authorization: Basic ' . base64_encode(sprintf('%s:%s', getenv('JIRA_USER'), getenv('JIRA_TOKEN')))];

$client = new \Buzz\Browser();

$response = $client->get('https://api.github.com/repos/' . getenv('GITHUB_ORG') . '/' . $githubRepository . '/milestones?state=all&per_page=100', $githubHeaders);
if ($response->getStatusCode() !== 200) {
    printf("Could not fetch existing Github Milestones\n");
    var_dump($response->getContent());
    exit(3);
}

$existingMilestones = [];
foreach(json_decode($response->getContent(), true) as $existingMilestone) {
    $existingMilestones[$existingMilestone['title']] = $existingMilestone['number'];
}

$count = 0;

@mkdir("data/" . $project, 0777);

$knownIssueTypes = explode(',', getenv('ISSUE_TYPES'));
$knownAssigneesMap = json_decode(getenv('ASSIGNEES'), true);

while (true) {
    $response = $client->get(getenv('JIRA_URL') . "/rest/api/2/search?jql=" . urlencode("project = $project ORDER BY created ASC") . "&fields=" . urlencode("*all") . "&startAt=" . $startAt, $jiraHeaders);

    if ($response->getStatusCode() !== 200) {
        printf("Could not fetch versions of project '$project'\n");
        printf($response->getStatusCode());
        exit(2);
    }

    $issues = json_decode($response->getContent(), true);

    if (count($issues['issues']) === 0) {
        printf("Exported %d issues from Jira into data/%s/ folder.\n", $count, $project);
        return;
    }
    $count += count($issues['issues']);

    foreach ($issues['issues'] as $issue) {
        $import = [
            'issue' => [
                'title' => sprintf('%s: %s', $issue['key'], $issue['fields']['summary']),
                'body' => sprintf(
                    "Jira issue originally created by user %s:\n\n%s",
                    mentionName($issue['fields']['creator']['accountId']),
                    toMarkdown($issue['fields']['description'])
                ),
                'created_at' => substr($issue['fields']['created'], 0, 19) . 'Z',
                'closed' => in_array($issue['fields']['status']['name'], explode(',', getenv('CLOSED_STATES'))),
            ],
        ];

        if (isset($issue['fields']['issuetype']['name']) && in_array($issue['fields']['issuetype']['name'], $knownIssueTypes)) {
            $import['issue']['labels'] = [$issue['fields']['issuetype']['name']];
        }

        if (isset($issue['fields']['fixVersions']) && count($issue['fields']['fixVersions']) > 0) {
            $milestoneVersion = array_reduce($issue['fields']['fixVersions'], function ($last, $version) {
                $versionName = preg_replace('(^v)', '', $version['name']);
                if (version_compare($last, $versionName) > 0) {
                    return $versionName;
                }
                return $last;
            }, '10.0.0');

            if (isset($existingMilestones[$milestoneVersion])) {
                $import['issue']['milestone'] = $existingMilestones[$milestoneVersion];
            }
        }

        if (isset($issue['fields']['assignee']) && $issue['fields']['assignee'] && in_array($issue['fields']['assignee']['accountId'], $knownAssigneesMap)) {
            $import['issue']['assignee'] = $knownAssigneesMap[$issue['fields']['assignee']['accountId']];
        }

        $import['comments'] = [];

        if (isset($issue['fields']['issuelinks']) && $issue['fields']['issuelinks']) {
            $comment = "";
            foreach ($issue['fields']['issuelinks'] as $link) {
                /*if (isset($link['inwardIssue'])) {
                    $comment .= sprintf("* %s [%s: %s](http://www.doctrine-project.org/jira/browse/%s)\n", $link['type']['inward'], $link['inwardIssue']['key'], $link['inwardIssue']['fields']['summary'], $link['inwardIssue']['key']);
                } else if (isset($link['outwardIssue'])) {
                    $comment .= sprintf("* %s [%s: %s](http://www.doctrine-project.org/jira/browse/%s)\n", $link['type']['outward'], $link['outwardIssue']['key'], $link['outwardIssue']['fields']['summary'], $link['outwardIssue']['key']);
                }*/
            }
            $import['comments'][] = [
                'body' => $comment,
                'created_at' => substr($issue['fields']['created'], 0, 19) . 'Z',
            ];
        }

        if (isset($issue['fields']['comment']) && count($issue['fields']['comment']['comments']) > 0) {
            foreach ($issue['fields']['comment']['comments'] as $comment) {
                $import['comments'][] = [
                    'created_at' => substr($comment['created'], 0, 19) . 'Z',
                    'body' => sprintf(
                        "Comment created by %s:\n\n%s",
                        mentionName($comment['author']['accountId']),
                        toMarkdown($comment['body'])
                    ),
                ];
            }
        }

        if (isset($issue['fields']['resolutiondate']) && $issue['fields']['resolutiondate']) {
            $import['comments'][] = [
                'created_at' => substr($issue['fields']['resolutiondate'], 0, 19) . 'Z',
                'body' => sprintf('Issue was closed with resolution "%s"', $issue['fields']['resolution']['name']),
            ];
        }

        if (count($import['comments']) === 0) {
            unset($import['comments']);
        }

        file_put_contents("data/" . $project . "/" . $issue['key'] . ".json", json_encode($import, JSON_PRETTY_PRINT));
        printf("Processed issue: %s (Idx: %d)\n", $issue['key'], $startAt);
        $startAt++;
    }

    printf("Completed batch, continuing with start at %d\n", $startAt);
}

function mentionName($name) {
    global $knownAssigneesMap;

    if (isset($knownAssigneesMap[$name])) {
        return '@' . $knownAssigneesMap[$name];
    }
    return $name;
}
