<?php

namespace Syncr;

use Symfony\Component\Console\Output\OutputInterface;

class DatabaseTransfer implements TransferInterface
{
    protected $direction;
    protected $config;

    public function __construct($direction, Config $config)
    {
        $this->direction = $direction;
        $this->config = $config;
    }

    public function process(OutputInterface $output)
    {

    }

    protected function processUp()
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

    protected function processDown()
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
}