<?php

require_once 'vendor/autoload.php';
require_once 'jira_markdown.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$githubHeaders = ['User-Agent: Doctrine Jira Migration', 'Authorization: token ' . $_SERVER['GITHUB_TOKEN']];

$client = new \Buzz\Browser();

$response = $client->get('https://api.github.com/rate_limit', $githubHeaders);

var_dump(json_decode($response->getContent(), true));
