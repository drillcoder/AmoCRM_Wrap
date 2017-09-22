<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 17.09.17
 * Time: 20:28
 */

namespace AmoCRM;

/**
 * Class Note
 * @package AmoCRM
 */
class Note extends Base
{
    /**
     * @var int
     */
    private $type;
    /**
     * @var int
     */
    private $elementId;
    /**
     * @var int
     */
    private $elementType;
    /**
     * @var string
     */
    private $text;
    /**
     * @var bool
     */
    private $editable;
    /**
     * @var string
     */
    private $attachment;

    /**
     * @return bool
     */
    public function save()
    {
        $data = array(
            'element_id' => $this->elementId,
            'element_type' => $this->elementType,
            'note_type' => $this->type,
            'text' => $this->text,
        );
        return Base::save($data);
    }

    /**
     * @param \stdClass $stdClass
     * @return Lead
     */
    public function loadInStdClass($stdClass)
    {
        Base::loadInStdClass($stdClass);
        $this->type = (int)$stdClass->note_type;
        $this->elementId = (int)$stdClass->element_id;
        $this->elementType = (int)$stdClass->element_type;
        $this->text = $stdClass->text;
        $this->editable = $stdClass->editable == 'Y';
        $this->attachment = $stdClass->ATTACHEMENT;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param int $elementId
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
    }

    /**
     * @return int
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @return bool|string
     */
    public function getElementTypeName()
    {
        if (array_key_exists($this->elementType, Amo::$info->get('ElementType')))
            return Amo::$info->get('ElementType')[$this->elementType];
        return false;
    }

    /**
     * @param int $elementType
     */
    public function setElementType($elementType)
    {
        $this->elementType = $elementType;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return boolean
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }


}