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
        'request' => null,
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
     * @var CustomField
     */
    protected $phones;
    /**
     * @var CustomField
     */
    protected $emails;
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
    protected $lastModified;
    /**
     * @var int
     */
    protected $modifiedUserId;
    /**
     * @var int
     */
    protected $responsibleUserId;
    /**
     * @var int
     */
    protected $linkedCompanyId;
    /**
     * @var int[]
     */
    protected $linkedLeadsId;
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
     * Base constructor.
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->setObjType();
        if (Amo::$authorization) {
            $this->phones = new CustomField(Amo::$info->get('phoneFieldId'));
            $this->emails = new CustomField(Amo::$info->get('emailFieldId'));
            if (!empty($id)) {
                $id = (int)$id;
                $this->loadInAmoId($id);
            }
        } else {
            $this->phones = new CustomField();
            $this->emails = new CustomField();
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
            $typeRequest = $this->objType['request'];
            $requestData['request'][$typeRequest][$method] = array($this->getRawBase($data));
            $typeUrl = $this->objType['url'];
            $res = Amo::cUrl("private/api/v2/json/$typeUrl/set", $requestData);
            if ($method == 'update') {
                $idRes = $res->{$typeRequest}->update[0]->id;
                if ($idRes == $this->amoId)
                    return true;
            } elseif ($method == 'add') {
                if (isset($res->{$typeRequest}->add[0]->id) && $this->loadInAmoId($res->{$typeRequest}->add[0]->id))
                    return true;
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
            $elementType = $this->objType['elementType'];
            $typeInfo = $this->objType['info'];
            $typeRequest = $this->objType['request'];
            $link = "private/api/v2/json/$typeUrl/list?id=$id";
            if ($typeInfo == 'Note') {
                $link .= "&type=$elementType";
            }
            $res = Amo::cUrl($link);
            if ($res) {
                $this->loadInStdClass($res->{$typeRequest}[0]);
                return true;
            }
        }
        return false;
    }

    /**
     * @param \stdClass $stdClass
     */
    public function loadInStdClass($stdClass)
    {
        $this->amoId = (int)$stdClass->id;
        if (isset($stdClass->name))
            $this->name = $stdClass->name;
        $this->createdUserId = (int)$stdClass->created_user_id;
        $dateCreate = new \DateTime();
        $dateCreate->setTimestamp($stdClass->date_create);
        $this->dateCreate = $dateCreate;
        $lastModified = new \DateTime();
        $lastModified->setTimestamp($stdClass->last_modified);
        $this->lastModified = $lastModified;
        if (isset($stdClass->modified_user_id))
            $this->modifiedUserId = (int)$stdClass->modified_user_id;
        $this->responsibleUserId = (int)$stdClass->responsible_user_id;
        if (isset($stdClass->linked_company_id))
            $this->linkedCompanyId = (int)$stdClass->linked_company_id;
        if (isset($stdClass->linked_leads_id))
            $this->linkedLeadsId = $stdClass->linked_leads_id;
        if (isset($stdClass->name) && is_array($stdClass->tags)) {
            foreach ($stdClass->tags as $tag) {
                $this->tags[$tag->id] = $tag->name;
            }
        }
        if (isset($stdClass->custom_fields) && is_array($stdClass->custom_fields)) {
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
     * @return string
     */
    public function getResponsibleUserName()
    {
        return Amo::$info->get('usersIdAndName')[$this->responsibleUserId];
    }

    /**
     * @param int|string $responsibleUserIdOrName
     * @return bool
     */
    public function setResponsibleUser($responsibleUserIdOrName)
    {
        $this->responsibleUserId = Amo::$info->getUserIdFromIdOrName($responsibleUserIdOrName);
        if (empty($this->responsibleUserId)) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getLinkedCompanyId()
    {
        return $this->linkedCompanyId;
    }

    /**
     * @param int $linkedCompanyId
     */
    public function setLinkedCompanyId($linkedCompanyId)
    {
        $this->linkedCompanyId = $linkedCompanyId;
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
        $id = CustomField::getIdFromNameOrId($this->objType['info'], $nameOrId);
        $values = array();
        if (array_key_exists($id, $this->customFields)) {
            foreach ($this->customFields[$id]->getValues() as $value) {
                $values[] = $value->getValue();
            }
            return implode('; ', $values);
        }
        return null;
    }

    /**
     * @return string[]|null;
     */
    public function getCustomFields()
    {
        $type = $this->objType['info'];
        $idCustomFields = Amo::$info->get("id{$type}CustomFields");
        $customFields = array();
        foreach ($this->customFields as $customField) {
            $id = $customField->getId();
            $values = array();
            foreach ($this->customFields[$id]->getValues() as $value) {
                $values[] = $value->getValue();
            }
            $customFields[$idCustomFields[$id]] = implode(', ', $values);
        }
        return $customFields;
    }

    /**
     * @param string|int $customFieldNameOrId
     * @param string $values
     * @return bool
     */
    public function setCustomField($customFieldNameOrId, $values = null)
    {
        $type = $this->objType['info'];
        $idCustomFields = Amo::$info->get("id{$type}CustomFields");
        if (array_key_exists($customFieldNameOrId, $idCustomFields)) {
            $customFieldId = $customFieldNameOrId;
            $customFieldName = $idCustomFields[$customFieldNameOrId];
        } elseif (in_array($customFieldNameOrId, $idCustomFields)) {
            $customFieldId = array_search($customFieldNameOrId, $idCustomFields);
            $customFieldName = $customFieldNameOrId;
        } else
            return false;
        if (empty($values)) {
            if (array_key_exists($customFieldId, $this->customFields)) {
                $this->customFields[$customFieldId]->delAllValues();
            }
        } else {
            $values = explode(';', $values);
            $valueObj = array();
            foreach ($values as $value) {
                $value = trim($value);
                if (isset(Amo::$info->get("id{$type}CustomFieldsEnums")[$customFieldId])) {
                    $enum = array_search($value, Amo::$info->get("id{$type}CustomFieldsEnums")[$customFieldId]);
                } else {
                    $enum = null;
                }
                $valueObj[] = new Value($value, $enum);
            }
            $customFieldObj = new CustomField($customFieldId, $valueObj, $customFieldName);
            $this->customFields[$customFieldObj->getId()] = $customFieldObj;
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
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return int
     */
    public function getModifiedUserId()
    {
        return $this->modifiedUserId;
    }

    /**
     * @return string
     */
    public function getModifiedUserName()
    {
        return Amo::$info->get('usersIdAndName')[$this->modifiedUserId];
    }

    /**
     * @return int
     */
    public function getCreatedUserId()
    {
        return $this->createdUserId;
    }

    /**
     * @return string
     */
    public function getCreatedUserName()
    {
        return Amo::$info->get('usersIdAndName')[$this->createdUserId];
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
        if (!empty(Amo::$info)) {
            $idPhoneEnums = Amo::$info->get('idPhoneEnums');
            if (array_key_exists($enum, $idPhoneEnums)) {
                foreach ($this->phones->getValues() as $value) {
                    if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone))
                        return true;
                }
                $this->phones->addValue(new Value($phone, $idPhoneEnums[$enum]));
                return true;
            }
        } else {
            foreach ($this->phones->getValues() as $value) {
                if (Amo::clearPhone($value->getValue()) == Amo::clearPhone($phone))
                    return true;
            }
            $this->phones->addValue(new Value($phone));
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
        if (!empty(Amo::$info)) {
            $idEmailEnums = Amo::$info->get('idEmailEnums');
            if (array_key_exists($enum, $idEmailEnums)) {
                foreach ($this->emails->getValues() as $value) {
                    if ($value->getValue() == $email)
                        return true;
                }
                $this->emails->addValue(new Value($email, $idEmailEnums[$enum]));
                return true;
            }
        } else {
            foreach ($this->emails->getValues() as $value) {
                if ($value->getValue() == $email)
                    return true;
            }
            $this->emails->addValue(new Value($email));
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
     * @param array $data
     * @return array
     */
    public function getRawBase($data = array())
    {
        $data = array_merge($data, array(
            'name' => $this->name,
            'responsible_user_id' => $this->responsibleUserId,
            'linked_company_id' => $this->linkedCompanyId,
            'linked_leads_id' => $this->linkedLeadsId,
        ));
        if (empty($this->amoId)) {
            $data['created_user_id'] = 0;

        } else {
            $data['id'] = $this->amoId;
            $data['last_modified'] = date('U');
            $data['modified_user_id'] = 0;
        }
        if (is_array($this->tags))
            $data['tags'] = implode(',', $this->tags);
        $customFields = $this->customFields;
        $customFields[] = $this->phones;
        $customFields[] = $this->emails;
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
        $types = Amo::$info->get('taskTypes');
        if (in_array($typeId, $types))
            $typeId = array_search($typeId, $types);
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