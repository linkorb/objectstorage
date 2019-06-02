<?php

namespace ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ObjectStorage\Utils;

class UploadCommand extends Command
{
    protected function configure()
    {
        $this->setName('objectstorage:upload')
            ->setDescription(
                'Upload a local file into objectstorage'
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
                'The key to store the uploaded file under'
            )
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Local filename to upload'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configfilename = $input->getOption('config');
        $config = Utils::loadConfig($configfilename);
        $service = Utils::getServiceFromConfig($config);

        $filename = $input->getArgument('filename');
        $key = $input->getArgument('key');

        $output->writeln("Uploading '" . $filename . "' as key '" . $key . "'");
        $service->upload($key, $filename);
        $output->writeln('Done');
    }
}
