<?php

namespace LinkORB\Component\ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LinkORB\Component\ObjectStorage\Utils;

class DownloadCommand extends Command
{

    protected function configure()
    {
        $this->setName('objectstorage:download')
            ->setDescription(
                'Download a file from objectstorage to a local file'
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
            )
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Local filename to write to'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configfilename = $input->getOption('config');
        $config = Utils::loadConfig($configfilename);
        $client = Utils::getClientFromConfig($config);

        $filename = $input->getArgument('filename');
        $key = $input->getArgument('key');

        $output->writeln("Downloading key '" . $key . "' to file '" . $filename . "'\n");
        $client->download($key, $filename);
        $output->writeln("Done");
    }
}
