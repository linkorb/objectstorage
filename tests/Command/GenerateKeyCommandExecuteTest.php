<?php

namespace ObjectStorage\Test\Command;

use ObjectStorage\Command\GenerateKeyCommand;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeyCommandExecuteTest extends TestCase
{
    private $root;

    public static function setUpBeforeClass(): void
    {
        // SymfonyStyle wraps on the terminal width. A wide column keeps each message on one line.
        putenv('COLUMNS=200');
    }

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/g'.bin2hex(random_bytes(2));
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testExistingFileReturnsOneAndLeavesTheFileAlone()
    {
        $path = $this->root.'/key';
        file_put_contents($path, 'already there');
        $output = new BufferedOutput();

        $status = $this->execute($output, $path, false);

        $this->assertSame(1, $status);
        $this->assertStringContainsString(
            '[ERROR] I cannot create a key file at "'.$path.'" because a file exists there already. I stop!',
            $output->fetch()
        );
        $this->assertSame('already there', file_get_contents($path));
    }

    public function testSigningKeyIsWrittenAndReported()
    {
        $path = $this->root.'/sign.key';
        $output = new BufferedOutput();

        $status = $this->execute($output, $path, true);

        $this->assertSame(0, $status);
        $this->assertStringContainsString(
            '[OK] Signing key saved to "'.$path.'".',
            $output->fetch()
        );
        $this->assertInstanceOf(AuthenticationKey::class, KeyFactory::loadAuthenticationKey($path));
    }

    public function testSigningKeyWriteFailureReturnsTwo()
    {
        $path = $this->root.'/m/k';
        $output = new BufferedOutput();

        // file_put_contents warns, then returns false. The command reports that as a failed save.
        $status = $this->withoutWarnings(function () use ($output, $path) {
            return $this->execute($output, $path, true);
        });

        $this->assertSame(2, $status);
        $this->assertStringContainsString(
            '[ERROR] I tried, but was unable to write the signing key to a file at "'.$path.'". I apologise!',
            $output->fetch()
        );
        $this->assertFileNotExists($path);
    }

    public function testEncryptionKeyIsWrittenAndReported()
    {
        $path = $this->root.'/enc.key';
        $output = new BufferedOutput();

        $status = $this->execute($output, $path, false);

        $this->assertSame(0, $status);
        $this->assertStringContainsString(
            '[OK] Encryption key saved to "'.$path.'".',
            $output->fetch()
        );
        $this->assertInstanceOf(EncryptionKey::class, KeyFactory::loadEncryptionKey($path));
    }

    public function testEncryptionKeyWriteFailureReturnsTwo()
    {
        $path = $this->root.'/m/k';
        $output = new BufferedOutput();

        // file_put_contents warns, then returns false. The command reports that as a failed save.
        $status = $this->withoutWarnings(function () use ($output, $path) {
            return $this->execute($output, $path, false);
        });

        $this->assertSame(2, $status);
        $this->assertStringContainsString(
            '[ERROR] I tried, but was unable to write the encryption key to a file at "'.$path.'". I apologise!',
            $output->fetch()
        );
        $this->assertFileNotExists($path);
    }

    private function execute(OutputInterface $output, $path, $signing)
    {
        $command = new ExposedGenerateKeyCommand();
        $parameters = ['path' => $path];
        if ($signing) {
            $parameters['--signing'] = true;
        }
        $input = new ArrayInput($parameters);
        $input->bind($command->getDefinition());

        return $command->execute($input, $output);
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

class ExposedGenerateKeyCommand extends GenerateKeyCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::execute($input, $output);
    }
}
