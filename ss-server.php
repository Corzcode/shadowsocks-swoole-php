<?php
include 'lib/autoload.php';

ShadowSocks::getInstance(['port' => 8388,'passwd' => '123456','method' => 'aes-256-cfb'])->start();