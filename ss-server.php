<?php
include 'lib/autoload.php';
$argv = getopt('c:d');
//define('DEBUG', true);
define('DAEMON', isset($argv['d']) ? true : false);
$confile = empty($argv['c']) ? __DIR__ . '/config.php' : getcwd() . '/' . $argv['c'];
if (! file_exists($confile)) {
    throw new Exception('config file is not exists');
}
ShadowSocks::getInstance(include $confile)->start(['daemonize' => DAEMON]);