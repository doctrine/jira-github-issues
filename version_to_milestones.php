<?php
/**
 * Doctrine Jira to Github Migration
 *
 * Step 1: Create a milestone for every Jira version in the Github Issue Tracker.
 *
 * This is necessary so that we can attach all issues that we are going to
 * import into Github Issues to their respective Jira version.
 *
 * @example
 *  $ php version_to_milestones.php <ProjectKey>
 */

require_once 'vendor/autoload.php';

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

$githubRepository = $projects[$project];
$githubHeaders = ['User-Agent: Doctrine Jira Migration', 'Authorization: token ' . $_SERVER['GITHUB_TOKEN']];

$client = new \Buzz\Browser();

$response = $client->get($_SERVER['JIRA_URL'] . "/rest/api/2/project/$project/versions");

if ($response->getStatusCode() !== 200) {
    printf("Could not fetch versions of project '$project'\n");
    printf($response->getStatusCode());
    exit(2);
}

$versions = json_decode($response->getContent(), true);

$skipVersions = ['Git Master'];

$milestones = [];
foreach ($versions as $version) {
    $version['name'] = preg_replace('(^v)', '', $version['name']);

    if (in_array($version['name'], $skipVersions)) {
        continue;
    }

    $milestone = [
        'title' => $version['name'],
        'state' => $version['released'] ? 'closed' : 'open',
    ];

    if (isset($version['releaseDate']) && $version['releaseDate']) {
        $milestone['due_on'] = sprintf('%sT23:59:59Z', $version['releaseDate']);
    }

    $milestones[] = $milestone;
}

usort($milestones, function ($a, $b) {
    return version_compare($a['title'], $b['title']) * -1;
});

$response = $client->get('https://api.github.com/repos/doctrine/' . $githubRepository . '/milestones?state=all&per_page=100', $githubHeaders);
if ($response->getStatusCode() !== 200) {
    printf("Could not fetch existing Github Milestones\n");
    var_dump($response->getContent());
    exit(3);
}

$existingMilestones = [];
foreach(json_decode($response->getContent(), true) as $existingMilestone) {
    $existingMilestones[$existingMilestone['title']] = true;
}

$createdMilestones = [];
foreach ($milestones as $milestone) {
    if (isset($existingMilestones[$milestone['title']])) {
        continue;
    }

    $response = $client->post('https://api.github.com/repos/doctrine/' . $githubRepository . '/milestones', $githubHeaders, json_encode($milestone));

    if ($response->getStatusCode() < 400) {
        $data = json_decode($response->getContent(), true);
        $createdMilestones[$milestone['title']] = $data['id'];
    } else {
        printf('Error creating milestone "%s": %s', $milestone['title'], $response->getContent()) . "\n";
    }
}
printf('Created %d milestones from Jira to Github.', count($createdMilestones));
var_dump($createdMilestones);
