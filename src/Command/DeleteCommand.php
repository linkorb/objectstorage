<?php

namespace ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ObjectStorage\Utils;

class DeleteCommand extends Command
{

    protected function configure()
    {
        $this->setName('objectstorage:delete')
            ->setDescription(
                'Delete a key from objectstorage'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Config filename'
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'The key of the file in objectstorage'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configfilename = $input->getOption('config');
        $config = Utils::loadConfig($configfilename);
        $service = Utils::getServiceFromConfig($config);

        $key = $input->getArgument('key');

        $output->writeln("Deleting key '" . $key . "'");
        $service->delete($key);
        $output->writeln("Done");
    }
}
