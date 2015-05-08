<?php

namespace ObjectStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class GenerateKeyCommand extends Command
{
    protected function configure()
    {
        $this->setName('objectstorage:generatekey')
            ->setDescription(
                'Generate encryption key and iv'
            )
        ;
    }
    
    private function strtohex($x)
    {
        $s='';
        foreach (str_split($x) as $c) {
            $s.=sprintf("%02X", ord($c));
        }
        return($s);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        $output->writeln("KEY: " . $this->strtohex($key));
        $output->writeln("IV: " . $this->strtohex($iv));
    }
}
