<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 10.09.17
 * Time: 21:20
 */

namespace DrillCoder\AmoCRM_Wrap\Helpers;

/**
 * Class Value
 * @package AmoCRM\Helpers
 */
class Value
{
    /**
     * @var string
     */
    private $value;
    /**
     * @var string|null
     */
    private $enum;
    /**
     * @var int|null
     */
    private $subtype;

    /**
     * Value constructor.
     * @param string $value
     * @param string|null $enum
     * @param int|null $subtype
     */
    public function __construct($value, $enum = null, $subtype = null)
    {
        $this->enum = (int)$enum;
        $this->value = $value;
        switch ($subtype) {
            case 1:
                $subtype = 'address_line_1';
                break;
            case 2:
                $subtype = 'address_line_2';
                break;
            case 3:
                $subtype = 'city';
                break;
            case 4:
                $subtype = 'state';
                break;
            case 5:
                $subtype = 'zip';
                break;
            case 6:
                $subtype = 'country';
                break;
        }
        $this->subtype = $subtype;
    }

    /**
     * @param $stdClass
     * @return Value
     */
    public static function loadInStdClass($stdClass)
    {
        if (!isset($stdClass->enum))
            $stdClass->enum = null;
        if (!isset($stdClass->subtype))
            $stdClass->subtype = null;
        return new Value($stdClass->value, $stdClass->enum, $stdClass->subtype);
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|string
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @return int|null
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param string $enum
     * @return Value
     */
    public function setEnum($enum)
    {
        $this->enum = $enum;
        return $this;
    }

    /**
     * @param string $value
     * @return Value
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}