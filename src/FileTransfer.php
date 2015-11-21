<?php

namespace Syncr;

use Symfony\Component\Console\Output\OutputInterface;

class FileTransfer implements TransferInterface
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
        $remoteServerConfig = $this->getRemoteServerConfig();
        list($host, $username, $password, $sshPort, $path) = $remoteServerConfig;

        $from = trim($this->config->read('local', 'server')['path'], '/') . '/';
        $to = sprintf('%s@%s:%s', $username, $host, trim($path, '/') . '/');

        $cmd = '%s rsync -zavP -e "ssh -p %s" %s "%s" "%s"';
        $sshpass = $password ? sprintf('sshpass -p "%s"', $password) : '';
        $exclude = $this->getExcludeOption();

        if ($this->direction == 'up') {
            $output->writeln('<comment>Sending files to remote server...</comment>');
            $cmd = sprintf($cmd, $sshpass, $sshPort, $exclude, $from, $to);
        } elseif ($this->direction == 'down') {
            $output->writeln('<comment>Getting files from remote server...</comment>');
            $cmd = sprintf($cmd, $sshpass, $sshPort, $exclude, $to, $from);
        }

        exec(trim($cmd));
        $output->writeln('<info>File synchronization finished!</info>');
    }

    protected function getRemoteServerConfig()
    {
        $remote = $this->config->read('remote', 'server');

        return [
            $remote['host'],
            $remote['username'],
            $remote['password'],
            $remote['ssh_port'],
            $remote['path']
        ];
    }

    protected function getExcludeOption()
    {
        $files = $this->getExcludeFiles();

        if (count($files) > 0) {
            $cmd = '';
            foreach ($files as $file) {
                $cmd .= '--exclude '.$file.' ';
            }

            return trim($cmd);
        }
    }

    protected function getExcludeFiles()
    {
        if ($this->direction == 'up') {
            $server = 'remote';
            $ignore = 'ignore_from_local';
        } elseif ($this->direction == 'down') {
            $server = 'local';
            $ignore = 'ignore_from_remote';
        }

        return $this->config->read($server, $ignore);
    }
}