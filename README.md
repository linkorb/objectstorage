# ObjectStorage library

ObjectStorage library for your cloud-based applications.

## Object Storage vs a normal file system

Object based storage solves large scale storage problems for cloud-based applications.

It provides a simple alternative to regular file system based storage.

### Problems of file system based storage

File-systems are usually accessible by one server at a time.
This can be solved by exposing the file system over a network share using technologies like NFS, SMB, etc.
These sharing technologies have a set of limitations when applied to large-scale applications:

* **Limited scalablity**: You can mount an NFS share from a small set of servers, it is not intended for large amounts of clients
* **Single point of failure**: When your NFS server fails, all your app-servers lose access to all the data
* **Scale up, instead of scale out**: The amount of storage, and the performance, are limited to a single machine. You can buy a bigger machine, but you can't buy more machines to distribute the load (no partitioning)

### Benefits of object based storage

Object based storage works differently: it does not support directories, filenames, etc.
It simply stores raw 'data', by a 'key'. To store data in object storage, you write to a key. To read it back, you read from the key. There are no real 'filenames', or 'directories'. This level of abstraction brings you a set of benefits:

* **Transparant partitioning**: You can easily partition the data by key.
* **Scalability**: you can access the data from as many application servers, without any of those servers having to mount a file-system. It's accessed as a network service.
* **Simple**: As the interface to Object Storage is much simpler (get/set keys) than file system interfaces (read/write/copy/rename/mkdir/ls/rmdir/etc)
* **Flexible**: Because of it's simplicity, it is easier to implement with different physical storage back-ends.

Specific implementations may offer further benefits, such as 'redundancy', 'caching', etc.

## About this library

This library implements a `Client`, that can use various `Drivers` to access different back-ends.

Currently the following drivers are supported:

* **S3**: Store your objects in Amazon S3
* **GridFs**: Store your objects in MongoDB GridFS
* **File**: Store your objects in a local filesystem (for debugging)

To create your own driver, simply create a class that implements a very simple `DriverInterface`. We intend to add support for PDO, Riak CS, Google Cloud Storage and more.

### Example usage:

```php
$driver = new LinkORB\Component\ObjectStorage\Driver\S3Driver($s3client);

$client = new LinkORB\Component\ObjectStorage\Client($driver);

// Upload a local png into object storage
$client->upload('my-photo', '/home/test/some_file.png');

// Download the image from object storage to a new local file
$client->download('my-photo', '/home/test/some_file.png');

// Delete the image from object storage
$client->delete('my-photo');

$message = "Hello world!";

// put the message data into object storage
$client->set('my-message', $message);

// read the message back from object storage
$text = $client->get('my-message');
echo $text; // Outputs "Hello world!";

// Delete the message from object storage
$client->delete('my-message');
```


## Console tool

This library comes with a simple console application that uses the client.
You can use it for testing and introspection.

### Example console commands:

    # Upload a file into object storage
    bin/console objectstorage:upload my-photo /home/test/input.png

    # Download a file from object storage
    bin/console objectstorage:upload my-photo /home/test/output.png

    # Delete data from object storage
    bin/console objectstorage:upload my-photo

### Configuration file

The console tool can be configured using a configuration file.

It will look for a file called `objectstorage.conf` in the current directory. 
Alternatively it will look for `~/.objectstorage.conf` and finally for `/etc/objectstorage.conf`.

You can also specify a config file explicity by using the option `--config myconfig.conf`

### Example config file:

This repository contains a file called `objectstorage.conf.dist` which you can use to copy to `objectstorage.conf` and add your own credentials. The comments in this file explain what options are available.

## Features

* PSR-0 compatible, works with composer and is registered on packagist.org
* PSR-1 and PSR-2 level coding style
* Supports Amazon S3 (`S3Driver`)
* Supports File systems (`FileDriver`)
* Supports MongoDB GridFS (`GridFs`) 
* Included with command line utility for testing and introspection

## Todo

* Add support for more backends (PDO, Riak CS)
* Add support for client-side encryption
* Add support for key-listing by prefix (on selected drivers only)

## Installing

Check out [composer](http://www.getcomposer.org) for details about installing and running composer.

Then, add `linkorb/objectstorage` to your project's `composer.json`:

```json
{
    "require": {
        "linkorb/objectstorage": "dev-master"
    }
}
```

## Contributing

Ready to build and improve on this repo? Excellent!
Go ahead and fork/clone this repo and we're looking forward to your pull requests!

If you are unable to implement changes you like yourself, don't hesitate to
open a new issue report so that we or others may take care of it.

## License

Please check LICENSE.md for full license information
