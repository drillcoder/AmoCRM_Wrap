<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 12.09.17
 * Time: 11:55
 */

namespace DrillCoder\AmoCRM_Wrap;

use DrillCoder\AmoCRM_Wrap\Helpers\Config;
use DrillCoder\AmoCRM_Wrap\Helpers\CustomField;
use DrillCoder\AmoCRM_Wrap\Helpers\Value;

/**
 * Class Base
 * @package AmoCRM
 */
abstract class Base
{
    /**
     * @var int
     */
    protected $id;
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
     * @var array
     */
    protected $config;

    /**
     * Base constructor.
     * @param int|null $amoId
     * @throws AmoWrapException
     */
    public function __construct($amoId = null)
    {
        $classNameArray = explode('\\', get_class($this));
        $className = strtolower(array_pop($classNameArray));
        $config = new Config();
        $this->config = $config->$className;
        if (AmoCRM::isAuthorization()) {
            if (!empty($amoId)) {
                $amoId = (int)$amoId;
                $this->loadInAmoId($amoId);
            }
        }
    }

    /**
     * @return Company|Contact|Lead|Note|Task
     * @throws AmoWrapException
     */
    public function save()
    {
        return Base::saveBase($this->getExtraRaw());
    }

    /**
     * @return array
     * @throws AmoWrapException
     */
    public function getRaw()
    {
        return Base::getRawBase($this->getExtraRaw());
    }

    /**
     * @return array
     */
    protected abstract function getExtraRaw();

