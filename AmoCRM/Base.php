<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 12.09.17
 * Time: 11:55
 */

namespace AmoCRM;

use AmoCRM\Helpers\CustomField;
use AmoCRM\Helpers\Value;

/**
 * Class Base
 * @package AmoCRM
 */
abstract class Base
{
    /**
     * @var string[]
     */
    protected $objType = array(
        'elementType' => 0,
        'info' => null,
        'url' => null,
        'delete' => null,
    );
    /**
     * @var int
     */
    protected $amoId;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var Value[]
     */
    protected $phones = array();
    /**
     * @var Value[]
     */
    protected $emails = array();
    /**
     * @var int
     */
    protected $createdUserId;
    /**
     * @var \DateTime
     */
    protected $dateCreate;
    /**
     * @var \DateTime
     */
    protected $dateUpdate;
    /**
     * @var int
     */
    protected $userIdUpdate;
    /**
     * @var int
     */
    protected $responsibleUserId;
    /**
     * @var int
     */
    protected $companyId;
    /**
     * @var int[]
     */
    protected $leadsId = array();
    /**
     * @var int[]
     */
    protected $contactsId = array();
    /**
     * @var string[]
     */
    protected $tags = array();
    /**
     * @var CustomField[]
     */
    protected $customFields = array();
    /**
     * @var int|string
     */
    protected $type;
    /**
     * @var int
     */
    protected $elementId;
    /**
     * @var int
     */
    protected $elementType;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var array
     */
    protected $unlink;

    /**
     * Base constructor.
     * @param int|null $amoId
     */
    public function __construct($amoId = null)
    {
        $this->setObjType();
        if (Amo::$authorization) {
            if (!empty($amoId)) {
                $amoId = (int)$amoId;
                $this->loadInAmoId($amoId);
            }
        }
    }

    /**
     * @return void
     */
    protected abstract function setObjType();

    /**
     * @return bool
     */
    public abstract function save();

    /**
     * @return bool
     */
    public abstract function getRaw();

