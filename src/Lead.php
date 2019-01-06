<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 11.09.17
 * Time: 16:07
 */

namespace DrillCoder\AmoCRM_Wrap;

use stdClass;

/**
 * Class Lead
 * @package DrillCoder\AmoCRM_Wrap
 */
class Lead extends BaseEntity
{
    /**
     * @var int
     */
    private $statusId;

    /**
     * @var int
     */
    private $sale;

    /**
     * @var int
     */
    private $pipelineId;

    /**
     * @var int
     */
    private $mainContactId;

    /**
     * @param stdClass $data
     *
     * @return Lead
     *
     * @throws AmoWrapException
     */
    public function loadInRaw($data)
    {
        BaseEntity::loadInRaw($data);
        $this->sale = (int)$data->sale;
        $this->pipelineId = (int)$data->pipeline->id;
        $this->statusId = (int)$data->status_id;
        if (isset($data->main_contact->id)) {
            $this->mainContactId = (int)$data->main_contact->id;
        }

        return $this;
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
     *
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
     */
    public function getStatusName()
    {
        $statuses = AmoCRM::getStatusesName($this->pipelineId);

        return $statuses[$this->statusId];
    }

    /**
     * @param int|string $pipelineIdOrName
     *
     * @return Lead
     *
     * @throws AmoWrapException
     */
    public function setPipeline($pipelineIdOrName)
    {
        $this->pipelineId = AmoCRM::searchPipelineId($pipelineIdOrName);

        return $this;
    }

    /**
     * @param int|string $statusIdOrName
     * @param int|string $pipelineIdOrName
     *
     * @return Lead
     *
     * @throws AmoWrapException
     */
    public function setStatus($statusIdOrName, $pipelineIdOrName = null)
    {
        $pipelineId = $pipelineIdOrName !== null ? AmoCRM::searchPipelineId($pipelineIdOrName) : $this->pipelineId;
        $this->statusId = AmoCRM::searchStatusId($pipelineId, $statusIdOrName);

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
     */
    public function getPipelineName()
    {
        $pipelines = AmoCRM::getPipelinesName();

        return $pipelines[$this->pipelineId];
    }

    /**
     * @return int
     */
    public function getMainContactId()
    {
        return $this->mainContactId;
    }

    /**
     * @return Contact
     *
     * @throws AmoWrapException
     */
    public function getMainContact()
    {
        return new Contact($this->mainContactId);
    }

    /**
     * @param Contact|string|int $contact
     *
     * @return Lead
     */
    public function setMainContact($contact)
    {
        $id = $contact instanceof Contact ? $contact->getId() : Base::onlyNumbers($contact);
        $this->mainContactId = $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return $this->statusId === 142 || $this->statusId === 143;
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
}