<?php

namespace ObjectStorage\Command;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('encrypt')
            ->setDescription(
                'Encrypt a file'
            )
            ->addArgument(
                'keyfile',
                InputArgument::REQUIRED,
                'The path to a file containing an encryption key, such as one generated with the "objectstorage genkey" console command.'
            )
            ->addArgument(
                'infile',
                InputArgument::REQUIRED,
                'The file to encrypt'
            )
            ->addArgument(
                'outfile',
                InputArgument::REQUIRED,
                'The path to which to write the encrypted file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errorOutput = ($output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $plaintextFile = $input->getArgument('infile');
        $encryptedFile = $input->getArgument('outfile');

        try {
            $key = KeyFactory::loadEncryptionKey($input->getArgument('keyfile'));
        } catch (CannotPerformOperation $e) {
            $errorOutput->writeln($e->getMessage());

            return 1;
        }
        if (\file_exists($encryptedFile)) {
            $errorOutput->writeln("<error>The file at \"{$encryptedFile}\" already exists and will not be overwritten.</error>");

            return 2;
        }
        if (!\file_exists($plaintextFile) || !\is_readable($plaintextFile)) {
            $errorOutput->writeln("<error>The file at \"{$plaintextFile}\" cannot be opened for reading.</error>");

            return 3;
        }

        $plaintext = \file_get_contents($plaintextFile);

        if (false === $plaintext) {
            $errorOutput->writeln("<error>The file at \"{$plaintextFile}\" cannot be opened for reading.</error>");

            return 3;
        }

        try {
            $encryptedData = Crypto::encrypt(
                new HiddenString($plaintext),
                $key,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            $errorOutput->writeln($e->getMessage());

            return 4;
        }

        $isWritten = \file_put_contents($encryptedFile, $encryptedData);

        if (false === $isWritten) {
            $errorOutput->writeln("<error>The encrypted data could not be written to the file at \"{$plaintextFile}\".</error>");

            return 5;
        }

        $output->writeln('<info>Successs!</info>');
    }
}
