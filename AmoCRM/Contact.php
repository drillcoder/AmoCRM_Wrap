<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 9:58
 */

namespace AmoCRM;

use AmoCRM\Helpers\CustomField;
use AmoCRM\Helpers\Value;

/**
 * Class Contact
 * @package AmoCRM
 */
class Contact extends Base
{
    /**
     * @var CustomField
     */
    private $phones;
    /**
     * @var CustomField
     */
    private $emails;

    /**
     * @var int[]
     */
    private $linkedLeadsId;

    /**
     * Contact constructor.
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->phones = new CustomField(Amo::$info->get('phoneFieldId'));
        $this->emails = new CustomField(Amo::$info->get('emailFieldId'));
        Base::__construct($id);
    }

    /**
     * @param \stdClass $stdClass
     */
    public function loadInStdClass($stdClass)
    {
        Base::loadInStdClass($stdClass);
        $this->linkedLeadsId = $stdClass->linked_leads_id;
        $this->customFields = array();
        if (is_array($stdClass->custom_fields)) {
            foreach ($stdClass->custom_fields as $custom_field) {
                $customField = CustomField::loadInStdClass($custom_field);
                if ($customField->getCode() == 'PHONE') {
                    $this->phones = $customField;
                } elseif ($customField->getCode() == 'EMAIL') {
                    $this->emails = $customField;
                } else {
                    $this->customFields[$customField->getId()] = $customField;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        $customFields = $this->customFields;
        $customFields[] = $this->phones;
        $customFields[] = $this->emails;
        $data = array(
            'linked_leads_id' => $this->linkedLeadsId,
        );
        return Base::saveBase($data, $customFields);
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        $customFields = $this->customFields;
        $customFields[] = $this->phones;
        $customFields[] = $this->emails;
        $data = array(
            'linked_leads_id' => $this->linkedLeadsId,
        );
        return Base::getRawBase($data, $customFields);
    }

    /**
     * @return string[]
     */
    public function getPhones()
    {
        $phones = array();
        foreach ($this->phones->getValues() as $value) {
            $phones[] = $value->getValue();
        }
        return $phones;
    }

    /**
     * @param string $phone
     * @param string $enum
     * @return bool
     */
    public function addPhone($phone, $enum = 'OTHER')
    {
        $enum = mb_strtoupper($enum);
        $idPhoneEnums = Amo::$info->get('idPhoneEnums');
        if (array_key_exists($enum, $idPhoneEnums)) {
            foreach ($this->phones->getValues() as $value) {
                if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone))
                    return true;
            }
            $this->phones->addValue(new Value($phone, $idPhoneEnums[$enum]));
            return true;
        }
        return false;
    }

    /**
     * @param string $phone
     * @return bool
     */
    public function delPhone($phone)
    {
        foreach ($this->phones->getValues() as $key => $value) {
            if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone)) {
                $this->phones->delValue($key);
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getEmails()
    {
        $emails = array();
        foreach ($this->emails->getValues() as $value) {
            $emails[] = $value->getValue();
        }
        return $emails;
    }

    /**
     * @param string $email
     * @param string $enum
     * @return bool
     */
    public function addEmail($email, $enum = 'OTHER')
    {
        $enum = mb_strtoupper($enum);
        $idEmailEnums = Amo::$info->get('idEmailEnums');
        if (array_key_exists($enum, $idEmailEnums)) {
            foreach ($this->emails->getValues() as $value) {
                if ($value->getValue() == $email)
                    return true;
            }
            $this->emails->addValue(new Value($email, $idEmailEnums[$enum]));
            return true;
        }
        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function delEmail($email)
    {
        foreach ($this->emails->getValues() as $key => $value) {
            if ($value->getValue() == $email) {
                $this->emails->delValue($key);
                return true;
            }
        }
        return false;
    }

    /**
     * @return int[]
     */
    public function getLinkedLeadsId()
    {
        return $this->linkedLeadsId;
    }

    /**
     * @param int $linkedLeadId
     */
    public function addLinkedLeadId($linkedLeadId)
    {
        $this->linkedLeadsId[] = $linkedLeadId;
    }

    /**
     * @param int $linkedLeadId
     * @return bool
     */
    public function delLinkedLeadId($linkedLeadId)
    {
        if ($key = array_search($linkedLeadId, $this->linkedLeadsId) !== false) {
            unset($this->linkedLeadsId[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        if (empty($this->id))
            $this->save();
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
        if (empty($this->id))
            $this->save();
        return parent::addTask($text, $responsibleUserIdOrName, $completeTill, $typeId);
    }
}