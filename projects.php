<?php

require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

return json_decode(getenv('PROJECTS'), true);
