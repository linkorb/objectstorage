# ObjectStorage 4.0 library

ObjectStorage library for your cloud-based applications.

## Object Storage vs a normal file system

Object-based storage solves large scale storage problems for cloud-based applications.

It provides a simple alternative to regular filesystem-based storage.

### Problems of filesystem-based storage

Filesystems are usually accessible by one server at a time.
This can be solved by exposing the filesystem over a network share using technologies like NFS, SMB, etc.
These sharing technologies have a set of limitations when applied to large-scale applications:

* **Limited scalablity**: You can mount an NFS share from a small set of servers, it is not intended for large amounts of clients
* **Single point of failure**: When your NFS server fails, *all* your app-servers lose access to *all* of the data
* **Scale up, instead of scale out**: The amount of storage, and the performance, are limited to a single machine. You can buy a bigger machine, but you can't buy more machines to distribute the load (no partitioning)

### Benefits of object-based storage

Object-based storage works differently: it does not support 'directories', 'filenames', etc. It just knows about 'keys'.
You simply store raw 'data' by a 'key'. To store data in objectstorage, you write to a key. To read it back, you read from the key. There are no real 'filenames', or 'directories'. This level of abstraction brings you a set of huge benefits:

* **Transparant partitioning**: You can easily partition the data by key
* **Scalability**: you can access the data from as many application servers as you want, without any of those servers having to mount a file-system. It's accessed as a network service.
* **Simple**: Interfacing with an objectstorage backend is much simpler (get/set keys) than file system interfaces (read/write/copy/rename/mkdir/ls/rmdir/etc)
* **Flexible**: Because of this simplicity, it is very easy to implement new physical storage back-ends. This protects you from getting stuck with sub-optimal storage solutions.

Specific implementations may offer further benefits, such as 'redundancy', 'caching', etc.

## About this library

This library implements a "Service", that can use various "Adapters" to access different "storage back-ends".

Currently the following adapters are implemented:

* **S3**: Store your objects in [Amazon S3](http://aws.amazon.com/s3/)
* **GridFs**: Store your objects in [MongoDB GridFS](http://docs.mongodb.org/manual/core/gridfs/)
* **PDO**: Store your objects in a relational database (for dev/testing/debugging)
* **File**: Store your objects in a local filesystem (for dev/testing/debugging)

To create your own adapter, simply create a class that implements the very simple `StorageAdapterInterface`. It is trivial to add support for Riak CS, Google Cloud Storage, etc.

### Example usage:

```php
// Instantiate a driver of your choice (file, s3, gridfs, pdo, etc...)
$adapter = ObjectStorage\Adapter\PdoAdapter::build($config);

// Instantiate an ObjectStorage Service that uses the adapter instance
$service = new ObjectStorage\Service($adapter);

// Upload a local png into object storage
$service->upload('my-photo', '/home/test/some_file.png');

// Download the image from object storage to a new local file
$service->download('my-photo', '/home/test/some_file.png');

// Delete the image from object storage
$service->delete('my-photo');

$message = "Hello world!";

// put the message data into object storage
$service->set('my-message', $message);

// read the message back from object storage
$text = $service->get('my-message');
echo $text; // Outputs "Hello world!";

// Delete the message from object storage
$service->delete('my-message');
```

### Encryption

The library includes adapters to allow you to transparently encrypt/decrypt
your data before it's passed to the storage backend.

This is done by wrapping the original storage adapter (s3, file, pdo, gridfs,
etc) into the one of the encryption adapters. Here's an example

```php
$adapter = new \ObjectStorage\Adapter\EncryptedStorageAdapter(
    new \ObjectStorage\Adapter\PdoAdapter($pdo),
    \ParagonIE\Halite\KeyFactory::loadAuthenticationKey($pathToSigningKey),
    \ParagonIE\Halite\KeyFactory::loadEncryptionKey($pathToEncryptionKey)
);
// You can use $adapter as before and both the storage keys and objects will be
// encrypted (use PlaintextKeyEncryptedStorageAdapter if you don't want the
// storage keys to be encrypted).
```

The encryption routines are provided by [ParagonIE/Halite][] and libsodium.

Use the following commands to generate and save a signing key and an encryption
key as needed in the previous example:-

```sh
./bin/objectstorage genkey --signing /path/to/a/file
./bin/objectstorage genkey /path/to/another/file
```

You can also use the included encrypt + decrypt commands.  In the following
example we encrypt `example.pdf` with the encryption key in `key.asc` and then
decrypt it again, using the same key and writing it to a new `example-new.pdf`:

```sh
bin/objectstorage encrypt key.asc example.pdf example.pdf.encrypted
bin/objectstorage decrypt key.asc example.pdf.encrypted example-new.pdf
```
    
## Console tool

This library comes with a simple console application that uses the library.
You can use it for testing and introspection.

### Example console commands:

    # Upload a file into object storage
    bin/objectstorage objectstorage:upload my-photo /home/test/input.png

    # Download a file from object storage
    bin/objectstorage objectstorage:upload my-photo /home/test/output.png

    # Delete data from object storage
    bin/objectstorage objectstorage:upload my-photo

### Configuration file

The console tool can be configured using a configuration file.

It will look for a file called `objectstorage.conf` in the current directory. 
Alternatively it will look for `~/.objectstorage.conf` and finally for `/etc/objectstorage.conf`.

You can also specify a config file explicity by using the option `--config myconfig.conf`

### Example config file:

This repository contains a file called `objectstorage.conf.dist` which you can use
to copy to `objectstorage.conf` and add your own credentials.

The comments in this file explain what options are available.

## Features

* PSR-0 compatible, works with composer and is registered on packagist.org
* PSR-1 and PSR-2 level coding style
* Supports Amazon S3 (`S3Adapter`)
* Supports MongoDB GridFS (`GridFsAdapter`) 
* Supports MySQL, PostgreSQL, Oracle, SQLite, MS SQL Server, etc through PDO (`PdoAdapter`) 
* Supports File systems (`FileAdapter`)
* Includes a CLI utility for testing and introspection

## Todo (Pull-requests welcome!)

* Add support for more backends (Riak CS, Google Cloud Storage, etc)
* Add support for client-side encryption
* Add support for key-listing by prefix (on selected drivers only)

## Installing

Check out [composer](http://www.getcomposer.org) for details about installing and running composer.

Then, add `linkorb/objectstorage` to your project's `composer.json`:

```json
{
    "require": {
        "linkorb/objectstorage": "^4.0"
    }
}
```

## Older versions of this library

Version 1.0, previously only available as dev-master, is still available by
updating your composer.json to require version "~1.0".

The `php5` branch will still work with PHP <= 5.6, but it will not have the
latest features and, particularly, should not be used if you need encrypted
storage.

## Contributing

Ready to build and improve on this repo? Excellent!
Go ahead and fork/clone this repo and we're looking forward to your pull requests!

If you are unable to implement changes you like yourself, don't hesitate to
open a new issue report so that we or others may take care of it.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!

## License

Please check LICENSE.md for full license information

[ParagonIE/Halite]: <https://paragonie.com/project/halite> "Halite - Simple PHP Cryptography Library"
