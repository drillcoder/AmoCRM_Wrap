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
     * @var bool
     */
    private $isSystem;
    /**
     * @var Value[]
     */
    private $values;

    /**
     * CustomField constructor.
     * @param int $id
     * @param Value[] $values
     * @param string|null $name
     * @param bool $isSystem
     */
    public function __construct($id = 0, array $values = array(), $name = null, $isSystem = false)
    {
        $this->id = (int)$id;
        $this->name = $name;
        $this->isSystem = $isSystem;
        $this->values = $values;
    }

    /**
     * @param \stdClass $stdClass
     * @return CustomField
     */
    public static function loadInRaw($stdClass)
    {
        $values = array();
        foreach ($stdClass->values as $valueStdClass) {
            $values[] = Value::loadInStdClass($valueStdClass);
        }
        return new CustomField($stdClass->id, $values, $stdClass->name, $stdClass->is_system);
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
     * @return string[]
     */
    public function getArrayValues()
    {
        $values = array();
        foreach ($this->values as $value) {
            $values[] = $value->getValue();
        }
        return $values;
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
     * @return bool
     */
    public function getIsSystem()
    {
        return $this->isSystem;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}