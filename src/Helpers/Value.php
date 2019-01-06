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
 * @package DrillCoder\AmoCRM_Wrap\Helpers
 */
class Value
{
    /**
     * Адрес. Первая строка
     */
    const SUBTYPE_ADDRESS_LINE_1 = 1;

    /**
     * Адрес. Вторая строка
     */
    const SUBTYPE_ADDRESS_LINE_2 = 2;

    /**
     * Город
     */
    const SUBTYPE_CITY = 3;

    /**
     * Регион
     */
    const SUBTYPE_STATE = 4;

    /**
     * Индекс
     */
    const SUBTYPE_ZIP = 5;

    /**
     * Страна. Задается кодом (Например: RU, UA, KZ, и т.д.)
     */
    const SUBTYPE_COUNTRY = 6;

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
     *
     * @param string      $value
     * @param string|null $enum
     * @param int|null    $subtype
     */
    public function __construct($value, $enum = null, $subtype = null)
    {
        $this->value = $value;
        $this->enum = (int)$enum;
        $this->subtype = $subtype;
    }

    /**
     * @param $data
     *
     * @return Value
     */
    public static function loadInRaw($data)
    {
        if (!isset($data->enum)) {
            $data->enum = null;
        }
        if (!isset($data->subtype)) {
            $data->subtype = null;
        }
        return new Value($data->value, $data->enum, $data->subtype);
    }

    /**
     * @param string $value
     *
     * @return Value
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $enum
     *
     * @return Value
     */
    public function setEnum($enum)
    {
        $this->enum = $enum;

        return $this;
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
}