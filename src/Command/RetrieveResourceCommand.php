<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetrieveResourceCommand extends Command
{
    const RETURN_CODE_OK = 0;

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:get-resource')
            ->setDescription('Retrieve a resource')
            ->addArgument('id', InputArgument::REQUIRED, 'id of resource to retrieve');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        return self::RETURN_CODE_OK;
    }
}
