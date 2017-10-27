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
    private $idPhoneEnums;
    /**
     * @var array
     */
    private $idEmailEnums;
    /**
     * @var array
     */
    private $usersIdAndName;
    /**
     * @var array
     */
    private $idContactCustomFields;
    /**
     * @var array
     */
    private $idContactCustomFieldsEnums;
    /**
     * @var array
     */
    private $idLeadCustomFields;
    /**
     * @var array
     */
    private $idLeadCustomFieldsEnums;
    /**
     * @var array
     */
    private $pipelines;
    /**
     * @var array
     */
    private $elementType;
    /**
     * @var array
     */
    private $taskTypes;

    /**
     * Info constructor.
     * @param \stdClass $info
     */
    public function __construct($info)
    {
//        echo '<pre>';
//        var_dump($info);
//        echo '</pre>';
//        die;
        $this->elementType = array(
            1 => 'contact',
            2 => 'lead',
            3 => 'company',
            4 => 'task',
        );
        $this->idContactCustomFieldsEnums = array();
        foreach ($info->custom_fields->contacts as $field) {
            $this->idContactCustomFields[$field->id] = $field->name;
            if ($field->code == 'PHONE') {
                $this->phoneFieldId = $field->id;
                $this->idPhoneEnums = array_flip(json_decode(json_encode($field->enums), true));
            }
            if ($field->code == 'EMAIL') {
                $this->emailFieldId = $field->id;
                $this->idEmailEnums = array_flip(json_decode(json_encode($field->enums), true));
            }
            if ($field->type_id == 5) {
                $this->idContactCustomFieldsEnums[$field->id] = json_decode(json_encode($field->enums), true);
            }
        }
        $this->idLeadCustomFieldsEnums = array();
        foreach ($info->custom_fields->leads as $field) {
            $this->idLeadCustomFields[$field->id] = $field->name;
            if ($field->type_id == 5) {
                $this->idLeadCustomFieldsEnums[$field->id] = json_decode(json_encode($field->enums), true);
            }
        }
        foreach ($info->users as $user) {
            $this->usersIdAndName[$user->id] = $user->name;
        }
        $this->pipelines = array();
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
}