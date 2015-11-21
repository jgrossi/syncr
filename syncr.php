#! /usr/bin/env php
<?php

use Symfony\Component\Console\Application;
use Syncr\SyncCommand;

require __DIR__.'/vendor/autoload.php';

$app = new Application('Syncr', '1.0');
$app->add(new SyncCommand());
$app->run();
