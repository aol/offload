<?php

// Because code coverage is messy
ini_set('memory_limit','1024M');

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->setPsr4("Aol\\Offload\\Tests\\", __DIR__ . '/src');

