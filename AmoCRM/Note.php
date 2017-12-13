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
     * @var bool
     */
    private $editable;
    /**
     * @var string
     */
    private $attachment;

    /**
     * @return void
     */
    protected function setObjType()
    {
        $this->objType = array(
            'elementType' => null,
            'info' => null,
            'url' => 'notes',
            'request' => 'notes',
            'delete' => 'notes',
        );
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return Base::getRawBase();
    }

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
        return Base::saveBase($data);
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