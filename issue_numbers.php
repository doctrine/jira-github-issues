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

$page = 1;
$numbers = [];
while (true) {
    $response = $client->get('https://api.github.com/repos/' . getenv('GITHUB_ORG') . '/' . $githubRepository . '/issues?state=all&page=' . $page, $githubHeaders);

    if ($response->getStatusCode() != 200) {
        exit;
    }

    $issues = json_decode($response->getContent(), true);

    if (count($issues) === 0) {
        file_put_contents("data/". $project . ".issues.json", json_encode($numbers, JSON_PRETTY_PRINT));
        exit;
    }

    foreach ($issues as $issue) {
        if ($issue['user']['login'] != getenv('GITHUB_USERNAME')) {
            continue;
        }

        if (strrpos($issue['title'], ':') === false) {
            continue;
        }

        list ($key, $title) = explode(':', $issue['title'], 2);
        $numbers[$key] = $issue['number'];
    }

    $page++;
}
file_put_contents("data/". $project . ".issues.json", json_encode($numbers, JSON_PRETTY_PRINT));
