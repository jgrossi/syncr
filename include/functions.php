<?php

function commands_available()
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