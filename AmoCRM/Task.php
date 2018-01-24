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
    protected $isComplete;
    /**
     * @var \DateTime
     */
    protected $completeTill;

    /**
     * @return void
     */
    protected function setObjType()
    {
        $this->objType = array(
            'elementType' => 4,
            'info' => null,
            'url' => 'tasks',
            'delete' => 'tasks',
        );
    }

    /**
     * @return array
     */
    protected function getExtraRaw()
    {
        return array(
            'complete_till_at' => $this->completeTill->format('U'),
            'is_completed' => $this->isComplete,
            'element_id' => $this->elementId,
            'element_type' => $this->elementType,
            'task_type' => $this->type,
            'text' => $this->text,
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
        $this->type = $stdClass->task_type;
        $this->elementId = (int)$stdClass->element_id;
        $this->elementType = (int)$stdClass->element_type;
        $this->text = $stdClass->text;
        $this->isComplete = $stdClass->is_completed;
        $completeTill = new \DateTime();
        $completeTill->setTimestamp($stdClass->complete_till_at);
        $this->completeTill = $completeTill;
    }

    /**
     * @return bool
     */
    public function isIsComplete()
    {
        return $this->isComplete;
    }

    /**
     * @param bool $isComplete
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