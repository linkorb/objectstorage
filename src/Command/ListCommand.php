<?php

namespace ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ObjectStorage\Utils as Utils;

class ListCommand extends Command
{
    protected function configure()
    {
        $this->setName('objectstorage:list')
            ->setDescription(
                'List keys with specified prefix'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Config filename'
            )
            ->addArgument(
                'prefix',
                InputArgument::OPTIONAL,
                'The prefix to scan for'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configfilename = $input->getOption('config');
        $config = Utils::loadConfig($configfilename);
        $service = Utils::getServiceFromConfig($config);

        $prefix = $input->getArgument('prefix');

        $output->writeln("Listing keys /w prefix '" . $prefix . "'\n");
        $keys = $service->listKeys($prefix);

        foreach ($keys as $key) {
            $output->writeln("* Key: '" . $key->getKey() . "'");
            $metadata = $key->getMetaData();
            foreach ($metadata as $k => $v) {
                $output->writeln("   - '$k' = " . (string) $v);
            }
        }
        $output->writeln('Done');
    }
}
