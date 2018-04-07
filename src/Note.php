<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 17.09.17
 * Time: 20:28
 */

namespace DrillCoder\AmoCRM_Wrap;


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
     * @param \stdClass $stdClass
     * @throws AmoWrapException
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
     * @return bool
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
     * @return Note
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * @param string $service
     * @return Note
     */
    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }
}