<?php
/**
 * Doctrine Jira to Github Migration
 *
 * Import non-binary attachments into a Gist and comment on the relevant issue
 * with a link.
 *
 * @example
 *
 * $ php attachments.php DDC
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

$issueMap = json_decode(file_get_contents('data/' . $project . '.issues.json'), true);

$issues = scandir('data/attachments/' . $project);

$count = 0;
foreach ($issues as $issue) {
    if (!isset($issueMap[$issue])) continue;
    $count++;

    if (isset($argv[2]) && $count <= $argv[2]) continue;

    $attachments = scandir('data/attachments/' . $project . '/' . $issue);

    $gist = [
        'description' => 'Attachments to Doctrine Jira Issue ' . $issue . ' - https://github.com/doctrine/' . $githubRepository . '/issues/' . $issueMap[$issue],
        'public' => false,
        'files' => [],
    ];

    foreach ($attachments as $attachment) {
        if (in_array($attachment, ['.', '..'])) continue;

        $ext = pathinfo($attachment, PATHINFO_EXTENSION);

        if (!in_array($ext, ['php', 'patch', 'diff', 'xml', 'yml', 'txt', 'sql', 'log', 'xsd'])) {
            continue;
        }


        $gist['files'][$attachment]['content'] = file_get_contents('data/attachments/' . $project . '/' . $issue . '/' . $attachment);
    }

    if (count($gist['files']) === 0) {
        continue;
    }

    $response = $client->post(
        'https://api.github.com/gists',
        $githubHeaders,
        json_encode($gist)
    );

    if ($response->getStatusCode() >= 400) {
        throw new \Exception($issue . " attachment import failed: " . $response->getContent());
    }

    $gistData = json_decode($response->getContent(), true);
    $url = $gistData['html_url'];

    $comment = "Imported " . count($gist["files"]) . " attachments from Jira into " . $url . "\n\n";
    foreach ($gist['files'] as $file => $_) {
        $anchor = "#file-" . str_replace(".", "-", $file);
        $comment .= "- [" . $file . "](" . $url . $anchor . ")\n";
    }

    $response = $client->post(
        'https://api.github.com/repos/doctrine/' . $githubRepository . '/issues/' . $issueMap[$issue] . '/comments',
        $githubHeaders,
        json_encode(['body' => $comment])
    );

    printf("%04d - Imported %s into %s\n", $count, $issue, $url);
}
