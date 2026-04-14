<?php

namespace Utopia\Console\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Utopia\Command;
use Utopia\Console;

class ConsoleTest extends TestCase
{
    public function testLogs(): void
    {
        $this->assertEquals(4, Console::log('log'));
        $this->assertEquals(17, Console::success('success'));
        $this->assertEquals(14, Console::info('info'));
        $this->assertEquals(19, Console::warning('warning'));
        $this->assertEquals(15, Console::error('error'));
        $this->assertEquals('this is an answer', Console::confirm('this is a question'));
    }

    public function testExecuteBasic(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add("echo 'hello world';"), $input, $output, $stderr, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);
    }

    public function testCommandToArray(): void
    {
        $command = (new Command('php'))
            ->add('-r')
            ->add("echo 'hello world';");

        $this->assertSame(['php', '-r', "echo 'hello world';"], $command->toArray());
    }

    public function testCommandToStringEscapesArguments(): void
    {
        $command = (new Command('php'))
            ->add('-r')
            ->add("echo 'hello'; rm -rf /");

        $this->assertSame("'php' '-r' 'echo '\''hello'\''; rm -rf /'", $command->toString());
    }

    public function testCommandValidatorCallable(): void
    {
        $command = (new Command('git'))
            ->add('checkout')
            ->add('feature/test-1', fn (string $value): bool => preg_match('/^[A-Za-z0-9._\/-]+$/', $value) === 1);

        $this->assertSame(['git', 'checkout', 'feature/test-1'], $command->toArray());
    }

    public function testCommandValidatorFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid command argument: feature/test; rm -rf /');

        (new Command('git'))
            ->add('checkout')
            ->add('feature/test; rm -rf /', fn (string $value): bool => preg_match('/^[A-Za-z0-9._\/-]+$/', $value) === 1);
    }

    public function testExecuteArray(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $cmd = ['php', '-r', "echo 'hello world';"];
        $code = Console::execute($cmd, $input, $output, $stderr, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);
    }

    public function testExecuteEnvVariables(): void
    {
        $randomData = base64_encode(random_bytes(10));
        putenv("FOO={$randomData}");

        $output = '';
        $stderr = '';
        $input = '';
        $cmd = ['printenv'];
        $code = Console::execute($cmd, $input, $output, $stderr, 10);

        $this->assertEquals(0, $code);

        $data = [];
        foreach (explode("\n", $output) as $row) {
            if (empty($row)) {
                continue;
            }

            $kv = explode('=', $row, 2);
            $this->assertEquals(2, count($kv), $row);
            $data[$kv[0]] = $kv[1];
        }

        $this->assertArrayHasKey('FOO', $data);
        $this->assertEquals($randomData, $data['FOO']);
    }

    public function testExecuteStream(): void
    {
        $output = '';
        $stderr = '';
        $input = '';

        $outputStream = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add('for ($i = 1; $i <= 5; $i++) { echo $i; usleep(1000000); }'), $input, $output, $stderr, 10, function ($output) use (&$outputStream) {
                $outputStream .= $output;
            });

        $this->assertEquals('12345', $output);
        $this->assertEquals('12345', $outputStream);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdOut(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add('fwrite(STDOUT, "success\n");'), $input, $output, $stderr, 3);

        $this->assertEquals("success\n", $output);
        $this->assertEquals('', $stderr);
        $this->assertEquals(0, $code);
    }

    public function testExecuteStdErr(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add('fwrite(STDERR, "error\n");'), $input, $output, $stderr, 3);

        $this->assertEquals('', $output);
        $this->assertEquals("error\n", $stderr);
        $this->assertEquals(0, $code);
    }

    public function testExecuteExitCode(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add("echo 'hello world'; exit(2);"), $input, $output, $stderr, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(2, $code);

        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add("echo 'hello world'; exit(100);"), $input, $output, $stderr, 10);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(100, $code);
    }

    public function testExecuteTimeout(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add("sleep(1); echo 'hello world'; exit(0);"), $input, $output, $stderr, 3);

        $this->assertEquals('hello world', $output);
        $this->assertEquals(0, $code);

        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add('-r')
            ->add("sleep(4); echo 'hello world'; exit(0);"), $input, $output, $stderr, 3);

        $this->assertEquals('', $output);
        $this->assertEquals(1, $code);
    }

    public function testLoop(): void
    {
        $file = __DIR__.'/../resources/loop.php';
        $input = '';
        $output = '';
        $stderr = '';
        $code = Console::execute((new Command(PHP_BINARY))
            ->add($file), $input, $output, $stderr, 30);

        $lines = explode("\n", $output);

        $this->assertGreaterThan(30, count($lines));
        $this->assertLessThan(50, count($lines));
        $this->assertEquals(1, $code);
    }

    public function testExecuteStringRemainsCompatible(): void
    {
        $output = '';
        $stderr = '';
        $input = '';
        $code = Console::execute('php -r "echo \'hello world\';"', $input, $output, $stderr, 10);

        $this->assertSame('hello world', $output);
        $this->assertSame(0, $code);
    }
}
