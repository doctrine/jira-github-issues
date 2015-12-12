<?php
/**
 * Doctrine Jira to Github Migration
 *
 * @example
 *
 * $ php import_tickets.php DDC
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

$files = scandir('data/' . $project);

if (isset($argv[2])) {
    $files = [$argv[2] . ".json"];
}

$count = 0;
foreach ($files as $file) {
    if ($file === "." || $file === "..") continue;

    $issueKey = str_replace('.json', '', $file);
    $issue = json_decode(file_get_contents('data/' . $project . '/' . $file), true);

    printf("Preparing %s... ", $issueKey);

    if (isset($ticketStatus[$issueKey])) {
        if ($ticketStatus[$issueKey]['status'] === 'pending') {
            printf("pending, skipped\n");
            continue;
            $response = $client->get($ticketStatus[$issueKey]['url'], $githubHeaders);

            if ($response->getStatusCode() == 200) {
                $ticketStatus[$issueKey] = json_decode($response->getContent(), true);
                file_put_contents("data/" . $project . ".status.json", json_encode($ticketStatus, JSON_PRETTY_PRINT));
                printf("updated status... ");
            }
        }

        if ($ticketStatus[$issueKey]['status'] === 'pending') {
            printf("pending, skipped\n");
            continue;
        }

        if ($ticketStatus[$issueKey]['status'] === 'imported') {
            printf("imported, skipped\n");
            continue;
        }

        if ($ticketStatus[$issueKey]['status'] === 'failed') {
            printf("Error importing, retry... ", $issueKey);
        }
    }
    //printf("debug skip\n"); continue;

    $response = $client->post('https://api.github.com/repos/doctrine/' . $githubRepository . '/import/issues', $githubHeaders, json_encode($issue));

    if ($response->getStatusCode() >= 400) {
        printf("Error: " . $response->getContent());
        exit;
    }

    $ticketStatus[$issueKey] = json_decode($response->getContent(), true);
    file_put_contents("data/" . $project . ".status.json", json_encode($ticketStatus, JSON_PRETTY_PRINT));
    printf("imported %s\n", $issueKey);

    $count++;

    if (($count % 10) === 0) {
        //exit;
    }
}
