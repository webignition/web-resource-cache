<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReturnResourceCommand extends Command
{
    const RETURN_CODE_OK = 0;

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:return-resource')
            ->setDescription('Return a resource to the given callback URLs')
            ->addArgument('id', InputArgument::REQUIRED, 'id of resource to return');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        return self::RETURN_CODE_OK;
    }
}
