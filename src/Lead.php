<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 11.09.17
 * Time: 16:07
 */

namespace DrillCoder\AmoCRM_Wrap;


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
     * @throws AmoWrapException
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
     * @return int
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * @param int $sale
     * @return Lead
     */
    public function setSale($sale)
    {
        $this->sale = $sale;
        return $this;
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
     * @throws AmoWrapException
     */
    public function getStatusName()
    {
        $pipelines = AmoCRM::getInfo()->get('pipelines');
        return $pipelines[$this->pipelineId]['statuses'][$this->statusId]['name'];
    }

    /**
     * @param int|string $idOrNamePipeline
     * @param int|string $idOrNameStatus
     * @return Lead
     * @throws AmoWrapException
     */
    public function setPipelineAndStatus($idOrNamePipeline, $idOrNameStatus)
    {
        $this->pipelineId = AmoCRM::getInfo()->getPipelineIdFromIdOrName($idOrNamePipeline);
        if (empty($this->pipelineId)) {
            throw new AmoWrapException('Не удалось задать воронку');
        }
        $this->statusId = AmoCRM::getInfo()->getStatusIdFromStatusIdOrNameAndPipelineIdOrName($this->pipelineId, $idOrNameStatus);
        if (empty($this->statusId)) {
            throw new AmoWrapException('Не удалось задать статус');
        }
        return $this;
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
     * @throws AmoWrapException
     */
    public function getPipelineName()
    {
        $pipelines = AmoCRM::getInfo()->get('pipelines');
        return $pipelines[$this->pipelineId]['name'];
    }

    /**
     * @return int
     */
    public function getMainContactId()
    {
        return $this->mainContactId;
    }

    /**
     * @param Contact $contact
     * @return Lead
     */
    public function setMainContact($contact)
    {
        $this->mainContactId = $contact->getId();
        return $this;
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
}