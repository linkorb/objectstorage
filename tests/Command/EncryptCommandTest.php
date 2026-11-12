<?php

namespace ObjectStorage\Test\Command;

use ObjectStorage\Command\EncryptCommand;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptCommandTest extends TestCase
{
    private $root;

    public static function setUpBeforeClass(): void
    {
        if (!stream_wrapper_register('enccmd', EncryptCommandPathProbe::class)) {
            self::fail('The enccmd stream wrapper is already registered.');
        }
    }

    public static function tearDownAfterClass(): void
    {
        stream_wrapper_unregister('enccmd');
    }

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/encrypt-command-'.bin2hex(random_bytes(8));
        mkdir($this->root);
        EncryptCommandPathProbe::$readable = true;
        EncryptCommandPathProbe::$opens = true;
        clearstatcache();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testMissingKeyfileReturnsOneOnTheSameOutput()
    {
        $output = new BufferedOutput();
        $keyfile = $this->root.'/missing.key';
        $ciphertext = $this->root.'/cipher.txt';

        $status = $this->execute($output, $keyfile, $this->root.'/plain.txt', $ciphertext);

        $this->assertSame(1, $status);
        $this->assertSame("Cannot read keyfile: {$keyfile}\n", $output->fetch());
        $this->assertFileNotExists($ciphertext);
    }

    public function testExistingCiphertextReturnsTwoOnTheErrorOutput()
    {
        $output = new SplitErrorOutput();
        $ciphertext = $this->root.'/cipher.txt';
        file_put_contents($ciphertext, 'original');

        $status = $this->execute($output, $this->keyPath(), $this->root.'/plain.txt', $ciphertext);

        $this->assertSame(2, $status);
        $this->assertSame('', $output->fetch());
        $this->assertSame(
            'The file at "'.$ciphertext.'" already exists and will not be overwritten.'."\n",
            $output->getErrorOutput()->fetch()
        );
        $this->assertSame('original', file_get_contents($ciphertext));
    }

    public function testMissingPlaintextReturnsThree()
    {
        $output = new BufferedOutput();
        $plaintext = $this->root.'/missing.txt';
        $ciphertext = $this->root.'/cipher.txt';

        $status = $this->execute($output, $this->keyPath(), $plaintext, $ciphertext);

        $this->assertSame(3, $status);
        $this->assertSame(
            'The file at "'.$plaintext.'" cannot be opened for reading.'."\n",
            $output->fetch()
        );
        $this->assertFileNotExists($ciphertext);
    }

    public function testUnreadablePlaintextReturnsThree()
    {
        EncryptCommandPathProbe::$readable = false;
        clearstatcache();
        $plaintext = 'enccmd://plain';
        $this->assertTrue(file_exists($plaintext));
        $this->assertFalse(is_readable($plaintext));

        $output = new BufferedOutput();
        $ciphertext = $this->root.'/cipher.txt';
        $status = $this->execute($output, $this->keyPath(), $plaintext, $ciphertext);

        $this->assertSame(3, $status);
        $this->assertSame(
            'The file at "'.$plaintext.'" cannot be opened for reading.'."\n",
            $output->fetch()
        );
        $this->assertFileNotExists($ciphertext);
    }

    public function testPlaintextReadFailureReturnsThree()
    {
        EncryptCommandPathProbe::$opens = false;
        clearstatcache();

        $output = new BufferedOutput();
        $plaintext = 'enccmd://plain';
        $ciphertext = $this->root.'/cipher.txt';
        $status = $this->withoutWarnings(function () use ($output, $plaintext, $ciphertext) {
            return $this->execute($output, $this->keyPath(), $plaintext, $ciphertext);
        });

        $this->assertSame(3, $status);
        $this->assertSame(
            'The file at "'.$plaintext.'" cannot be opened for reading.'."\n",
            $output->fetch()
        );
        $this->assertFileNotExists($ciphertext);
    }

    public function testWriteFailureReturnsFiveAndNamesThePlaintextPath()
    {
        $plaintext = $this->root.'/plain.txt';
        file_put_contents($plaintext, 'secret');
        $ciphertext = $this->root.'/missing-dir/cipher.txt';
        $output = new BufferedOutput();

        // file_put_contents warns, then returns false. The reported path is the plaintext.
        $status = $this->withoutWarnings(function () use ($output, $plaintext, $ciphertext) {
            return $this->execute($output, $this->keyPath(), $plaintext, $ciphertext);
        });

        $this->assertSame(5, $status);
        $this->assertSame(
            'The encrypted data could not be written to the file at "'.$plaintext.'".'."\n",
            $output->fetch()
        );
        $this->assertFileNotExists($ciphertext);
    }

    public function testEncryptsThePlaintextAndReportsSuccesss()
    {
        $plaintext = $this->root.'/plain.txt';
        file_put_contents($plaintext, 'secret');
        $ciphertext = $this->root.'/cipher.txt';
        $keyfile = $this->keyPath();
        $output = new BufferedOutput();

        $status = $this->execute($output, $keyfile, $plaintext, $ciphertext);

        $this->assertNull($status);
        $this->assertSame("Successs!\n", $output->fetch());
        $decrypted = Crypto::decrypt(
            file_get_contents($ciphertext),
            KeyFactory::loadEncryptionKey($keyfile),
            Halite::ENCODE_BASE64URLSAFE
        );
        $this->assertSame('secret', $decrypted->getString());
    }

    private function execute(OutputInterface $output, $keyfile, $plaintext, $ciphertext)
    {
        $command = new ExposedEncryptCommand();
        $input = new ArrayInput([
            'keyfile' => $keyfile,
            'infile' => $plaintext,
            'outfile' => $ciphertext,
        ]);
        $input->bind($command->getDefinition());

        return $command->execute($input, $output);
    }

    private function keyPath()
    {
        $path = $this->root.'/key';
        KeyFactory::save(KeyFactory::generateEncryptionKey(), $path);

        return $path;
    }

    private function withoutWarnings(callable $call)
    {
        set_error_handler(static function () {
            return true;
        });
        try {
            return $call();
        } finally {
            restore_error_handler();
        }
    }

    private function removeDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path.'/'.$entry;
            if (is_dir($child)) {
                $this->removeDirectory($child);
                continue;
            }
            unlink($child);
        }
        rmdir($path);
    }
}

class ExposedEncryptCommand extends EncryptCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::execute($input, $output);
    }
}

class SplitErrorOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private $errorOutput;

    public function __construct()
    {
        parent::__construct();
        $this->errorOutput = new BufferedOutput();
    }

    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error)
    {
        $this->errorOutput = $error;
    }
}

class EncryptCommandPathProbe
{
    public $context;
    public static $readable = true;
    public static $opens = true;

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        return self::$opens;
    }

    public function stream_read($count)
    {
        return '';
    }

    public function stream_eof()
    {
        return true;
    }

    public function stream_stat()
    {
        return $this->statArray();
    }

    public function url_stat($path, $flags)
    {
        return $this->statArray();
    }

    private function statArray()
    {
        $mode = self::$readable ? 0100644 : 0100222;

        return [
            0 => 0,
            1 => 0,
            2 => $mode,
            3 => 1,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => -1,
            12 => -1,
            'dev' => 0,
            'ino' => 0,
            'mode' => $mode,
            'nlink' => 1,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => -1,
            'blocks' => -1,
        ];
    }
}