    /**
     * @param array $data
     * @return Base|Company|Contact|Lead|Note|Task
     * @throws AmoWrapException
     */
    public function saveBase($data = array())
    {
        if (empty($this->id)) {
            $method = 'add';
        } else {
            $method = 'update';
        }
        $requestData[$method] = array($this->getRawBase($data));
        $res = AmoCRM::cUrl("api/v2/{$this->config['url']}", $requestData);
        if ($method == 'update') {
            $idRes = $res->_embedded->items[0]->id;
            if ($idRes == $this->id)
                return $this;
        } elseif ($method == 'add') {
            if (isset($res->_embedded->items[0]->id)) {
                $this->loadInAmoId($res->_embedded->items[0]->id);
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось сохранить или обновить сущность');
    }

    /**
     * @param int $id
     * @return Base|Company|Contact|Lead|Note|Task
     * @throws AmoWrapException
     */
    protected function loadInAmoId($id)
    {
        if (!empty($id)) {
            $typeUrl = $this->config['url'];
            $link = "api/v2/$typeUrl?id=$id";
            if ($this->config['info'] == 'Note') {
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
            $res = AmoCRM::cUrl($link);
            if (!empty($res->_embedded->items[0])) {
                return $this->loadInRaw($res->_embedded->items[0]);
            }
        }
        throw new AmoWrapException('Не удалось загрузить сущность');
    }

    /**
     * @param \stdClass|array $stdClass
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function loadInRaw($stdClass)
    {
        $stdClass = json_decode(json_encode($stdClass));
        if (!empty($stdClass->id)) {
            $this->id = (int)$stdClass->id;
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
            return $this;
        }
        throw new AmoWrapException('Не удалось загрузить сущность из сырых данных');
    }

    /**
     * @return Base|Company|Contact|Lead|Note|Task
     * @throws AmoWrapException
     */
    public function delete()
    {
        $typeDelete = $this->config['delete'];
        $post = array('ACTION' => 'DELETE', 'ID[]' => $this->id);
        $url = "ajax/$typeDelete/multiple/delete/";
        $res = AmoCRM::cUrl($url, http_build_query($post), null, true);
        if ($res !== null && $res->status == 'success') {
            foreach ($this as $key => $item) {
                $this->$key = null;
            }
            return $this;
        }
        throw new AmoWrapException('Не удалось удалить сущность');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return Base|Company|Contact|Lead
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
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
     * @throws AmoWrapException
     */
    public function getResponsibleUserName()
    {
        $usersIdAndName = AmoCRM::getInfo()->get('usersIdAndName');
        return $usersIdAndName[$this->responsibleUserId];
    }

    /**
     * @param int|string $responsibleUserIdOrName
     * @return Base|Company|Contact|Lead|Task
     * @throws AmoWrapException
     */
    public function setResponsibleUser($responsibleUserIdOrName)
    {
        $this->responsibleUserId = AmoCRM::getInfo()->getUserIdFromIdOrName($responsibleUserIdOrName);
        if (empty($this->responsibleUserId)) {
            throw new AmoWrapException('Ответственный не найден');
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @param Company|null $company
     * @return Base|Contact|Lead
     */
    public function setCompanyId($company = null)
    {
        $this->companyId = $company !== null ? $company->getId() : $company;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $tag
     * @return Base|Company|Contact|Lead
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @param string $tag
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function delTag($tag)
    {
        $key = array_search($tag, $this->tags);
        if ($key !== false) {
            unset($this->tags[$key]);
            throw new AmoWrapException('Тэг не найден');
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @return int|false
     * @throws AmoWrapException
     */
    public function addCustomField($name, $type)
    {
        $elementType = $this->config['elementType'];
        $data['request']['fields']['add'] = array(
            array(
                "name" => $name,
                "type" => $type,
                "element_type" => $elementType,
                "origin" => 'AmoCRM Wrap'
            )
        );
        $res = AmoCRM::cUrl('private/api/v2/json/fields/set', $data);
        if ($res !== null) {
            return $res->fields->add[0]->id;
        }
        throw new AmoWrapException('Не удалось добавить пользовательское поле');
    }

    /**
     * @param int|string $nameOrId
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function delCustomField($nameOrId)
    {
        $id = CustomField::getIdFromNameOrId($this->config['info'], $nameOrId);
        if (empty($id)) {
            throw new AmoWrapException('Не удалось найти id пользовательского поля');
        }
        $data['request']['fields']['delete'] = array(
            array(
                "id" => $id,
                "origin" => 'AmoCRM Wrap'
            )
        );
        $res = AmoCRM::cUrl('private/api/v2/json/fields/set', $data);
        if ($res !== null) {
            if ($res->fields->delete[0]->id == $id) {
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось удалить пользовательское поле');
    }


    /**
     * @param string|int $nameOrId
     * @return string
     * @throws AmoWrapException
     */
    public function getCustomFieldValue($nameOrId)
    {
        $values = array();
        if (AmoCRM::isAuthorization()) {
            $id = CustomField::getIdFromNameOrId($this->config['info'], $nameOrId);
            if (array_key_exists($id, $this->customFields)) {
                foreach ($this->customFields[$id]->getValues() as $value) {
                    $values[] = $value->getValue();
                }
                return implode('; ', $values);
            }
        } else {
            $customFields = $this->getCustomFieldsValue();
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
        return '';
    }

    /**
     * @return string[];
     */
    public function getCustomFieldsValue()
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
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function setCustomField($nameOrId, $values)
    {
        $type = $this->config['info'];
        $idCustomFields = AmoCRM::getInfo()->get("id{$type}CustomFields");
        if (array_key_exists($nameOrId, $idCustomFields)) {
            $id = $nameOrId;
            $name = $idCustomFields[$nameOrId];
        } elseif (in_array($nameOrId, $idCustomFields)) {
            $id = array_search($nameOrId, $idCustomFields);
            $name = $nameOrId;
        } else {
            throw new AmoWrapException('Не найти пользовательское поле');
        }
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
                    $idTypeCustomFieldsEnums = AmoCRM::getInfo()->get("id{$type}CustomFieldsEnums");
                    if (isset($idTypeCustomFieldsEnums[$id])) {
                        $enum = array_search($value, $idTypeCustomFieldsEnums[$id]);
                    } else {
                        $enum = null;
                    }
                    $valueObj[] = new Value($value, $enum);
                }
                $customFieldObj = new CustomField($id, $valueObj, $name);
                $this->customFields[$customFieldObj->getId()] = $customFieldObj;
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось задать значение пользовательскому полю');
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
     * @return Base|Note|Task
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
        return $this;
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
     * @return Base|Note|Task
     */
    public function setElementType($elementType)
    {
        $this->elementType = $elementType;
        return $this;
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
     * @return Base|Note|Task
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int|string $type
     * @return Base|Note|Task
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
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
     * @return string
     * @throws AmoWrapException
     */
    public function getUserNameUpdate()
    {
        $usersIdAndName = AmoCRM::getInfo()->get('usersIdAndName');
        return $usersIdAndName[$this->userIdUpdate];
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
     * @throws AmoWrapException
     */
    public function getCreatedUserName()
    {
        $usersIdAndName = AmoCRM::getInfo()->get('usersIdAndName');
        return $usersIdAndName[$this->createdUserId];
    }

    /**
     * @return int[]
     */
    public function getLeadsId()
    {
        return $this->leadsId;
    }

    /**
     * @param Lead $lead
     * @return Base|Company|Contact
     */
    public function addLead(Lead $lead)
    {
        $this->leadsId[] = $lead->getId();
        return $this;
    }

    /**
     * @param Lead $lead
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function delLeadId(Lead $lead)
    {
        $delKeys = array_keys($this->leadsId, $lead->getId());
        if (count($delKeys) > 0) {
            foreach ($delKeys as $delKey) {
                unset($this->leadsId[$delKey]);
            }
            $this->unlink['leads_id'][] = $lead->getId();
            return $this;
        }
        throw new AmoWrapException('Не найден id сделки');
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
     * @param Contact $contact
     * @return Base|Company|Lead
     */
    public function addContactId(Contact $contact)
    {
        $this->contactsId[] = $contact->getId();
        return $this;
    }

    /**
     * @param Contact $contact
     * @return Base|Company|Lead
     * @throws AmoWrapException
     */
    public function delContactId(Contact$contact)
    {
        $delKeys = array_keys($this->contactsId, $contact->getId());
        if (!empty($delKeys)) {
            foreach ($delKeys as $delKey) {
                unset($this->contactsId[$delKey]);
            }
            $this->unlink['contacts_id'][] = $contact->getId();
            return $this;
        }
        throw new AmoWrapException('Не найден id контакта');
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
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function addPhone($phone, $enum = 'OTHER')
    {
        $enum = mb_strtoupper($enum);
        if (!empty($this->phones)) {
            foreach ($this->phones as $value) {
                if (AmoCRM::clearPhone($value->getValue()) == AmoCRM::clearPhone($phone)) {
                    return $this;
                }
            }
        }
        if (AmoCRM::isAuthorization()) {
            $idPhoneEnums = AmoCRM::getInfo()->get('idPhoneEnums');
            if (array_key_exists($enum, $idPhoneEnums)) {
                $this->phones[] = new Value($phone, $idPhoneEnums[$enum]);
                return $this;
            }
        } else {
            $this->phones[] = new Value($phone);
            return $this;
        }
        throw new AmoWrapException('Не удалось добавить телефон');
    }

    /**
     * @param string $phone
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function delPhone($phone)
    {
        if (!empty($this->phones)) {
            foreach ($this->phones as $key => $value) {
                if (AmoCRM::clearPhone($value->getValue()) == AmoCRM::clearPhone($phone)) {
                    unset($this->phones[$key]);
                    return $this;
                }
            }
        }
        throw new AmoWrapException('Не удалось удалить телефон');
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
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function addEmail($email, $enum = 'OTHER')
    {
        $email = mb_strtolower($email);
        $enum = mb_strtoupper($enum);
        if (!empty($this->emails)) {
            foreach ($this->emails as $value) {
                if (mb_strtolower($value->getValue()) == $email)
                    return $this;
            }
        }
        if (AmoCRM::isAuthorization()) {
            $idEmailEnums = AmoCRM::getInfo()->get('idEmailEnums');
            if (array_key_exists($enum, $idEmailEnums)) {
                $this->emails[] = new Value($email, $idEmailEnums[$enum]);
                return $this;
            }
        } else {
            $this->emails[] = new Value($email);
            return $this;
        }
        throw new AmoWrapException('Не удалось добавить почту');
    }

    /**
     * @param string $email
     * @return Base|Company|Contact
     * @throws AmoWrapException
     */
    public function delEmail($email)
    {
        $email = mb_strtolower($email);
        if (!empty($this->emails)) {
            foreach ($this->emails as $key => $value) {
                if (mb_strtolower($value->getValue()) == $email) {
                    unset($this->emails[$key]);
                    return $this;
                }
            }
        }
        throw new AmoWrapException('Не удалось удалить почту');
    }

    /**
     * @param array $data
     * @return array
     * @throws AmoWrapException
     */
    public function getRawBase($data = array())
    {
        if (!empty($this->name)) {
            $data['name'] = $this->name;
        }
        if (!empty($this->responsibleUserId)) {
            $data['responsible_user_id'] = $this->responsibleUserId;
        }
        if (!empty($this->companyId)) {
            $data['company_id'] = $this->companyId;
        }
        if (!empty($this->leadsId)) {
            $data['leads_id'] = $this->leadsId;
        }
        if (!empty($this->contactsId)) {
            $data['contacts_id'] = $this->contactsId;
        }
        $data['tags'] = implode(',', $this->tags);
        if (!empty($this->unlink)) {
            $data['unlink'] = $this->unlink;
        }
        if (empty($this->id)) {
            $data['created_by'] = 0;
        } else {
            $data['id'] = $this->id;
            $data['updated_at'] = date('U');
            $data['updated_by'] = 0;
        }
        $customFields = $this->customFields;
        if (!empty($this->phones)) {
            $idPhoneEnums = AmoCRM::getInfo()->get('idPhoneEnums');
            foreach ($this->phones as &$phone) {
                if ($phone->getEnum() === 0) {
                    $phone->setEnum($idPhoneEnums['OTHER']);
                }
            }
            $customFieldPhone = new CustomField(AmoCRM::getInfo()->get('phoneFieldId'), $this->phones);
            $customFields[] = $customFieldPhone;
        }
        if (!empty($this->emails)) {
            $idEmailEnums = AmoCRM::getInfo()->get('idEmailEnums');
            foreach ($this->emails as &$email) {
                if ($email->getEnum() === 0) {
                    $email->setEnum($idEmailEnums['OTHER']);
                }
            }
            $customFieldEmail = new CustomField(AmoCRM::getInfo()->get('emailFieldId'), $this->emails);
            $customFields[] = $customFieldEmail;
        }
        if (!empty($customFields)) {
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
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function addNote($text, $type = 4)
    {
        if (empty($this->amoId)) {
            $this->save();
        }
        $note = new Note();
        $note->setText($text)
            ->setType($type)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();
        return $this;
    }

    /**
     * @param string $text
     * @param string $serviceName
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function addSystemNote($text, $serviceName)
    {
        if (empty($this->amoId)) {
            $this->save();
        }
        $note = new Note();
        $note->setText($text)
            ->setType(25)
            ->setService($serviceName)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();
        return $this;

    }

    /**
     * @param string $text
     * @param int|string|null $responsibleUserIdOrName
     * @param \DateTime|null $completeTill
     * @param int|string $typeId
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function addTask($text, $responsibleUserIdOrName = null, $completeTill = null, $typeId = 3)
    {
        if (empty($this->amoId)) {
            $this->save();
        }
        if ($responsibleUserIdOrName === null) {
            $responsibleUserIdOrName = $this->responsibleUserId;
        }
        $task = new Task();
        if ($completeTill !== null) {
            $task->setCompleteTill($completeTill);
        }
        if (!in_array($typeId, AmoCRM::getInfo()->get('taskTypes'))) {
            throw new AmoWrapException('Не удалось найти тип задачи');
        }
        $task->setText($text)
            ->setType($typeId)
            ->setResponsibleUser($responsibleUserIdOrName)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();
        return $this;
    }

    /**
     * @param string $pathToFile
     * @return Base|Company|Contact|Lead
     * @throws AmoWrapException
     */
    public function addFile($pathToFile)
    {
        if (empty($this->amoId)) {
            $this->save();
        }
        if (is_file($pathToFile) && file_exists($pathToFile)) {
            $elementType = $this->config['elementType'];
            if (class_exists('CURLFile')) {
                $CURLFile = new \CURLFile(realpath($pathToFile));
                $post = array(
                    'UserFile' => $CURLFile
                );
            } else {
                $post = array(
                    'UserFile' => "@" . $pathToFile
                );
            }
            $url = "/private/notes/edit2.php?ACTION=ADD_NOTE&ELEMENT_ID=" . $this->id . "&ELEMENT_TYPE=" .
                $elementType . "&file" . "api" . str_replace(".", "", microtime(true));
            $res = AmoCRM::cUrl($url, $post, null, true);
            if (isset($res->status) && $res->status == 'fail') {
                throw new AmoWrapException('Не удалось добавить файл');
            }
            $post = array(
                'ACTION' => "ADD_NOTE",
                'DATE_CREATE' => time(),
                'ATTACH' => $res->note->params->link,
                'BODY' => $res->note->params->text,
                'ELEMENT_ID' => $this->id,
                'ELEMENT_TYPE' => $elementType,
            );
            $res = AmoCRM::cUrl("private/notes/edit2.php", $post, null, true);
            if (isset($res->status) && $res->status == 'ok') {
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось добавить файл');
    }
}