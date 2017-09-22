<?php

/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 9:40
 */


/**
 * Class ClassAutoloaderAmoCRM
 */
class ClassAutoloaderAmoCRM
{
    private static $_lastLoadedFilename;

    /**
     * ClassAutoloaderAmoCRM constructor.
     */
    public function __construct()
    {
        spl_autoload_register(array($this, '__autoloadAmoCRM'));
    }

    /**
     * @param string $className
     */
    private function __autoloadAmoCRM($className)
    {
        $pathParts = explode('\\', $className);
        self::$_lastLoadedFilename = dirname(__FILE__) . '/'. implode(DIRECTORY_SEPARATOR, $pathParts) . '.php';
        if (!empty(self::$_lastLoadedFilename)) {
            if (file_exists(self::$_lastLoadedFilename)) {
                require_once(self::$_lastLoadedFilename);
            }
        }
    }
}

$autoloader = new ClassAutoloaderAmoCRM();