    /**
     * @param array $data
     * @return bool
     */
    public function saveBase($data = array())
    {
        if (Amo::$authorization) {
            if (empty($this->amoId)) {
                $method = 'add';
            } else {
                $method = 'update';
            }
            $requestData[$method] = array($this->getRawBase($data));
            $typeUrl = $this->objType['url'];
            $res = Amo::cUrl("api/v2/$typeUrl", $requestData);
            if ($method == 'update') {
                $idRes = $res->_embedded->items[0]->id;
                if ($idRes == $this->amoId)
                    return true;
            } elseif ($method == 'add') {
                if (isset($res->_embedded->items[0]->id) && $this->loadInAmoId($res->_embedded->items[0]->id)) {
                    return true;
                }
            }
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    protected function loadInAmoId($id)
    {
        if (!empty($id)) {
            $typeUrl = $this->objType['url'];
            $link = "api/v2/$typeUrl?id=$id";
            if ($this->objType['info'] == 'Note') {
                $type = null;
                switch ($this->elementType) {
                    case 1:
                        $type = 'contact';
                        break;
                    case 2:
                        $type = 'lead';
                        break;
                    case 3:
                        $type = 'company';
                        break;
                }
                $link .= "&type=$type";
            }
            $res = Amo::cUrl($link);
            if ($res) {
                $this->loadInRaw($res->_embedded->items[0]);
                return true;
            }
        }
        return false;
    }

    /**
     * @param \stdClass|array $stdClass
     */
    public function loadInRaw($stdClass)
    {
        $stdClass = json_decode(json_encode($stdClass));
        $this->amoId = (int)$stdClass->id;
        if (isset($stdClass->name))
            $this->name = $stdClass->name;
        $this->createdUserId = (int)$stdClass->created_by;
        $dateCreate = new \DateTime();
        $dateCreate->setTimestamp($stdClass->created_at);
        $this->dateCreate = $dateCreate;
        $dateUpdate = new \DateTime();
        $dateUpdate->setTimestamp($stdClass->updated_at);
        $this->dateUpdate = $dateUpdate;
        $this->responsibleUserId = (int)$stdClass->responsible_user_id;
        if (isset($stdClass->updated_by)) {
            $this->userIdUpdate = (int)$stdClass->updated_by;
        }
        if (isset($stdClass->company->id)) {
            $this->companyId = (int)$stdClass->company->id;
        }
        if (isset($stdClass->leads->id)) {
            $this->leadsId = $stdClass->leads->id;
        }
        if (isset($stdClass->contacts->id)) {
            $this->contactsId = $stdClass->contacts->id;
        }
        if (isset($stdClass->tags) && is_array($stdClass->tags)) {
            foreach ($stdClass->tags as $tag) {
                $this->tags[$tag->id] = $tag->name;
            }
        }
        if (isset($stdClass->custom_fields) && is_array($stdClass->custom_fields)) {
            foreach ($stdClass->custom_fields as $custom_field) {
                $customField = CustomField::loadInRaw($custom_field);
                if ($customField->getIsSystem() && $customField->getName() == 'Телефон') {
                    $this->phones = $customField->getValues();
                } elseif ($customField->getIsSystem() && $customField->getName() == 'Email') {
                    $this->emails = $customField->getValues();
                } else {
                    $this->customFields[$customField->getId()] = $customField;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $typeDelete = $this->objType['delete'];
        $post = array('ACTION' => 'DELETE', 'ID[]' => $this->amoId);
        $url = "ajax/$typeDelete/multiple/delete/";
        $res = Amo::cUrl($url, http_build_query($post), null, true);
        if ($res->status == 'success') {
            return true;
        }
        return false;
    }

    /**
     * @return int|null
     */
    public function getAmoId()
    {
        return $this->amoId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getResponsibleUserId()
    {
        return $this->responsibleUserId;
    }

    /**
     * @return string|null
     */
    public function getResponsibleUserName()
    {
        if (Amo::$authorization) {
            return Amo::$info->get('usersIdAndName')[$this->responsibleUserId];
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return null;
    }

    /**
     * @param int|string $responsibleUserIdOrName
     * @return bool
     */
    public function setResponsibleUser($responsibleUserIdOrName)
    {
        if (Amo::$authorization) {
            $this->responsibleUserId = Amo::$info->getUserIdFromIdOrName($responsibleUserIdOrName);
            if (!empty($this->responsibleUserId)) {
                return true;
            }
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return false;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @param int|null $companyId
     */
    public function setCompanyId($companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * @return \string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function delTag($tag)
    {
        $key = array_search($tag, $this->tags);
        if ($key !== false) {
            unset($this->tags[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @param string $type
     * @return int|false
     */
    public function addCustomField($name, $type)
    {
        $elementType = $this->objType['elementType'];
        $data['request']['fields']['add'] = array(
            array(
                "name" => $name,
                "type" => $type,
                "element_type" => $elementType,
                "origin" => 'AmoCRM Wrap'
            )
        );
        $res = Amo::cUrl('private/api/v2/json/fields/set', $data);
        if ($res)
            return $res->fields->add[0]->id;
        return false;
    }

    /**
     * @param $nameOrId
     * @return bool
     */
    public function delCustomField($nameOrId)
    {
        $id = CustomField::getIdFromNameOrId($this->objType['info'], $nameOrId);
        if (empty($id))
            return false;
        $data['request']['fields']['delete'] = array(
            array(
                "id" => $id,
                "origin" => 'AmoCRM Wrap'
            )
        );
        $res = Amo::cUrl('private/api/v2/json/fields/set', $data);
        if ($res)
            return $res->fields->delete[0]->id == $id;
        return false;
    }


    /**
     * @param string|int $nameOrId
     * @return string|null;
     */
    public function getCustomField($nameOrId)
    {
        $values = array();
        if (Amo::$authorization) {
            $id = CustomField::getIdFromNameOrId($this->objType['info'], $nameOrId);
            if (array_key_exists($id, $this->customFields)) {
                foreach ($this->customFields[$id]->getValues() as $value) {
                    $values[] = $value->getValue();
                }
                return implode('; ', $values);
            }
        } else {
            $customFields = $this->getCustomFields();
            if (isset($this->customFields[$nameOrId])) {
                foreach ($this->customFields[$nameOrId]->getValues() as $value) {
                    $values[] = $value->getValue();
                }
                return implode('; ', $values);
            } else {
                if (array_key_exists($nameOrId, $customFields)) {
                    return $customFields[$nameOrId];
                }
            }
        }
        return null;
    }

    /**
     * @return string[]|null;
     */
    public function getCustomFields()
    {
        $customFields = array();
        foreach ($this->customFields as $customField) {
            $id = $customField->getId();
            $values = array();
            foreach ($this->customFields[$id]->getValues() as $value) {
                $values[] = $value->getValue();
            }
            $customFields[$customField->getName()] = implode(', ', $values);
        }
        return $customFields;
    }

    /**
     * @param string|int $nameOrId
     * @param string $values
     * @return bool
     */
    public function setCustomField($nameOrId, $values)
    {
        if (Amo::$authorization) {
            $type = $this->objType['info'];
            $idCustomFields = Amo::$info->get("id{$type}CustomFields");
            if (array_key_exists($nameOrId, $idCustomFields)) {
                $id = $nameOrId;
                $name = $idCustomFields[$nameOrId];
            } elseif (in_array($nameOrId, $idCustomFields)) {
                $id = array_search($nameOrId, $idCustomFields);
                $name = $nameOrId;
            } else
                return false;
            if (!empty($id)) {
                if (empty($values)) {
                    if (array_key_exists($id, $this->customFields)) {
                        $this->customFields[$id]->delAllValues();
                    }
                } else {
                    $values = explode(';', $values);
                    $valueObj = array();
                    foreach ($values as $value) {
                        $value = trim($value);
                        if (isset(Amo::$info->get("id{$type}CustomFieldsEnums")[$id])) {
                            $enum = array_search($value, Amo::$info->get("id{$type}CustomFieldsEnums")[$id]);
                        } else {
                            $enum = null;
                        }
                        $valueObj[] = new Value($value, $enum);
                    }
                    $customFieldObj = new CustomField($id, $valueObj, $name);
                    $this->customFields[$customFieldObj->getId()] = $customFieldObj;
                }
            }
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return true;
    }

    /**
     * @return int
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param int $elementId
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
    }

    /**
     * @return int
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @param int $elementType
     */
    public function setElementType($elementType)
    {
        $this->elementType = $elementType;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreate()
    {
        return $this->dateCreate;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * @return int
     */
    public function getUserIdUpdate()
    {
        return $this->userIdUpdate;
    }

    /**
     * @return string|null
     */
    public function getUserNameUpdate()
    {
        if (Amo::$authorization) {
            return Amo::$info->get('usersIdAndName')[$this->userIdUpdate];
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return null;
    }

    /**
     * @return int
     */
    public function getCreatedUserId()
    {
        return $this->createdUserId;
    }

    /**
     * @return string|null
     */
    public function getCreatedUserName()
    {
        if (Amo::$authorization) {
            return Amo::$info->get('usersIdAndName')[$this->createdUserId];
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return null;
    }

    /**
     * @return int[]
     */
    public function getLeadsId()
    {
        return $this->leadsId;
    }

    /**
     * @param int $leadId
     */
    public function addLeadId($leadId)
    {
        $this->leadsId[] = $leadId;
    }

    /**
     * @param int $leadId
     * @return bool
     */
    public function delLeadId($leadId)
    {
        $delKeys = array_keys($this->leadsId, $leadId);
        if (!empty($delKeys)) {
            foreach ($delKeys as $delKey) {
                unset($this->leadsId[$delKey]);
            }
            $this->unlink['leads_id'][] = $leadId;
            return true;
        }
        return false;
    }

    /**
     * @return int[]
     */
    public function getContactsId()
    {
        $contactsId = array();
        foreach ($this->contactsId as $contactId) {
            $contactsId[] = $contactId;
        }
        return $contactsId;
    }

    /**
     * @param int $contactId
     */
    public function addContactId($contactId)
    {
        $this->contactsId[] = $contactId;
    }

    /**
     * @param int $contactId
     * @return bool
     */
    public function delContactId($contactId)
    {
        $delKeys = array_keys($this->contactsId, $contactId);
        if (!empty($delKeys)) {
            foreach ($delKeys as $delKey) {
                unset($this->contactsId[$delKey]);
            }
            $this->unlink['contacts_id'][] = $contactId;
            return true;
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getPhones()
    {
        $phones = array();
        foreach ($this->phones as $value) {
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
        if (!empty($this->phones)) {
            foreach ($this->phones as $value) {
                if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone)) {
                    return true;
                }
            }
        }
        if (Amo::$authorization) {
            $idPhoneEnums = Amo::$info->get('idPhoneEnums');
            if (array_key_exists($enum, $idPhoneEnums)) {
                $this->phones[] = new Value($phone, $idPhoneEnums[$enum]);
                return true;
            }
        } else {
            $this->phones[] = new Value($phone);
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
        if (!empty($this->phones)) {
            foreach ($this->phones as $key => $value) {
                if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone)) {
                    unset($this->phones[$key]);
                    return true;
                }
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
        foreach ($this->emails as $value) {
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
        $email = mb_strtolower($email);
        $enum = mb_strtoupper($enum);
        if (!empty($this->emails)) {
            foreach ($this->emails as $value) {
                if (mb_strtolower($value->getValue()) == $email)
                    return true;
            }
        }
        if (Amo::$authorization) {
            $idEmailEnums = Amo::$info->get('idEmailEnums');
            if (array_key_exists($enum, $idEmailEnums)) {
                $this->emails[] = new Value($email, $idEmailEnums[$enum]);
                return true;
            }
        } else {
            $this->emails[] = new Value($email);
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
        $email = mb_strtolower($email);
        if (!empty($this->emails)) {
            foreach ($this->emails as $key => $value) {
                if (mb_strtolower($value->getValue()) == $email) {
                    unset($this->emails[$key]);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getRawBase($data = array())
    {
        $data = array_merge($data, array(
            'name' => $this->name,
            'responsible_user_id' => $this->responsibleUserId,
            'company_id' => $this->companyId,
            'leads_id' => $this->leadsId,
            'contacts_id' => $this->contactsId,
            'tags' => implode(',', $this->tags),
            'unlink' => $this->unlink,
        ));
        if (empty($this->amoId)) {
            $data['created_by'] = 0;

        } else {
            $data['id'] = $this->amoId;
            $data['updated_at'] = date('U');
            $data['updated_by'] = 0;
        }
        $customFields = $this->customFields;
        if (!empty($this->phones)) {
            $idPhoneEnums = Amo::$info->get('idPhoneEnums');
            foreach ($this->phones as &$phone) {
                if ($phone->getEnum() === 0) {
                    $phone->setEnum($idPhoneEnums['OTHER']);
                }
            }
            $customFieldPhone = new CustomField(Amo::$info->get('phoneFieldId'), $this->phones);
            $customFields[] = $customFieldPhone;
        }
        if (!empty($this->emails)) {
            $idEmailEnums = Amo::$info->get('idEmailEnums');
            foreach ($this->emails as &$email) {
                if ($email->getEnum() === 0) {
                    $email->setEnum($idEmailEnums['OTHER']);
                }
            }
            $customFieldEmail = new CustomField(Amo::$info->get('emailFieldId'), $this->emails);
            $customFields[] = $customFieldEmail;
        }
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
                $data['custom_fields'][] = $customField;
            }
        }
        return $data;
    }

    /**
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        $note = new Note();
        $note->setText($text);
        $note->setType($type);
        $note->setElementId($this->amoId);
        $note->setElementType($this->objType['elementType']);
        return $note->save();
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
        if ($responsibleUserIdOrName === null) {
            $responsibleUserIdOrName = $this->responsibleUserId;
        }
        $task = new Task();
        $task->setText($text);
        $task->setCompleteTill($completeTill);
        if (Amo::$authorization) {
            $types = Amo::$info->get('taskTypes');
            if (in_array($typeId, $types)) {
                $typeId = array_search($typeId, $types);
            }
        } else {
            $typeId = 3;
        }
        $task->setType($typeId);
        $task->setResponsibleUser($responsibleUserIdOrName);
        $task->setElementId($this->amoId);
        $task->setElementType($this->objType['elementType']);
        $task->save();
        return true;
    }

    /**
     * @param string $pathToFile
     * @return bool
     */
    public function addFile($pathToFile)
    {
        if (is_file($pathToFile) && file_exists($pathToFile)) {
            $elementType = $this->objType['elementType'];
            if (class_exists('CURLFile')) {
                $cfile = new \CURLFile(realpath($pathToFile));
                $post = array(
                    'UserFile' => $cfile
                );
            } else {
                $post = array(
                    'UserFile' => "@" . $pathToFile
                );
            }
            $url = "/private/notes/edit2.php?ACTION=ADD_NOTE&ELEMENT_ID=" . $this->amoId . "&ELEMENT_TYPE=" . $elementType . "&fileapi" . str_replace(".", "", microtime(true));
            $res = Amo::cUrl($url, $post, null, true);
            if (isset($res->status) && $res->status == 'fail') {
                return false;
            }
            $post = array(
                'ACTION' => "ADD_NOTE",
                'DATE_CREATE' => time(),
                'ATTACH' => $res->note->params->link,
                'BODY' => $res->note->params->text,
                'ELEMENT_ID' => $this->amoId,
                'ELEMENT_TYPE' => $elementType,
            );
            $res = Amo::cUrl("private/notes/edit2.php", $post, null, true);
            if (isset($res->status) && $res->status == 'ok') {
                return true;
            }
        }
        return false;
    }
}