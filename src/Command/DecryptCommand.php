<?php

namespace ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class DecryptCommand extends Command
{
    protected function configure()
    {
        $this->setName('objectstorage:decrypt')
            ->setDescription(
                'Decrypt a file'
            )
        ->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'The file to decrypt'
        )
        ;
    }

    private function strtohex($x)
    {
        $s = '';
        foreach (str_split($x) as $c) {
            $s .= sprintf('%02X', ord($c));
        }

        return $s;
    }

    private function hextostr($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        return $string;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = getenv('OBJECTSTORAGE_ENCRYPTION_KEY');
        $iv = getenv('OBJECTSTORAGE_ENCRYPTION_IV');

        if (!$key || !$iv) {
            throw new RuntimeException('Could not obtain encryption key + iv from environment');
        }

        $filename = $input->getArgument('filename');

        $key = $this->hextostr($key);
        $iv = $this->hextostr($iv);

        $data = file_get_contents($filename);
        $res = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        echo $res;
    }
}
