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
echo "---> Synchonization finished.\n";


/**
 * Synchronization functions
 */

function sync_files($config, $direction = ' up')
{
    $remote = $config['remote']['server'];
    $local = $config['local']['server'];
    $password = $remote['password'];
    $ssh_port = (int) $remote['ssh_port'];
    $from = trim($local['path'], '/').'/';
    $username = $remote['username'];
    $host = $remote['host'];
    $remote_path = trim($remote['path'], '/').'/';
    $remote_string = $username.'@'.$host.':'.$remote_path;

    if ($direction == 'up') {
        echo "---> Sending files to remote server...\n";
        if ($password) {
            $command = 'sshpass -p "%s" rsync -zavP -e "ssh -p %d" "%s" "%s"';
            $command = sprintf($command, $password, $ssh_port, $from, $remote_string);
        } else {
            $command = 'rsync -zavP -e "ssh -p %d" "%s" "%s"';
            $command = sprintf($command, $ssh_port, $from, $remote_string);
        }
    } elseif ($direction == 'down') {
        echo "---> Getting files from remote server...\n";
        if ($password) {
            $command = 'sshpass -p "%s" rsync -zavP -e "ssh -p %d" "%s" "%s"';
            $command = sprintf($command, $password, $ssh_port, $remote_string, $from);
        } else {
            $command = 'rsync -zavP -e "ssh -p %d" "%s" "%s"';
            $command = sprintf($command, $ssh_port, $remote_string, $from);
        }
    }

    shell_exec($command);
}

function sync_database($config, $direction = 'up')
{
    if ($direction == 'up') {
        sync_database_up($config);
    } elseif ($direction == 'down') {
        sync_database_down($config);
    }
}

function sync_database_up($config)
{
    $username = $config['local']['database']['username'];
    $password = $config['local']['database']['password'];
    $name = $config['local']['database']['name'];
    $sshpass = '';
    $filename = 'mysqldump_'.uniqid();
    $command = 'mysqldump -u%s -p%s %s > %s.sql';
    $command = sprintf($command, $username, $password, $name, $filename);

    echo "---> Backing up local MySQL database...\n";
    echo shell_exec($command);
    
    $command = sprintf('gzip %s.sql', $filename);
    echo shell_exec($command);

    $username = $config['remote']['server']['username'];
    $password = $config['remote']['server']['password'];
    $host = $config['remote']['server']['host'];
    $ssh_port = $config['remote']['server']['ssh_port'];
    $command = 'scp -P $ssh_port ./%s.sql.gz %s@%s:%s.sql.gz';
    $command = sprintf($command, $filename, $username, $host, $filename);
    echo "---> Copying local SQL file to remote server...\n";
    echo shell_exec($command);

    echo shell_exec("rm $filename.sql.gz");

    $db_username = $config['remote']['database']['username'];
    $db_password = $config['remote']['database']['password'];
    $db_name = $config['remote']['database']['name'];
    
    if ($password) {
        $sshpass = sprintf('sshpass -p "%s" ', $password);
    }

    echo "---> Importing database into remove server...\n";
    $command = <<<COMMAND
{$sshpass}ssh -q -t -p $ssh_port $username@$host << ENDSSH
gzip -d $filename.sql.gz
mysql -u$db_username -p$db_password $db_name < $filename.sql
rm $filename.sql
ENDSSH
COMMAND;
    exec($command);
}

function sync_database_down($config)
{
    $username = $config['remote']['server']['username'];
    $password = $config['remote']['server']['password'];
    $host = $config['remote']['server']['host'];
    $ssh_port = $config['remote']['server']['ssh_port'];
    $sshpass = '';
    $db_username = $config['remote']['database']['username'];
    $db_password = $config['remote']['database']['password'];
    $db_name = $config['remote']['database']['name'];
    $filename = 'mysqldump_'.uniqid();

    if ($password) {
        $sshpass = sprintf('sshpass -p "%s" ', $password);
    }

    $command = <<<COMMAND
{$sshpass}ssh -q -p $ssh_port $username@$host << ENDSSH
mysqldump -u$db_username -p$db_password $db_name > $filename.sql
gzip $filename.sql
ENDSSH
COMMAND;

    echo "---> Backing up remote MySQL database...\n";
    exec($command);

    $command = <<<COMMAND
{$sshpass}scp -P $ssh_port $username@$host:{$filename}.sql.gz ./
gzip -d {$filename}.sql.gz
COMMAND;

    echo "---> Copying remote dump file to local server...\n";
    echo shell_exec($command);

    $db_username = $config['local']['database']['username'];
    $db_password = $config['local']['database']['password'];
    $db_name = $config['local']['database']['name'];
    $command = "mysql -u$db_username -p$db_password $db_name < $filename.sql";

    echo "---> Importing SQL dump file to local database...\n";
    exec($command);

    $command = <<<COMMAND
rm $filename.sql
{$sshpass}ssh -q -p $ssh_port $username@$host << ENDSSH
rm $filename.sql.gz
ENDSSH
COMMAND;
    
    echo "---> Cleaning dump files on local and remote servers...\n";
    exec($command);
}

function config_is_valid($config)
{
    return isset($config['remote']) and 
        isset($config['remote']['server']) and 
        isset($config['remote']['server']['host']) and 
        isset($config['remote']['server']['username']) and 
        isset($config['remote']['server']['password']) and 
        isset($config['remote']['server']['ssh_port']) and 
        isset($config['remote']['server']['path']) and 
        isset($config['remote']['database']) and 
        isset($config['remote']['database']['name']) and 
        isset($config['remote']['database']['username']) and 
        isset($config['remote']['database']['password']) and 
        isset($config['local']) and 
        isset($config['local']['server']) and 
        isset($config['local']['server']['path']) and 
        isset($config['local']['database']) and 
        isset($config['local']['database']['name']) and 
        isset($config['local']['database']['username']) and 
        isset($config['local']['database']['password']);
}

function commands_check()
{
    $commands = array('rsync', 'ssh', 'sshpass', 'mysqldump', 'gzip', 'scp');
    foreach ($commands as $command) {
        if (!command_exists($command)) {
            return false;
        }
    }

    return true;
}

function command_exists($command) 
{
    $return = shell_exec("which $command");

    return !empty($return);
}

