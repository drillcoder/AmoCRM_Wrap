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
    private $idUsers;
    /**
     * @var array
     */
    private $idContactCustomFields;
    /**
     * @var array
     */
    private $idLeadCustomFields;

    /**
     * Info constructor.
     * @param \stdClass $info
     */
    public function __construct($info)
    {
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
        }
        foreach ($info->custom_fields->leads as $field) {
            $this->idLeadCustomFields[$field->id] = $field->name;
        }
            foreach ($info->users as $user) {
            $this->idUsers[$user->id] = $user->name;
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