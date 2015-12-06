<?php
require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$client = new \Buzz\Browser();

$githubHeaders = ['User-Agent: Doctrine Jira Migration', 'Authorization: token ' . $_SERVER['GITHUB_TOKEN']];
$githubRepository = "dbal";

$response = $client->get('https://api.github.com/repos/doctrine/' . $githubRepository . '/milestones?state=all&per_page=100', $githubHeaders);
if ($response->getStatusCode() !== 200) {
    printf("Could not fetch existing Github Milestones\n");
    var_dump($response->getContent());
    exit(3);
}

foreach(json_decode($response->getContent(), true) as $existingMilestone) {
    if ($existingMilestone['title'] == '2.5') continue;

    $response = $client->delete('https://api.github.com/repos/doctrine/' . $githubRepository . '/milestones/' . $existingMilestone['number'], $githubHeaders);
    echo($response->getContent()) . "\n";
}
