#!/usr/bin/env php
<?php 

/*
   +----------------------------------------------------------------------+
   | Syncr - Easy files and MySQL databases synchronization with PHP      |
   +----------------------------------------------------------------------+
   | Copyright (c) 2015 Junior Grossi                                     |
   | Authors: Junior Grossi <juniorgro@gmail.com>                         |
   +----------------------------------------------------------------------+
 */

namespace syncr;

/**
 * Check for command line options
 */
$options = getopt('', array('up', 'down', 'database', 'help'));

/**
 * Check if --help option exists in the command
 */
if (isset($options['help'])) {
    echo <<<MESSAGE

Synopsis:
    php syncr [options]
Options:
    --up        Synchronize local files with remote server
    --down      Synchronize remote files with local server
    --database  Synchronize also the MySQL database


MESSAGE;
    exit();
}

/**
 * Check for the synchronization direction (up or down)
 */
if (!isset($options['up']) and !isset($options['down'])) {
    echo <<<MESSAGE
    
+-----------------------------------------------------------+
|                       ! ERROR !                           |
| You have to specify --up or --down options to start the   |
| synchronization process.                                  |
+-----------------------------------------------------------+

MESSAGE;
    exit();
} else {
    if (isset($options['up'])) {
        $direction = 'up';
    } elseif (isset($options['down'])) {
        $direction = 'down';
    }
}

if (isset($options['database'])) {
    $sync_database = true;
} else $sync_database = false;

/**
 * Check if the config file exists
 */
$file = 'syncr.json';
if (file_exists(__DIR__.'/'.$file)) {
    $json = file_get_contents(__DIR__.'/'.$file);
    $config = json_decode($json, true);
} else {
    echo <<<MESSAGE

+-----------------------------------------------------------+
|                       ! ERROR !                           |
| Missing the configuration file.                           |
| Ensure you have a syncr.json file with the appropriate    |
| configurations variables.                                 |
+-----------------------------------------------------------+

MESSAGE;
    exit();
}

/**
 * Check if the config file is valid
 */
if (!config_is_valid($config)) {
    echo <<<MESSAGE
    
+-----------------------------------------------------------+
|                       ! ERROR !                           |
| The configuration file does not have the required         |
| variables.                                                |
+-----------------------------------------------------------+

MESSAGE;
    exit();
}

/** 
 * Check if all commands exist
 */
if (!commands_check()) {
    echo <<<MESSAGE
    
+-----------------------------------------------------------+
|                       ! ERROR !                           |
| Ensure you have all the following commands installed in   |
| your machine: rsync, ssh, sshpass, mysqldump, gzip and    |
| scp.                                                      |
+-----------------------------------------------------------+

MESSAGE;
    exit();
}

/**
 * Continue with normal process
 */
sync_files($config, $direction);
if ($sync_database) {
    sync_database($config, $direction);
}
echo "---> Synchronization finished.\n";



function sync_database($config, $direction = 'up')
{
    if ($direction == 'up') {
        sync_database_up($config);
    } elseif ($direction == 'down') {
        sync_database_down($config);
    }
}






