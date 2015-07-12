<?php
/**
 * @author Leon Verschuren <lverschuren@hotmail.com>
 */

require __DIR__.'/vendor/autoload.php';

use leonverschuren\Console\Command\ExifCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ExifCommand());
$application->run();
