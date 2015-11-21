<?php

namespace Syncr;

use Symfony\Component\Console\Output\OutputInterface;

interface TransferInterface
{
    public function process(OutputInterface $output);
}