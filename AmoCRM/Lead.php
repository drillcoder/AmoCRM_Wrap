<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 11.09.17
 * Time: 16:07
 */

namespace AmoCRM;

use AmoCRM\Helpers\Info;
use AmoCRM\Helpers\CustomField;
use AmoCRM\Helpers\Value;

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
     * @param Info $info
     * @param \stdClass $stdClass
     * @return Lead
     */
    public static function loadInStdClass($info, $stdClass)
    {
        $lead = new Lead($info);
        $lead->id = (int)$stdClass->id;
        $lead->name = $stdClass->name;
        $lead->createdUserId = (int)$stdClass->created_user_id;
        $dateCreate = new \DateTime();
        $dateCreate->setTimestamp($stdClass->date_create);
        $lead->dateCreate = $dateCreate;
        $lead->modifiedUserId = (int)$stdClass->modified_user_id;
        $lastModified = new \DateTime();
        $lastModified->setTimestamp($stdClass->last_modified);
        $lead->lastModified = $lastModified;
        $lead->price = (int)$stdClass->price;
        $lead->responsibleUserId = (int)$stdClass->responsible_user_id;
        $lead->linkedCompanyId = (int)$stdClass->linked_company_id;
        $lead->pipelineId = (int)$stdClass->pipeline_id;
        $lead->statusId = (int)$stdClass->status_id;
        $lead->mainContactId = (int)$stdClass->main_contact_id;
        foreach ($stdClass->tags as $tag) {
            $lead->tags[$tag->id] = $tag->name;
        }
        $lead->customFields = array();
        foreach ($stdClass->custom_fields as $custom_field) {
            $customField = CustomField::loadInStdClass($custom_field);
            $lead->customFields[$customField->getId()] = $customField;
        }
        return $lead;
    }

    public function save()
    {
        $lead = array(
            'name' => $this->name,
            'main_contact_id' => $this->mainContactId,
            'pipeline_id' => $this->pipelineId,
            'price' => $this->price,
            'status_id' => $this->statusId,
            'responsible_user_id' => $this->responsibleUserId,
        );
        if (empty($this->id)) {
            $method = 'add';
            $lead['created_user_id'] = 0;

        } else {
            $method = 'update';
            $lead['id'] = $this->id;
            $lead['last_modified'] = date('U');
            $lead['modified_user_id'] = 0;
        }
        if (is_array($this->tags))
            $lead['tags'] = implode(',', $this->tags);
        if (!empty($this->customFields)) {
            /** @var CustomField $customFieldObj */
            foreach ($this->customFields as $customFieldObj) {
                $customField = array(
                    'id' => $customFieldObj->getId(),
                );
                $values = array();
                foreach ($customFieldObj->getValues() as $valueObj) {
                    $value = array(
                        'enum' => $valueObj->getEnum(),
                        'value' => $valueObj->getValue(),
                        'subtype' => $valueObj->getSubtype(),
                    );
                    $values[] = $value;
                }
                $customField['values'] = $values;
                $lead['custom_fields'][] = $customField;
            }
        }
        $leads['request']['leads'][$method] = array(
            $lead
        );
        return array('type' => 'leads', 'data' => $leads);
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
     * @param string|int $customFieldNameOrId
     * @param string $value
     * @return bool
     */
    public function addCustomField($customFieldNameOrId, $value)
    {
        $valueObj = new Value($value);
        if (array_key_exists($customFieldNameOrId, $this->info->get('idLeadCustomFields'))) {
            $customFieldObj = new CustomField($customFieldNameOrId, array($valueObj), $this->info->get('idLeadCustomFields')[$customFieldNameOrId]);
        } elseif (in_array($customFieldNameOrId, $this->info->get('idLeadCustomFields'))) {
            $customFieldObj = new CustomField(array_search($customFieldNameOrId, $this->info->get('idLeadCustomFields')), array($valueObj), $customFieldNameOrId);
        } else
            return false;
        $this->customFields[$customFieldObj->getId()] = $customFieldObj;
        return true;
    }

    /**
     * @param string|int $customFieldNameOrId
     * @return bool
     */
    public function delCustomField($customFieldNameOrId)
    {
        if (array_key_exists($customFieldNameOrId, $this->info->get('idLeadCustomFields'))) {
            $customFieldId = $customFieldNameOrId;
        } elseif (in_array($customFieldNameOrId, $this->info->get('idLeadCustomFields'))) {
            $customFieldId = array_search($customFieldNameOrId, $this->info->get('idLeadCustomFields'));
        } else
            return false;
        if (array_key_exists($customFieldId, $this->customFields))
            $this->customFields[$customFieldId]->delAllValues();
        return true;
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @param int $statusId
     */
    public function setStatusId($statusId) //TODO Сделать возможность писать текстом а не id
    {
        $this->statusId = $statusId;
    }

    /**
     * @return int
     */
    public function getPipelineId()
    {
        return $this->pipelineId;
    }

    //TODO Сделать возможность получения статуса и воронки в текстовом виде

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