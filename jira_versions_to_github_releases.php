<?php
/**
 * Jira to Github Issue Migration
 *
 * Besides Milestones for the Issue Tracker, we also want to import every
 * completed version into a proper Github release. For this we need to map
 * the version names to Git tags.
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
$jiraHeaders = ['Authorization: Basic ' . base64_encode(sprintf('%s:%s', $_SERVER['JIRA_USER'], $_SERVER['JIRA_PASSWORD']))];
$client = new \Buzz\Browser();

$response = $client->get("http://www.doctrine-project.org/jira/rest/api/2/project/$project/versions", $jiraHeaders);

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
    var_dump($version);
}
