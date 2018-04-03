<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 11.09.17
 * Time: 16:07
 */

namespace AmoCRM;

/**
 * Class Lead
 * @package AmoCRM
 */
class Lead extends Base
{
    /**
     * @var int
     */
    protected $statusId;
    /**
     * @var int
     */
    protected $sale;
    /**
     * @var int
     */
    protected $pipelineId;
    /**
     * @var int
     */
    protected $mainContactId;

    /**
     * @return void
     */
    protected function setObjType()
    {
        $this->objType = array(
            'elementType' => 2,
            'info' => 'Lead',
            'url' => 'leads',
            'delete' => 'leads',
        );
    }

    /**
     * @return array
     */
    protected function getExtraRaw()
    {
        return array(
            'pipeline_id' => $this->pipelineId,
            'sale' => $this->sale,
            'status_id' => $this->statusId,
        );
    }

    /**
     * @param \stdClass $stdClass
     */
    public function loadInRaw($stdClass)
    {
        Base::loadInRaw($stdClass);
        $this->sale = (int)$stdClass->sale;
        $this->pipelineId = (int)$stdClass->pipeline->id;
        $this->statusId = (int)$stdClass->status_id;
        if (isset($stdClass->main_contact->id)) {
            $this->mainContactId = (int)$stdClass->main_contact->id;
        }
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            foreach ($this as $key => $item) {
                $this->$key = null;
            }
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * @param int $sale
     */
    public function setSale($sale)
    {
        $this->sale = $sale;
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        return Amo::$info->get('pipelines')[$this->pipelineId]['statuses'][$this->statusId]['name'];
    }

    /**
     * @param int|string $idOrNamePipeline
     * @param int|string $idOrNameStatus
     * @return bool
     */
    public function setStatus($idOrNamePipeline, $idOrNameStatus)
    {
        $this->pipelineId = Amo::$info->getPipelineIdFromIdOrName($idOrNamePipeline);
        if (empty($this->pipelineId)) {
            return false;
        }
        $this->statusId = Amo::$info->getStatusIdFromStatusIdOrNameAndPipelineIdOrName($this->pipelineId, $idOrNameStatus);
        if (empty($this->statusId)) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getPipelineId()
    {
        return $this->pipelineId;
    }

    /**
     * @return string
     */
    public function getPipelineName()
    {
        return Amo::$info->get('pipelines')[$this->pipelineId]['name'];
    }

    /**
     * @return int
     */
    public function getMainContactId()
    {
        return $this->mainContactId;
    }

    /**
     * @param int $contactId
     */
    public function setMainContactId($contactId)
    {
        $this->mainContactId = $contactId;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        if ($this->statusId == 142 || $this->statusId == 143)
            return true;
        return false;
    }

    /**
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        if (empty($this->id)) {
            $this->save();
        }
        return parent::addNote($text, $type);
    }

    /**
     * @param string $text
     * @param \DateTime|null $completeTill
     * @param int|string $typeId
     * @param int|string|null $responsibleUserIdOrName
     * @return bool
     */
    public function addTask($text, $responsibleUserIdOrName = null, $completeTill = null, $typeId = 3)
    {
        if (empty($this->id)) {
            $this->save();
        }
        return parent::addTask($text, $responsibleUserIdOrName, $completeTill, $typeId);
    }

    /**
     * @param string $pathToFile
     * @return bool
     */
    public function addFile($pathToFile)
    {
        if (empty($this->id)) {
            $this->save();
        }
        return parent::addFile($pathToFile);
    }
}