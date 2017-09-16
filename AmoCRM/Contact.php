<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 9:58
 */

namespace AmoCRM;

use AmoCRM\Helpers\CustomField;
use AmoCRM\Helpers\Info;
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
     * @param Info $info
     */
    public function __construct($info)
    {
        Base::__construct($info);
        $this->phones = new CustomField($info->get('phoneFieldId'));
        $this->emails = new CustomField($info->get('emailFieldId'));
    }


    /**
     * @param Info $info
     * @param \stdClass $stdClass
     * @return Contact
     */
    public static function loadInStdClass($info, $stdClass)
    {
        $contact = new Contact($info);
        $contact->id = (int)$stdClass->id;
        $contact->name = $stdClass->name;
        $contact->createdUserId = (int)$stdClass->created_user_id;
        $dateCreate = new \DateTime();
        $dateCreate->setTimestamp($stdClass->date_create);
        $contact->dateCreate = $dateCreate;
        $contact->modifiedUserId = (int)$stdClass->modified_user_id;
        $lastModified = new \DateTime();
        $lastModified->setTimestamp($stdClass->last_modified);
        $contact->lastModified = $lastModified;
        $contact->responsibleUserId = (int)$stdClass->responsible_user_id;
        $contact->linkedCompanyId = (int)$stdClass->linked_company_id;
        $contact->linkedLeadsId = (int)$stdClass->linked_leads_id;
        foreach ($stdClass->tags as $tag) {
            $contact->tags[$tag->id] = $tag->name;
        }
        $contact->customFields = array();
        foreach ($stdClass->custom_fields as $custom_field) {
            $customField = CustomField::loadInStdClass($custom_field);
            if ($customField->getCode() == 'PHONE') {
                $contact->phones = $customField;
            } elseif ($customField->getCode() == 'EMAIL') {
                $contact->emails = $customField;
            } else {
                $contact->customFields[$customField->getId()] = $customField;
            }
        }
        return $contact;
    }

    /**
     * @return array
     */
    public function save()
    {
        $contact = array(
            'name' => $this->name,
            'linked_leads_id' => $this->linkedLeadsId,
            'linked_company_id' => $this->linkedCompanyId,
            'responsible_user_id' => $this->responsibleUserId,
        );
        if (empty($this->id)) {
            $method = 'add';
            $contact['created_user_id'] = 0;

        } else {
            $method = 'update';
            $contact['id'] = $this->id;
            $contact['last_modified'] = date('U');
            $contact['modified_user_id'] = 0;
        }
        if (is_array($this->tags))
            $contact['tags'] = implode(',', $this->tags);
        $customFields = $this->customFields;
        $customFields[] = $this->phones;
        $customFields[] = $this->emails;
        if (!empty($customFields)) {
            /** @var CustomField $customFieldObj */
            foreach ($customFields as $customFieldObj) {
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
                $contact['custom_fields'][] = $customField;
            }
        }
        $contacts['request']['contacts'][$method] = array(
            $contact
        );
        return array('type' => 'contacts', 'data' => $contacts);
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
        $idPhoneEnums = $this->info->get('idPhoneEnums');
        if (array_key_exists($enum, $idPhoneEnums)) {
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
        $idEmailEnums = $this->info->get('idEmailEnums');
        if (array_key_exists($enum, $idEmailEnums)) {
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
     * @param string|int $customFieldNameOrId
     * @param string $value
     * @return bool
     */
    public function addCustomField($customFieldNameOrId, $value)
    {
        $valueObj = new Value($value);
        if (array_key_exists($customFieldNameOrId, $this->info->get('idContactCustomFields'))) {
            $customFieldObj = new CustomField($customFieldNameOrId, array($valueObj), $this->info->get('idContactCustomFields')[$customFieldNameOrId]);
        } elseif (in_array($customFieldNameOrId, $this->info->get('idContactCustomFields'))) {
            $customFieldObj = new CustomField(array_search($customFieldNameOrId, $this->info->get('idContactCustomFields')), array($valueObj), $customFieldNameOrId);
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
        if (array_key_exists($customFieldNameOrId, $this->info->get('idContactCustomFields'))) {
            $customFieldId = $customFieldNameOrId;
        } elseif (in_array($customFieldNameOrId, $this->info->get('idContactCustomFields'))) {
            $customFieldId = array_search($customFieldNameOrId, $this->info->get('idContactCustomFields'));
        } else
            return false;
        if (array_key_exists($customFieldId, $this->customFields))
            $this->customFields[$customFieldId]->delAllValues();
        return true;
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
}