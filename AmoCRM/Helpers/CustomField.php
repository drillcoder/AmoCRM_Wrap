<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 16:50
 */

namespace AmoCRM\Helpers;
use AmoCRM\Amo;

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
        if (!isset($stdClass->code))
            $stdClass->code = null;
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
     * @param string $type
     * @param string|int $nameOrId
     * @return int|null
     */
    public static function getIdFromNameOrId($type, $nameOrId)
    {
        $idsCustomFields = Amo::$info->get("id{$type}CustomFields");
        if (array_key_exists($nameOrId, $idsCustomFields)) {
            $id = $nameOrId;
        } elseif (in_array($nameOrId, $idsCustomFields)) {
            $id = array_search($nameOrId, $idsCustomFields);
        } else
            return null;
        return $id;
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