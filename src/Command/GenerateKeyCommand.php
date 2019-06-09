<?php

namespace ObjectStorage\Command;

use ParagonIE\Halite\KeyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateKeyCommand extends Command
{
    const ARG_PATH = 'path';
    const OPT_SIGNING = 'signing';

    protected function configure()
    {
        $this->setName('genkey')
            ->setDescription(
                'Generate a symmetric encryption or signing key and write it to a file at the supplied path.  This command will not overwrite an existing file.'
            )
            ->addArgument(
                self::ARG_PATH,
                InputArgument::REQUIRED,
                'The path to which to save the key file.'
            )
            ->addOption(
                self::OPT_SIGNING,
                's',
                InputOption::VALUE_NONE
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $path = $input->getArgument(self::ARG_PATH);

        if (\file_exists($path)) {
            $io->error("I cannot create a key file at \"{$path}\" because a file exists there already. I stop!");

            return 1;
        }

        if ($input->getOption(self::OPT_SIGNING)) {
            if (true !== KeyFactory::save(KeyFactory::generateAuthenticationKey(), $path)) {
                $io->error("I tried, but was unable to write the signing key to a file at \"{$path}\". I apologise!");

                return 2;
            }
            $io->success("Signing key saved to \"{$path}\".");

            return 0;
        }

        if (true !== KeyFactory::save(KeyFactory::generateEncryptionKey(), $path)) {
            $io->error("I tried, but was unable to write the encryption key to a file at \"{$path}\". I apologise!");

            return 2;
        }
        $io->success("Encryption key saved to \"{$path}\".");

        return 0;
    }
}
