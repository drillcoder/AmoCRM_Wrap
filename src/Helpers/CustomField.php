<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 08.09.17
 * Time: 16:50
 */

namespace DrillCoder\AmoCRM_Wrap\Helpers;

use stdClass;

/**
 * Class CustomField
 * @package DrillCoder\AmoCRM_Wrap\Helpers
 */
class CustomField
{
    /**
     * Обыное текстовое поле
     */
    const TYPE_TEXT = 1;

    /**
     * Текстовое поле с возможностью передавать только цифры
     */
    const TYPE_NUMERIC = 2;

    /**
     * Поле обозначающее только наличие или отсутствие свойства (например: "да"/"нет")
     */
    const TYPE_CHECKBOX = 3;

    /**
     * Поле типа список с возможностью выбора одного элемента
     */
    const TYPE_SELECT = 4;

    /**
     * Поле типа список c возможностью выбора нескольких элементов списка
     */
    const TYPE_MULTISELECT = 5;

    /**
     * Поле типа дата возвращает и принимает значения в формате (Y-m-d H:i:s)
     */
    const TYPE_DATE = 6;

    /**
     * Обычное текстовое поле предназначенное для ввода URL адресов
     */
    const TYPE_URL = 7;

    /**
     * Поле textarea содержащее большое количество текста
     */
    const TYPE_TEXTAREA = 9;

    /**
     * Поле типа переключатель
     */
    const TYPE_RADIOBUTTON = 10;

    /**
     * Короткое поле адрес
     */
    const TYPE_STREETADDRESS = 11;

    /**
     * Поле адрес (в интерфейсе является набором из нескольких полей)
     */
    const TYPE_SMART_ADDRESS = 13;

    /**
     * Поле типа дата поиск по которому осуществляется без учета года, значения в формате (Y-m-d H:i:s)
     */
    const TYPE_BIRTHDAY = 14;

    /**
     * Рабочий телефон
     */
    const PHONE_WORK = 'WORK';

    /**
     * Рабочий прямой телефон
     */
    const PHONE_WORKDD = 'WORKDD';

    /**
     * Мобильный телефон
     */
    const PHONE_MOB = 'MOB';

    /**
     * Факс
     */
    const PHONE_FAX = 'FAX';

    /**
     * Домашний телефон
     */
    const PHONE_HOME = 'HOME';

    /**
     * Другой телефон
     */
    const PHONE_OTHER = 'OTHER';

    /**
     * Рабочая почта
     */
    const EMAIL_WORK = 'WORK';
    /**
     * Личная почта
     */
    const EMAIL_PRIV = 'PRIV';
    /**
     * Другая почта
     */
    const EMAIL_OTHER = 'OTHER';

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
     *
     * @param int         $id
     * @param Value[]     $values
     * @param string|null $name
     * @param bool        $isSystem
     */
    public function __construct($id = 0, array $values = array(), $name = null, $isSystem = false)
    {
        $this->id = (int)$id;
        $this->name = $name;
        $this->isSystem = $isSystem;
        $this->values = $values;
    }

    /**
     * @param stdClass $data
     *
     * @return CustomField
     */
    public static function loadInRaw($data)
    {
        $values = array();
        foreach ($data->values as $valueData) {
            $values[] = Value::loadInRaw($valueData);
        }

        return new CustomField($data->id, $values, $data->name, $data->is_system);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getIsSystem()
    {
        return $this->isSystem;
    }

    /**
     * @param Value[] $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return Value[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function getValuesInStr()
    {
        $values = array();
        foreach ($this->getValues() as $value) {
            $values[] = $value->getValue();
        }

        return implode('; ', $values);
    }

    /**
     * @return string[]
     */
    public function getValuesInArray()
    {
        $values = array();
        foreach ($this->values as $value) {
            $values[] = $value->getValue();
        }

        return $values;
    }
}