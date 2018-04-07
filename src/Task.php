<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 26.09.17
 * Time: 14:31
 */

namespace DrillCoder\AmoCRM_Wrap;


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
     * Task constructor.
     * @param null $amoId
     * @throws AmoWrapException
     */
    public function __construct($amoId = null)
    {
        parent::__construct($amoId);
        $this->completeTill = new \DateTime();
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
     * @param \stdClass $stdClass
     * @throws AmoWrapException
     */
    public function loadInRaw($stdClass)
    {
        Base::loadInRaw($stdClass);
        $this->type = $stdClass->task_type;
        $this->elementId = (int)$stdClass->element_id;
        $this->elementType = (int)$stdClass->element_type;
        $this->text = $stdClass->text;
        $this->isComplete = $stdClass->is_completed;
        $this->completeTill->setTimestamp($stdClass->complete_till_at);
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
     * @return Task
     */
    public function setIsComplete($isComplete)
    {
        $this->isComplete = $isComplete;
        return $this;
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
     * @return Task
     */
    public function setCompleteTill($completeTill)
    {
        $this->completeTill = $completeTill;
        return $this;
    }
}