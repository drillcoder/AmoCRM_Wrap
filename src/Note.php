<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 17.09.17
 * Time: 20:28
 */

namespace DrillCoder\AmoCRM_Wrap;

use stdClass;

/**
 * Class Note
 * @package DrillCoder\AmoCRM_Wrap
 */
class Note extends BaseEntity
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
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $phone;

    /**
     * @param stdClass $data
     *
     * @return Note
     *
     * @throws AmoWrapException
     */
    public function loadInRaw($data)
    {
        BaseEntity::loadInRaw($data);
        $this->editable = $data->is_editable;
        $this->attachment = $data->attachment;

        return $this;
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
     *
     * @return Note
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @param string $service
     *
     * @return Note
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @param $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @param string $userId
     *
     * @return $this
     */
    public function setCreatedUser($userId)
    {
        $this->createdUserId = $userId;

        return $this;
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
            'created_by' => $this->createdUserId,
            'params' => array(
                'text' => $this->text,
                'service' => $this->service,
                'phone' => $this->phone,
            ),
        );
    }
}