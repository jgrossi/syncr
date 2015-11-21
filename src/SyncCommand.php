<?php

namespace Syncr;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// ./syncr.php sync -d up -b -c syncr.json
// ./syncr.php sync --direction up --database --config syncr.json

class SyncCommand extends Command
{
    protected function configure()
    {
        $this->setName('sync')
            ->setDescription('Synchronize files and database between local and remote servers')
            ->addOption('direction', 'd', InputOption::VALUE_REQUIRED, 'Set the transfer process direction: up|down')
            ->addOption('database', 'b', InputOption::VALUE_NONE, 'Set this to sync MySQL database')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Change the defaul config file name', 'syncr.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $direction = $input->getOption('direction');
        $configFile = $input->getOption('config');
        $database = $input->getOption('database');

        $fileTransfer= new FileTransfer($direction, $config = new Config($configFile));
        $fileTransfer->process($output);

        if ($database) {
            $dbTransfer = new DatabaseTransfer($direction, $config);
            $dbTransfer->process($output);
        }
    }
}