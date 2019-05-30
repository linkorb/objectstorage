<?php

namespace ObjectStorage\Command;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptCommand extends Command
{
    protected function configure()
    {
        $this->setName('decrypt')
            ->setDescription(
                'Decrypt a file'
            )
            ->addArgument(
                'keyfile',
                InputArgument::REQUIRED,
                'The path to a file containing an encryption key, such as one generated with the "objectstorage genkey" console command.'
            )
            ->addArgument(
                'infile',
                InputArgument::REQUIRED,
                'The file to decrypt'
            )
            ->addArgument(
                'outfile',
                InputArgument::REQUIRED,
                'The path to which to write the decrypted file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errorOutput = ($output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $encryptedFile = $input->getArgument('infile');
        $plaintextFile = $input->getArgument('outfile');

        try {
            $key = KeyFactory::loadEncryptionKey($input->getArgument('keyfile'));
        } catch (CannotPerformOperation $e) {
            $errorOutput->writeln($e->getMessage());

            return 1;
        }
        if (\file_exists($plaintextFile)) {
            $errorOutput->writeln("<error>The file at \"{$plaintextFile}\" already exists and will not be overwritten.</error>");

            return 2;
        }
        if (!\file_exists($encryptedFile) || !\is_readable($encryptedFile)) {
            $errorOutput->writeln("<error>The file at \"{$encryptedFile}\" cannot be opened for reading.</error>");

            return 3;
        }

        $ciphertext = \file_get_contents($encryptedFile);

        if (false === $ciphertext) {
            $errorOutput->writeln("<error>The file at \"{$encryptedFile}\" cannot be opened for reading.</error>");

            return 3;
        }

        try {
            $plaintext = (string) Crypto::decrypt(
                $ciphertext,
                $key,
                Halite::ENCODE_BASE64URLSAFE
            );
        } catch (CannotPerformOperation $e) {
            $errorOutput->writeln($e->getMessage());

            return 4;
        }

        $isWritten = \file_put_contents($plaintextFile, $plaintext);

        if (false === $isWritten) {
            $errorOutput->writeln("<error>The decrypted data could not be written to the file at \"{$plaintextFile}\".</error>");

            return 5;
        }

        $output->writeln('<info>Successs!</info>');
    }
}
