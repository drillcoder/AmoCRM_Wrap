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
    protected $editable;
    /**
     * @var string
     */
    protected $attachment;
    /**
     * @var string
     */
    protected $params;

    /**
     * @var string
     */
    protected $service;

    /**
     * @return void
     */
    protected function setObjType()
    {
        $this->objType = array(
            'elementType' => null,
            'info' => 'Note',
            'url' => 'notes',
            'delete' => 'notes',
        );
    }

    /**
     * @return array
     */
    protected function getExtraRaw()
    {
        return array(
            'element_id' => $this->elementId,
            'element_type' => $this->elementType,
            'note_type' => $this->type,
            'text' => $this->text,
            'params' => array(
                'text' => $this->text,
                'service' => $this->service,
            ),
        );
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return Base::getRawBase($this->getExtraRaw());
    }

    /**
     * @return bool
     */
    public function save()
    {

        return Base::saveBase($this->getExtraRaw());
    }

    /**
     * @param \stdClass $stdClass
     */
    public function loadInRaw($stdClass)
    {
        Base::loadInRaw($stdClass);
        $this->type = (int)$stdClass->note_type;
        $this->elementId = (int)$stdClass->element_id;
        $this->elementType = (int)$stdClass->element_type;
        $this->text = $stdClass->text;
        $this->editable = $stdClass->is_editable;
        $this->attachment = $stdClass->attachment;
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

    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }
}