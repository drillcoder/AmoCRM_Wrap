<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 11.09.17
 * Time: 16:07
 */

namespace AmoCRM;

use AmoCRM\Helpers\CustomField;

/**
 * Class Lead
 * @package AmoCRM
 */
class Lead extends Base
{
    /**
     * @var int
     */
    private $statusId;
    /**
     * @var int
     */
    private $price;
    /**
     * @var int
     */
    private $pipelineId;
    /**
     * @var int
     */
    private $mainContactId;

    /**
     * @param \stdClass $stdClass
     * @return Lead
     */
    public function loadInStdClass($stdClass)
    {
        Base::loadInStdClass($stdClass);
        $this->price = (int)$stdClass->price;
        $this->pipelineId = (int)$stdClass->pipeline_id;
        $this->statusId = (int)$stdClass->status_id;
        $this->mainContactId = (int)$stdClass->main_contact_id;
        $this->customFields = array();
        if (is_array($stdClass->tags)) {
            foreach ($stdClass->custom_fields as $custom_field) {
                $customField = CustomField::loadInStdClass($custom_field);
                $this->customFields[$customField->getId()] = $customField;
            }
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        $customFields = $this->customFields;
        $data = array(
            'main_contact_id' => $this->mainContactId,
            'pipeline_id' => $this->pipelineId,
            'price' => $this->price,
            'status_id' => $this->statusId,
        );
        return Base::save($data, $customFields);
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
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
     * @param int|string $idOrName
     * @return bool
     */
    public function setStatus($idOrName)
    {
        if (array_key_exists($idOrName, Amo::$info->get('pipelines')[$this->pipelineId]['statuses'])) {
            $id = $idOrName;
        } else {
            $statuses = Amo::$info->get('pipelines')[$this->pipelineId]['statuses'];
            $statusesArray = array();
            foreach ($statuses as $statusId => $status) {
                $statusesArray[$statusId] = $status['name'];
            }
            if (in_array($idOrName, $statusesArray)) {
                $id = array_search($idOrName, $statusesArray);
            } else {
                return false;
            }
        }
        $this->statusId = $id;
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
     * @param int|string $idOrNamePipeline
     * @param int|string $idOrNameStatus
     * @return bool
     */
    public function setPipeline($idOrNamePipeline, $idOrNameStatus)
    {
        if (array_key_exists($idOrNamePipeline, Amo::$info->get('pipelines'))) {
            $idPipeline = $idOrNamePipeline;
        } else {
            $pipelines = Amo::$info->get('pipelines');
            $pipelinesArray = array();
            foreach ($pipelines as $pipelineId => $pipeline) {
                $pipelinesArray[$pipelineId] = $pipeline['name'];
            }
            if (in_array($idOrNamePipeline, $pipelinesArray)) {
                $idPipeline = array_search($idOrNamePipeline, $pipelinesArray);
            } else {
                return false;
            }
        }
        $this->pipelineId = $idPipeline;
        return $this->setStatus($idOrNameStatus);
    }

    /**
     * @return int
     */
    public function getMainContactId()
    {
        return $this->mainContactId;
    }

    /**
     * @param int $mainContactId
     */
    public function setMainContactId($mainContactId)
    {
        $this->mainContactId = $mainContactId;
    }

}