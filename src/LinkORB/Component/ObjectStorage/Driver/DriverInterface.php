<?php

namespace LinkORB\Component\ObjectStorage\Driver;

interface DriverInterface
{
    public function get($key);
    public function set($key, $data);
    public function delete($key);

}
