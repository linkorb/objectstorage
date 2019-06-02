<?php

namespace ObjectStorage\Command;

use ParagonIE\Halite\KeyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GenerateKeyCommand extends Command
{
    const ARG_PATH = 'path';

    protected function configure()
    {
        $this->setName('genkey')
            ->setDescription(
                'Generate a symmetric encryption key and write it to a file at the supplied path.  This command will not overwrite an existing file.'
            )
            ->addArgument(
                self::ARG_PATH,
                InputArgument::REQUIRED,
                'The path to which to save the key file.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument(self::ARG_PATH);

        if (\file_exists($path)) {
            $output->writeln("I cannot create a key file at \"{$path}\" because a file exists there already. I stop!");

            return 1;
        }

        if (true !== KeyFactory::save(KeyFactory::generateEncryptionKey(), $path)) {
            $output->writeln("I tried, but was unable to write the key to a file at \"{$path}\". I apologise!");

            return 2;
        }
    }
}
