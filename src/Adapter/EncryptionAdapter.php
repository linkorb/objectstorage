<?php

namespace ObjectStorage\Adapter;

use RuntimeException;
use InvalidArgumentException;

class EncryptionAdapter implements StorageAdapterInterface
{
    private $child;
    private $encryption_key;
    private $encryption_iv;

    public function __construct(StorageAdapterInterface $child, $encryption_key, $encryption_iv)
    {
        $this->child = $child;
        $this->encryption_key = $this->hextostr($encryption_key);
        $this->encryption_iv = $this->hextostr($encryption_iv);
    }

    public function setData($key, $data)
    {
        $data = openssl_encrypt($data, 'aes-256-cbc', $this->encryption_key, OPENSSL_RAW_DATA, $this->encryption_iv);
        return $this->child->setData($key, $data);
    }

    public function getData($key)
    {
        $data = $this->child->getData($key);
        $data = openssl_decrypt($data, 'aes-256-cbc', $this->encryption_key, OPENSSL_RAW_DATA, $this->encryption_iv);
        return $data;
    }

    public function deleteData($key)
    {
        return $this->child->deleteData($key);
    }
    
    
    private function strtohex($x)
    {
        $s='';
        foreach (str_split($x) as $c) {
            $s.=sprintf("%02X", ord($c));
        }
        return($s);
    }
    
    private function hextostr($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
}
