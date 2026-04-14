# Utopia Console

Small collection of helpers for working with PHP command line applications. The Console class focuses on everyday needs such as logging, prompting users for input, executing external commands, and building long-running daemons.

## Installation

Install using Composer:

```bash
composer require utopia-php/console
```

## Usage

```php
<?php
require_once __DIR__.'/vendor/autoload.php';

use Utopia\Console;
use Utopia\Command;

Console::success('Ready to work!');

$answer = Console::confirm('Continue? [y/N]');

if ($answer !== 'y') {
    Console::warning('Aborting...');
    Console::exit(1);
}

$output = '';
$stderr = '';
$command = (new Command(PHP_BINARY))
    ->add('-r')
    ->add('echo "Hello";');

$exitCode = Console::execute($command, '', $output, $stderr, 3);

Console::log("Command returned {$exitCode} with: {$output}");
```

### Log Messages

```php
Console::log('Plain log');        // stdout
Console::success('Green log');    // stdout
Console::info('Blue log');        // stdout
Console::warning('Yellow log');   // stderr
Console::error('Red log');        // stderr
```

### Execute Commands

`Console::execute()` returns the exit code and writes stdout and stderr into the referenced output variables. Pass a timeout (in seconds) to stop long-running processes and an optional progress callback to stream intermediate output. Prefer `Utopia\Command` or argv arrays when you want structured command building.

```php
$command = (new Command(PHP_BINARY))
    ->add('-r')
    ->add('fwrite(STDOUT, "success\\n");');

$output = '';
$input = '';
$stderr = '';
$exitCode = Console::execute($command, $input, $output, $stderr, 3);

echo $exitCode;  // 0
echo $output;    // "success\n"
```

### Create a Daemon

Use `Console::loop()` to build daemons without tight loops. The helper sleeps between iterations and periodically triggers garbage collection.

```php
<?php

use Utopia\Console;

Console::loop(function () {
    echo "Hello World\n";
}, 1); // 1 second
```

## System Requirements

Utopia Console requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## License

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
