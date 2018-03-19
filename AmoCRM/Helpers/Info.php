<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 12.09.17
 * Time: 10:47
 */

namespace AmoCRM\Helpers;

/**
 * Class Info
 * @package AmoCRM\Helpers
 */
class Info
{
    /**
     * @var int
     */
    private $phoneFieldId;
    /**
     * @var int
     */
    private $emailFieldId;
    /**
     * @var array
     */
    private $idPhoneEnums = array();
    /**
     * @var array
     */
    private $idEmailEnums = array();
    /**
     * @var array
     */
    private $usersIdAndName = array();
    /**
     * @var array
     */
    private $idContactCustomFields = array();
    /**
     * @var array
     */
    private $idContactCustomFieldsEnums = array();
    /**
     * @var array
     */
    private $idLeadCustomFields = array();
    /**
     * @var array
     */
    private $idLeadCustomFieldsEnums = array();
    /**
     * @var array
     */
    private $idCompanyCustomFields = array();
    /**
     * @var array
     */
    private $idCompanyCustomFieldsEnums = array();
    /**
     * @var array
     */
    private $pipelines = array();
    /**
     * @var array
     */
    private $taskTypes = array();

    /**
     * Info constructor.
     * @param \stdClass $info
     */
    public function __construct($info)
    {
        foreach ($info->users as $user) {
            $this->usersIdAndName[$user->id] = $user->name;
        }
        foreach ($info->custom_fields->contacts as $field) {
            $this->idContactCustomFields[$field->id] = $field->name;
            if ($field->name == 'Телефон' && $field->is_system) {
                $this->phoneFieldId = $field->id;
                $this->idPhoneEnums = array_flip(json_decode(json_encode($field->enums), true));
            }
            if ($field->name == 'Email' && $field->is_system) {
                $this->emailFieldId = $field->id;
                $this->idEmailEnums = array_flip(json_decode(json_encode($field->enums), true));
            }
            if ($field->field_type == 5) {
                $this->idContactCustomFieldsEnums[$field->id] = json_decode(json_encode($field->enums), true);
            }
        }
        foreach ($info->custom_fields->leads as $field) {
            $this->idLeadCustomFields[$field->id] = $field->name;
            if ($field->field_type == 4) {
                $this->idLeadCustomFieldsEnums[$field->id] = json_decode(json_encode($field->enums), true);
            }
        }
        foreach ($info->custom_fields->companies as $field) {
            $this->idCompanyCustomFields[$field->id] = $field->name;
            if ($field->field_type == 5) {
                $this->idCompanyCustomFieldsEnums[$field->id] = json_decode(json_encode($field->enums), true);
            }
        }
        foreach ($info->pipelines as $pipeline) {
            $this->pipelines[$pipeline->id]['name'] = $pipeline->name;
            $this->pipelines[$pipeline->id]['statuses'] = array();
            foreach ($pipeline->statuses as $status) {
                $this->pipelines[$pipeline->id]['statuses'][$status->id] = array(
                    'name' => $status->name,
                    'color' => $status->color
                );
            }
        }
        foreach ($info->task_types as $type) {
            $this->taskTypes[$type->id] = $type->name;
        }
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function get($prop)
    {
        return $this->$prop;
    }

    /**
     * @param int|string $pipelineIdOrName
     * @return int|null
     */
    public function getPipelineIdFromIdOrName($pipelineIdOrName)
    {
        if (array_key_exists($pipelineIdOrName, $this->pipelines)) {
            return $pipelineIdOrName;
        } else {
            foreach ($this->pipelines as $id => $pipeline) {
                if (mb_strtolower($pipeline['name']) == mb_strtolower($pipelineIdOrName)) {
                    return $id;
                }
            }
        }
        return null;
    }

    /**
     * @param int $idOrNamePipeline
     * @param int|string $idOrNameStatus
     * @return int|null
     */
    public function getStatusIdFromStatusIdOrNameAndPipelineIdOrName($idOrNamePipeline, $idOrNameStatus)
    {
        $pipelineId = $this->getPipelineIdFromIdOrName($idOrNamePipeline);
        if (array_key_exists($idOrNameStatus, $this->pipelines[$pipelineId]['statuses'])) {
            return $idOrNameStatus;
        } else {
            foreach ($this->pipelines[$pipelineId]['statuses'] as $id => $pipeline) {
                if (mb_strtolower($pipeline['name']) == mb_strtolower($idOrNameStatus)) {
                    return $id;
                }
            }
        }
        return null;
    }

    /**
     * @param int|string $userIdOrName
     * @return int|null
     */
    public function getUserIdFromIdOrName($userIdOrName)
    {
        if (array_key_exists($userIdOrName, $this->usersIdAndName)) {
            return $userIdOrName;
        } else {
            foreach ($this->usersIdAndName as $id => $name) {
                if (stripos($name, $userIdOrName) !== false) {
                    return $id;
                }
            }
        }
        return null;
    }
}