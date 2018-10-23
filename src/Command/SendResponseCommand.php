<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendResponseCommand extends Command
{
    const RETURN_CODE_OK = 0;

    protected function configure()
    {
        $this
            ->setName('web-resource-cache:send-response')
            ->setDescription('Send the response for a request to the given callback URLs')
            ->addArgument('request-hash', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        return self::RETURN_CODE_OK;
    }
}
