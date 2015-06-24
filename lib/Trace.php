<?php

/**
 * 输出控制
 * 
 * @author Corz
 * @namespace package_name
 */
class Trace
{

    /**
     * debug信息
     * @param string $info
     */
    public static function debug($info)
    {
        if (self::isDebug()) {
            echo var_export($info, true) . PHP_EOL;
        }
    }

    /**
     * debug信息
     * @param string $info
     */
    public static function info($info)
    {
        if (! DAEMON) {
            echo var_export($info, true) . PHP_EOL;
        }
    }

    /**
     * 
     */
    protected static function isDebug()
    {
        return defined('DEBUG') ? DEBUG : false;
    }
}