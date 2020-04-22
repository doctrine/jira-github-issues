<?php

$issueMap = [];


$projectIds = array_keys(json_decode(getenv('PROJECTS'), true));

foreach ($projectIds as $project) {
    $issueMap = array_merge($issueMap, json_decode(file_get_contents('data/' . $project . '.issues.json'), true));
}

file_put_contents('data/issues.json', json_encode([getenv('GITHUB_ORG') . '_issue_map' => $issueMap], JSON_PRETTY_PRINT));
