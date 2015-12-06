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
    'User-Agent: Doctrine Jira Migration',
    'Authorization: token ' . $_SERVER['GITHUB_TOKEN'],
    'Accept: application/vnd.github.golden-comet-preview+json'
];
$jiraHeaders = ['Authorization: Basic ' . base64_encode(sprintf('%s:%s', $_SERVER['JIRA_USER'], $_SERVER['JIRA_PASSWORD']))];

$ticketStatus = [];
if (file_exists('data/' . $project . '.status.json')) {
    $ticketStatus = json_decode(file_get_contents('data/' . $project . '.status.json'), true);
}

if (isset($ticketStatus[$argv[2]])) {
    $response = $client->get($ticketStatus[$argv[2]]['url'], $githubHeaders);

    if ($response->getStatusCode() >= 400) {
        var_dump($response->getContent());
        exit(2);
    }

    $issue = json_decode(file_get_contents('data/' . $project . '/' . $argv[2] . '.json'), true);
    var_dump($issue);
    var_dump(json_decode($response->getcontent(), true));
}
