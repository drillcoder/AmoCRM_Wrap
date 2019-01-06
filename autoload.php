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
        $prefixes = array(
            'DrillCoder\AmoCRM_Wrap\\' => array(
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ),
        );
        foreach ($prefixes as $prefix => $dirs) {
            $prefix_len = mb_strlen($prefix);
            if (mb_strpos($class, $prefix) !== 0) {
                continue;
            }
            $class = mb_substr($class, $prefix_len);
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
    }
);