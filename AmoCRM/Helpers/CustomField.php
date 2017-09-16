<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 16:50
 */

namespace AmoCRM\Helpers;

/**
 * Class CustomField
 * @package AmoCRM\Helpers
 */
class CustomField
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $code;
    /**
     * @var Value[]
     */
    private $values;

    /**
     * CustomField constructor.
     * @param int $id
     * @param Value[] $values
     * @param string|null $name
     * @param string|null $code
     */
    public function __construct($id, array $values = array(), $name = null, $code = null)
    {
        $this->id = (int)$id;
        $this->name = $name;
        $this->code = $code;
        $this->values = $values;
    }

    /**
     * @param \stdClass $stdClass
     * @return CustomField
     */
    public static function loadInStdClass($stdClass)
    {
        $values = array();
        foreach ($stdClass->values as $valueStdClass) {
            $values[] = Value::loadInStdClass($valueStdClass);
        }
        return new CustomField($stdClass->id, $values, $stdClass->name, $stdClass->code);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Value[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param Value $value
     */
    public function addValue($value)
    {
        $this->values[] = $value;
    }

    /**
     * @param int $key
     */
    public function delValue($key)
    {
        unset($this->values[$key]);
    }

    /**
     *
     */
    public function delAllValues()
    {
        $this->values = array();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}