<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 07.04.2018
 * Time: 15:28
 */

namespace DrillCoder\AmoCRM_Wrap;


/**
 * Class AmoWrapException
 * @package AmoCRM
 */
class AmoWrapException extends \Exception
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}