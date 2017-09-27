<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 26.09.17
 * Time: 14:31
 */

namespace AmoCRM;

/**
 * Class Task
 * @package AmoCRM
 */
class Task extends Base
{
    /**
     * @var bool
     */
    private $isComplete;
    /**
     * @var \DateTime
     */
    private $completeTill;

    /**
     * @return bool
     */
    public function save()
    {
        $data = array(
            'element_id' => $this->elementId,
            'element_type' => $this->elementType,
            'task_type' => $this->type,
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
        $this->type = $stdClass->task_type;
        $this->elementId = (int)$stdClass->element_id;
        $this->elementType = (int)$stdClass->element_type;
        $this->text = $stdClass->text;
        $this->isComplete = $stdClass->status == 1;
        $completeTill = new \DateTime();
        $completeTill->setTimestamp($stdClass->complete_till);
        $this->completeTill = $completeTill;
    }

    /**
     * @return boolean
     */
    public function isIsComplete()
    {
        return $this->isComplete;
    }

    /**
     * @param boolean $isComplete
     */
    public function setIsComplete($isComplete)
    {
        $this->isComplete = $isComplete;
    }

    /**
     * @return \DateTime
     */
    public function getCompleteTill()
    {
        return $this->completeTill;
    }

    /**
     * @param \DateTime $completeTill
     */
    public function setCompleteTill($completeTill)
    {
        $this->completeTill = $completeTill;
    }
}