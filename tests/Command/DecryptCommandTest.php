<?php

namespace ObjectStorage\Test\Command;

use ObjectStorage\Command\DecryptCommand;
use ParagonIE\Halite\Halite;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\HiddenString\MisuseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptCommandTest extends TestCase
{
    private $workspace;
    private $key;
    private $keyfile;

    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/objectstorage-decrypt-'.bin2hex(random_bytes(4));
        mkdir($this->workspace);
        $this->key = KeyFactory::generateEncryptionKey();
        $this->keyfile = $this->workspace.'/key';
        KeyFactory::save($this->key, $this->keyfile);
        stream_wrapper_register('osdecryptunreadable', DecryptCommandUnreadableStream::class);
        stream_wrapper_register('osdecryptopenfail', DecryptCommandUnopenableStream::class);
    }

    protected function tearDown(): void
    {
        if (in_array('osdecryptunreadable', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('osdecryptunreadable');
        }
        if (in_array('osdecryptopenfail', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('osdecryptopenfail');
        }
        $this->removeTree($this->workspace);
    }

    public function testUnreadableKeyfileReturnsOne()
    {
        $output = new BufferedOutput();
        $keyfile = $this->workspace.'/missing.key';

        $code = $this->execute($output, $keyfile, $this->workspace.'/cipher', $this->workspace.'/plain');

        $this->assertSame(1, $code);
        $this->assertSame("Cannot read keyfile: {$keyfile}\n", $output->fetch());
    }

    public function testExistingPlaintextFileReturnsTwoOnTheErrorOutput()
    {
        $output = new DecryptCommandSplitOutput();
        $outfile = $this->workspace.'/plain';
        file_put_contents($outfile, 'already there');

        $code = $this->execute($output, $this->keyfile, $this->workspace.'/cipher', $outfile);

        $this->assertSame(2, $code);
        $this->assertSame('', $output->fetch());
        $this->assertSame(
            "The file at \"{$outfile}\" already exists and will not be overwritten.\n",
            $output->getErrorOutput()->fetch()
        );
    }

    public function testMissingCiphertextReturnsThree()
    {
        $output = new BufferedOutput();
        $infile = $this->workspace.'/missing.cipher';
        $this->assertFalse(file_exists($infile));

        $code = $this->execute($output, $this->keyfile, $infile, $this->workspace.'/plain');

        $this->assertSame(3, $code);
        $this->assertSame(
            "The file at \"{$infile}\" cannot be opened for reading.\n",
            $output->fetch()
        );
    }

    public function testUnreadableCiphertextReturnsThree()
    {
        $output = new BufferedOutput();
        $infile = 'osdecryptunreadable://cipher';
        $this->assertTrue(file_exists($infile));
        $this->assertFalse(is_readable($infile));

        $code = $this->execute($output, $this->keyfile, $infile, $this->workspace.'/plain');

        $this->assertSame(3, $code);
        $this->assertSame(
            "The file at \"{$infile}\" cannot be opened for reading.\n",
            $output->fetch()
        );
    }

    public function testCiphertextStreamThatFailsToOpenReturnsThree()
    {
        $output = new BufferedOutput();
        $infile = 'osdecryptopenfail://cipher';
        $this->assertTrue(file_exists($infile));
        $this->assertTrue(is_readable($infile));

        $warnings = [];
        set_error_handler(function ($severity, $message) use (&$warnings) {
            if (E_WARNING !== $severity) {
                return false;
            }
            $warnings[] = $message;

            return true;
        });
        try {
            $code = $this->execute($output, $this->keyfile, $infile, $this->workspace.'/plain');
        } finally {
            restore_error_handler();
        }

        $this->assertSame(3, $code);
        $this->assertSame(
            "The file at \"{$infile}\" cannot be opened for reading.\n",
            $output->fetch()
        );
        $this->assertSame(
            [
                'file_get_contents(osdecryptopenfail://cipher): failed to open stream: "ObjectStorage\\Test\\Command\\DecryptCommandUnopenableStream::stream_open" call failed',
            ],
            $warnings
        );
    }

    public function testDecryptedPlaintextCannotBeInlinedAsAString()
    {
        // Crypto::decrypt() returns a HiddenString. The command casts it, and that cast throws.
        $output = new BufferedOutput();
        $infile = $this->workspace.'/cipher';
        $outfile = $this->workspace.'/plain';
        file_put_contents(
            $infile,
            Crypto::encrypt(new HiddenString('hello'), $this->key, Halite::ENCODE_BASE64URLSAFE)
        );

        try {
            $this->execute($output, $this->keyfile, $infile, $outfile);
            $this->fail('Decrypting a real ciphertext must throw MisuseException.');
        } catch (MisuseException $exception) {
            $this->assertSame(
                'This HiddenString object cannot be inlined as a string.',
                $exception->getMessage()
            );
        }

        $this->assertSame('', $output->fetch());
        $this->assertFileNotExists($outfile);
    }

    private function execute(OutputInterface $output, $keyfile, $infile, $outfile)
    {
        $command = new ExposedDecryptCommand();
        $input = new ArrayInput([
            'keyfile' => $keyfile,
            'infile' => $infile,
            'outfile' => $outfile,
        ]);
        $input->bind($command->getDefinition());

        return $command->execute($input, $output);
    }

    private function removeTree($path)
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $full = $path.'/'.$entry;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}

class ExposedDecryptCommand extends DecryptCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::execute($input, $output);
    }
}

class DecryptCommandSplitOutput extends BufferedOutput implements ConsoleOutputInterface
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

class DecryptCommandStatStream
{
    public $context;

    public function url_stat($path, $flags)
    {
        $mode = $this->statMode();
        $names = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];
        $values = [0, 0, $mode, 1, 0, 0, 0, 4, 0, 0, 0, 4096, 0];
        $stat = [];
        foreach ($names as $index => $name) {
            $stat[$index] = $values[$index];
            $stat[$name] = $values[$index];
        }

        return $stat;
    }

    protected function statMode()
    {
        return 0100644;
    }
}

class DecryptCommandUnreadableStream extends DecryptCommandStatStream
{
    protected function statMode()
    {
        return 0100000;
    }
}

class DecryptCommandUnopenableStream extends DecryptCommandStatStream
{
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        return false;
    }
}
