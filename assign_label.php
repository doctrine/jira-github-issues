<?php


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
$client = new \Buzz\Browser();

if (!isset($projects[$project])) {
    printf("Unknown project: $project\n");
    exit(2);
}

$githubRepository = $projects[$project];
$githubHeaders = [
    'User-Agent: ' . getenv('GITHUB_ORG') . ' Jira Migration',
    'Authorization: token ' . getenv('GITHUB_TOKEN'),
    'Accept: application/vnd.github.golden-comet-preview+json'
];

if (!file_exists('data/' . $project . '.issues.json')) {
    printf("Requires issue numbers file.\n");
    exit(3);
}
$issueNumbers = json_decode(file_get_contents('data/' . $project . '.issues.json'), true);

$files = scandir('data/' . $project);

if (isset($argv[2])) {
    $files = [$argv[2] . ".json"];
}

$count = 0;
foreach ($files as $file) {
    if ($file === "." || $file === "..") continue;

    $issueKey = str_replace('.json', '', $file);
    $issue = json_decode(file_get_contents('data/' . $project . '/' . $file), true);
    // POST /repos/:owner/:repo/issues/:number/labels
    if (isset($issueNumbers[$issueKey]) && isset($issue['issue']['labels'])) {
        $githubId = $issueNumbers[$issueKey];

        $client->post(
            'https://api.github.com/repos/' . getenv('GITHUB_ORG') . '/' . $githubRepository . '/issues/' . $githubId . '/labels',
            $githubHeaders,
            json_encode($issue['issue']['labels'])
        );
        printf("https://github.com/%s/%s/issues/%d\n", getenv('GITHUB_ORG'), $githubRepository, $githubId);
    }
}
