<?php

/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 07.04.18
 * Time: 20:36
 */

spl_autoload_register(
/**
 * @param string $class
 */
    function ($class) {
        $ns = 'DrillCoder\AmoCRM_Wrap';
        $prefixes = array(
            "{$ns}\\" => array(
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ),
        );
        foreach ($prefixes as $prefix => $dirs) {
            $prefix_len = strlen($prefix);
            if (substr($class, 0, $prefix_len) !== $prefix) {
                continue;
            }
            $class = substr($class, $prefix_len);
            $part = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            foreach ($dirs as $dir) {
                $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
                $file = $dir . DIRECTORY_SEPARATOR . $part;
                if (is_readable($file)) {
                    require $file;
                    return;
                }
            }
        }
    });


