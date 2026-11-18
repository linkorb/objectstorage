<?php

// The legacy mongo extension does not load on PHP 8. These stand-ins let
// GridFsAdapter::build finish, and they keep the server and database name
// the method chose.

if (!class_exists('MongoGridFS', false)) {
    class MongoGridFS
    {
        public $server;
        public $dbname;

        public function __construct($server, $dbname)
        {
            $this->server = $server;
            $this->dbname = $dbname;
        }
    }
}

if (!class_exists('MongoClient', false)) {
    class MongoClient
    {
        private $server;

        public function __construct($server)
        {
            $this->server = $server;
        }

        public function selectDB($dbname)
        {
            return new GridFsAdapterBuildDatabase($this->server, $dbname);
        }
    }

    class GridFsAdapterBuildDatabase
    {
        private $server;
        private $dbname;

        public function __construct($server, $dbname)
        {
            $this->server = $server;
            $this->dbname = $dbname;
        }

        public function getGridFS()
        {
            return new MongoGridFS($this->server, $this->dbname);
        }
    }
}
