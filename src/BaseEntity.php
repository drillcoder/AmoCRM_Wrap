<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 12.09.17
 * Time: 11:55
 */

namespace DrillCoder\AmoCRM_Wrap;

use CURLFile;
use DateTime;
use DrillCoder\AmoCRM_Wrap\Helpers\Config;
use DrillCoder\AmoCRM_Wrap\Helpers\CustomField;
use DrillCoder\AmoCRM_Wrap\Helpers\Value;
use Exception;
use stdClass;

/**
 * Class Base
 * @package DrillCoder\AmoCRM_Wrap
 */
abstract class BaseEntity extends Base
{
    /**
     * @var string
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
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Value[]
     */
    private $phones = array();

    /**
     * @var Value[]
     */
    private $emails = array();

    /**
     * @var string
     */
    protected $createdUserId;

    /**
     * @var DateTime
     */
    private $dateCreate;

    /**
     * @var DateTime
     */
    private $dateUpdate;

    /**
     * @var string
     */
    private $userIdUpdate;

    /**
     * @var string
     */
    private $responsibleUserId;

    /**
     * @var string
     */
    private $companyId;

    /**
     * @var int[]
     */
    private $leadsId = array();

    /**
     * @var int[]
     */
    private $contactsId = array();

    /**
     * @var string[]
     */
    private $tags = array();

    /**
     * @var CustomField[]
     */
    private $customFields = array();

    /**
     * @var int|string
     */
    protected $type;

    /**
     * @var array
     */
    private $unlink = array();

    /**
     * @var array
     */
    protected $config;

    /**
     * @param int|string|null $id
     *
     * @throws AmoWrapException
     */
    public function __construct($id = null)
    {
        if (!AmoCRM::isAuthorization()) {
            throw new AmoWrapException('Требуется авторизация');
        }

        $classNameArray = explode('\\', get_class($this));
        $className = mb_strtolower(array_pop($classNameArray));
        $this->config = Config::$$className;

        if ($id !== null) {
            $id = Base::onlyNumbers($id);
            $this->load($id);
        }
    }

    /**
     * @param stdClass|array $data
     *
     * @throws AmoWrapException
     */
    public function loadInRaw($data)
    {
        try {
            $data = json_decode(json_encode($data));
            if (!empty($data->id)) {
                $this->id = Base::onlyNumbers($data->id);
                $this->name = isset($data->name) ? $data->name : null;
                $this->createdUserId = isset($data->created_by) ? Base::onlyNumbers($data->created_by) : null;
                $dateCreate = new DateTime();
                $dateCreate->setTimestamp($data->created_at);
                $this->dateCreate = $dateCreate;
                $dateUpdate = new DateTime();
                $dateUpdate->setTimestamp($data->updated_at);
                $this->dateUpdate = $dateUpdate;
                $this->responsibleUserId = isset($data->responsible_user_id) ?
                    Base::onlyNumbers($data->responsible_user_id) : null;
                $this->userIdUpdate = isset($data->updated_by) ? Base::onlyNumbers($data->updated_by) : null;
                $this->companyId = isset($data->company->id) ? Base::onlyNumbers($data->company->id) : null;
                $this->leadsId = isset($data->leads->id) ? $data->leads->id : array();
                $this->contactsId = isset($data->contacts->id) ? $data->contacts->id : array();
                $this->type = isset($data->note_type) ? (int)$data->note_type : null;
                $this->elementId = isset($data->element_id) ? Base::onlyNumbers($data->element_id) : null;
                $this->elementType = isset($data->element_type) ? (int)$data->element_type : null;
                $this->text = isset($data->text) ? $data->text : null;

                if (isset($data->tags) && is_array($data->tags)) {
                    foreach ($data->tags as $tag) {
                        $this->tags[$tag->id] = $tag->name;
                    }
                }
                if (isset($data->custom_fields) && is_array($data->custom_fields)) {
                    foreach ($data->custom_fields as $custom_field) {
                        $customField = CustomField::loadInRaw($custom_field);
                        if ($customField->getId() === AmoCRM::getPhoneFieldId()) {
                            $this->phones = $customField->getValues();
                        } elseif ($customField->getId() === AmoCRM::getEmailFieldId()) {
                            $this->emails = $customField->getValues();
                        } else {
                            $this->customFields[$customField->getId()] = $customField;
                        }
                    }
                }
            } else {
                throw new AmoWrapException('Не удалось загрузить сущность из сырых данных');
            }
        } catch (Exception $e) {
            throw new AmoWrapException("Ошибка в обёртке: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->getRawBase($this->getExtraRaw());
    }

    /**
     * @return Company|Contact|Lead|Note|Task
     *
     * @throws AmoWrapException
     */
    public function save()
    {
        return $this->saveBase($this->getExtraRaw());
    }

    /**
     * @return BaseEntity|Company|Contact|Lead|Note|Task
     *
     * @throws AmoWrapException
     */
    public function delete()
    {
        $url = "ajax/{$this->config['delete']}/multiple/delete/";
        $post = array('ACTION' => 'DELETE', 'ID[]' => $this->id);

        $res = AmoCRM::cUrl($url, http_build_query($post), null, true);
        if ($res !== null && $res->status === 'success') {
            foreach ($this as $key => $item) {
                $this->$key = null;
            }

            return $this;
        }

        throw new AmoWrapException('Не удалось удалить сущность');
    }

    /**
     * @return string
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
     *
     * @return BaseEntity|Company|Contact|Lead
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
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
        $usersIdAndName = AmoCRM::getUsers();
        return $usersIdAndName[$this->responsibleUserId];
    }

    /**
     * @param int|string $responsibleUserIdOrName
     *
     * @return BaseEntity|Company|Contact|Lead|Task
     *
     * @throws AmoWrapException
     */
    public function setResponsibleUser($responsibleUserIdOrName)
    {
        $this->responsibleUserId = AmoCRM::searchUserId($responsibleUserIdOrName);

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
     * @return Company
     *
     * @throws AmoWrapException
     */
    public function getCompany()
    {
        return new Company($this->companyId);
    }

    /**
     * @param Company $company
     *
     * @return BaseEntity|Contact|Lead
     */
    public function setCompany($company)
    {
        $id = $company instanceof Company ? $company->getId() : Base::onlyNumbers($company);
        $this->companyId = $id;

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
     *
     * @return BaseEntity|Company|Contact|Lead
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function delTag($tag)
    {
        $key = array_search($tag, $this->tags);
        if ($key !== false) {
            unset($this->tags[$key]);

            return $this;
        }

        throw new AmoWrapException('Тэг не найден');
    }

    /**
     * @param string $name
     * @param int    $type
     * @param array  $enums
     *
     * @return int
     *
     * @throws AmoWrapException
     */
    public function addCustomField($name, $type = CustomField::TYPE_TEXT, $enums = array())
    {
        if (!is_array($enums)) {
            $enums = array($enums);
        }
        $elementType = $this->config['elementType'];
        $data['request']['fields']['add'] = array(
            array(
                'name' => $name,
                'type' => $type,
                'enums' => $enums,
                'element_type' => $elementType,
                'origin' => 'DrillCoder AmoCRM Wrap',
                'disabled' => false,
            )
        );
        $res = AmoCRM::cUrl('private/api/v2/json/fields/set', $data);
        if ($res !== null && isset($res->response->fields->add[0]->id)) {
            return $res->response->fields->add[0]->id;
        }

        throw new AmoWrapException('Не удалось добавить пользовательское поле');
    }

    /**
     * @param int|string $nameOrId
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function delCustomField($nameOrId)
    {
        $id = $this->searchCustomFieldsId($nameOrId);

        $data['request']['fields']['delete'] = array(
            array(
                'id' => $id,
                'origin' => 'DrillCoder AmoCRM Wrap'
            )
        );
        $res = AmoCRM::cUrl('private/api/v2/json/fields/set', $data);
        if ($res !== null && isset($res->response->fields->delete[0]->id) && $res->response->fields->delete[0]->id === $id) {
            return $this;
        }

        throw new AmoWrapException('Не удалось удалить пользовательское поле');
    }

    /**
     * @param string|int $nameOrId
     *
     * @return string
     *
     * @throws AmoWrapException
     */
    public function getCustomFieldValueInStr($nameOrId)
    {
        return $this->getCustomField($nameOrId)->getValuesInStr();
    }

    /**
     * @param string|int $nameOrId
     *
     * @return string[]
     *
     * @throws AmoWrapException
     */
    public function getCustomFieldValueInArray($nameOrId)
    {
        return $this->getCustomField($nameOrId)->getValuesInArray();
    }

    /**
     * @param string|int   $nameOrId
     * @param string|array $values
     * @param int|null     $subtype
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function setCustomFieldValue($nameOrId, $values, $subtype = null)
    {
        $customField = $this->getCustomField($nameOrId);
        if (!is_array($values)) {
            $values = array($values);
        }

        if ($subtype !== null) {
            $valueObj = $customField->getValues();
        } else {
            $valueObj = array();
        }

        foreach ($values as $value) {
            $value = trim($value);
            $customFieldsEnums = $this->getEnums($customField->getId());
            $enum = null;
            foreach ($customFieldsEnums as $enumId => $enumName) {
                if (mb_stripos($enumName, $value) !== false) {
                    $enum = $enumId;
                }
            }
            $valueObj[] = new Value($value, $enum, $subtype);
        }
        $customField->setValues($valueObj);

        return $this;
    }

    /**
     * @return string
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param string $elementId
     *
     * @return BaseEntity|Note|Task
     */
    public function setElementId($elementId)
    {
        $this->elementId = Base::onlyNumbers($elementId);

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
     *
     * @return BaseEntity|Note|Task
     */
    public function setElementType($elementType)
    {
        $this->elementType = (int)$elementType;

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
     *
     * @return BaseEntity|Note|Task
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
     *
     * @return BaseEntity|Note|Task
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreate()
    {
        return $this->dateCreate;
    }

    /**
     * @return DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * @return string
     */
    public function getUserIdUpdate()
    {
        return $this->userIdUpdate;
    }

    /**
     * @return string
     */
    public function getUserNameUpdate()
    {
        $users = AmoCRM::getUsers();

        return $users[$this->userIdUpdate];
    }

    /**
     * @return string
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
        $users = AmoCRM::getUsers();

        return $users[$this->createdUserId];
    }

    /**
     * @return int[]
     */
    public function getLeadsId()
    {
        return $this->leadsId;
    }

    /**
     * @return Lead[]
     *
     * @throws AmoWrapException
     */
    public function getLeads()
    {
        $leads = array();
        foreach ($this->leadsId as $leadId) {
            $lead = new Lead($leadId);
            $leads[] = $lead;
        }
        return $leads;
    }

    /**
     * @param Lead|string|int $lead
     *
     * @return BaseEntity|Company|Contact
     */
    public function addLead($lead)
    {
        $id = $lead instanceof Lead ? $lead->getId() : Base::onlyNumbers($lead);
        $this->leadsId[] = $id;

        return $this;
    }

    /**
     * @param Lead|string|int $lead
     *
     * @return BaseEntity|Company|Contact
     *
     * @throws AmoWrapException
     */
    public function delLead($lead)
    {
        $id = $lead instanceof Lead ? $lead->getId() : Base::onlyNumbers($lead);
        $delKeys = array_keys($this->leadsId, $id);
        if (count($delKeys) > 0) {
            foreach ($delKeys as $delKey) {
                unset($this->leadsId[$delKey]);
            }
            $this->unlink['leads_id'][] = $id;

            return $this;
        }

        throw new AmoWrapException('Не найден id сделки');
    }

    /**
     * @return int[]
     */
    public function getContactsId()
    {
        return $this->contactsId;
    }

    /**
     * @return Contact[]
     *
     * @throws AmoWrapException
     */
    public function getContacts()
    {
        $contacts = array();
        foreach ($this->contactsId as $contactId) {
            $contact = new Contact($contactId);
            $contacts[] = $contact;
        }
        return $contacts;
    }

    /**
     * @param Contact|string|int $contact
     *
     * @return BaseEntity|Company|Lead
     */
    public function addContact($contact)
    {
        $id = $contact instanceof Contact ? $contact->getId() : Base::onlyNumbers($contact);
        $this->contactsId[] = $id;

        return $this;
    }

    /**
     * @param Contact|string|int $contact
     *
     * @return BaseEntity|Company|Lead
     *
     * @throws AmoWrapException
     */
    public function delContact($contact)
    {
        $id = $contact instanceof Contact ? $contact->getId() : Base::onlyNumbers($contact);
        $delKeys = array_keys($this->contactsId, $id);
        if (!empty($delKeys)) {
            foreach ($delKeys as $delKey) {
                unset($this->contactsId[$delKey]);
            }
            $this->unlink['contacts_id'][] = $id;
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
     *
     * @return BaseEntity|Company|Contact
     *
     * @throws AmoWrapException
     */
    public function addPhone($phone, $enum = CustomField::PHONE_OTHER)
    {
        $enum = mb_strtoupper($enum);
        if (count($this->phones) > 0) {
            foreach ($this->phones as $value) {
                if (Base::onlyNumbers($value->getValue()) === Base::onlyNumbers($phone)) {
                    return $this;
                }
            }
        }

        $idPhoneEnums = AmoCRM::getPhoneEnums();
        if (isset($idPhoneEnums[$enum])) {
            $this->phones[] = new Value($phone, $idPhoneEnums[$enum]);

            return $this;
        }

        throw new AmoWrapException('Не удалось добавить телефон');
    }

    /**
     * @param string $phone
     *
     * @return BaseEntity|Company|Contact
     *
     * @throws AmoWrapException
     */
    public function delPhone($phone)
    {
        if (count($this->phones) > 0) {
            foreach ($this->phones as $key => $value) {
                if (AmoCRM::onlyNumbers($value->getValue()) === AmoCRM::onlyNumbers($phone)) {
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
     *
     * @return BaseEntity|Company|Contact
     *
     * @throws AmoWrapException
     */
    public function addEmail($email, $enum = CustomField::EMAIL_OTHER)
    {
        $email = mb_strtolower($email);
        $enum = mb_strtoupper($enum);
        if (!empty($this->emails)) {
            foreach ($this->emails as $value) {
                if (mb_strtolower($value->getValue()) === $email) {
                    return $this;
                }
            }
        }

        $emailEnums = AmoCRM::getEmailEnums();
        if (isset($emailEnums[$enum])) {
            $this->emails[] = new Value($email, $emailEnums[$enum]);

            return $this;
        }

        throw new AmoWrapException('Не удалось добавить почту');
    }

    /**
     * @param string $email
     *
     * @return BaseEntity|Company|Contact
     *
     * @throws AmoWrapException
     */
    public function delEmail($email)
    {
        $email = mb_strtolower($email);
        if (!empty($this->emails)) {
            foreach ($this->emails as $key => $value) {
                if (mb_strtolower($value->getValue()) === $email) {
                    unset($this->emails[$key]);

                    return $this;
                }
            }
        }

        throw new AmoWrapException('Не удалось удалить почту');
    }

    /**
     * @param string $text
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addNote($text)
    {
        if ($this->id === null) {
            $this->save();
        }

        $note = new Note();
        $note->setText($text)
            ->setType(4)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();

        return $this;
    }

    /**
     * @param string $text
     * @param string $serviceName
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addNoteSystem($text, $serviceName)
    {
        if ($this->id === null) {
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
     * @param string $phone
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addNoteSmsOut($text, $phone)
    {
        if ($this->id === null) {
            $this->save();
        }

        $note = new Note();
        $note->setText($text)
            ->setType(103)
            ->setPhone($phone)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();

        return $this;
    }

    /**
     * @param string $text
     * @param string $phone
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addNoteSmsIn($text, $phone)
    {
        if ($this->id === null) {
            $this->save();
        }

        $note = new Note();
        $note->setText($text)
            ->setType(102)
            ->setPhone($phone)
            ->setCreatedUser($this->getResponsibleUserId())
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();

        return $this;
    }

    /**
     * @param string          $text
     * @param int|string|null $responsibleUserIdOrName
     * @param DateTime|null   $completeTill
     * @param int|string      $type
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addTask($text, $responsibleUserIdOrName = null, DateTime $completeTill = null, $type = 3)
    {
        if (empty($this->amoId)) {
            $this->save();
        }
        $tapeId = AmoCRM::searchTaskType($type);

        if ($responsibleUserIdOrName === null) {
            $responsibleUserIdOrName = $this->responsibleUserId;
        }

        $task = new Task();
        if ($completeTill !== null) {
            $task->setCompleteTill($completeTill);
        }

        $task->setText($text)
            ->setType($tapeId)
            ->setResponsibleUser($responsibleUserIdOrName)
            ->setElementId($this->id)
            ->setElementType($this->config['elementType'])
            ->save();

        return $this;
    }

    /**
     * @param string $pathToFile
     *
     * @return BaseEntity|Company|Contact|Lead
     *
     * @throws AmoWrapException
     */
    public function addFile($pathToFile)
    {
        if ($this->id === null) {
            $this->save();
        }

        if (is_file($pathToFile) && file_exists($pathToFile)) {
            $elementType = $this->config['elementType'];
            if (class_exists('CURLFile')) {
                $CURLFile = new CURLFile(realpath($pathToFile));
                $post = array(
                    'UserFile' => $CURLFile
                );
            } else {
                $post = array(
                    'UserFile' => '@' . $pathToFile
                );
            }
            $url = "/private/notes/upload.php?ACTION=ADD_NOTE&ELEMENT_ID={$this->id}&ELEMENT_TYPE={$elementType}&fileapi" .
	               str_replace('.', '', microtime(true));
            $res = AmoCRM::cUrl($url, $post, null, true);
            if ($res !== null && isset($res->status) && $res->status === 'fail') {
                throw new AmoWrapException('Не удалось добавить файл');
            }
            $post = array(
                'ACTION' => 'ADD_NOTE',
                'DATE_CREATE' => time(),
                'ATTACH' => $res->note->params->link,
                'BODY' => $res->note->params->text,
                'ELEMENT_ID' => $this->id,
                'ELEMENT_TYPE' => $elementType,
            );
            $res = AmoCRM::cUrl('private/notes/edit2.php', $post, null, true);
            if ($res !== null && isset($res->status) && $res->status !== 'ok') {
                throw new AmoWrapException('Не удалось добавить файл');
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function getExtraRaw();

    /**
     * @param int $id
     *
     * @throws AmoWrapException
     */
    protected function load($id)
    {
        $typeUrl = $this->config['url'];
        $link = "api/v2/$typeUrl?id=$id";

        if ($this->config['info'] === 'note') {
            $type = Config::$types[$this->elementType];
            $link .= "&type=$type";
        }

        $res = AmoCRM::cUrl($link);
        if ($res !== null && count($res->_embedded->items) > 0) {
            $this->loadInRaw(current($res->_embedded->items));
        } else {
            throw new AmoWrapException('Не удалось загрузить сущность');
        }
    }

    /**
     * @param string|int $nameOrId
     *
     * @return CustomField
     *
     * @throws AmoWrapException
     */
    private function getCustomField($nameOrId)
    {
        $id = $this->searchCustomFieldsId($nameOrId);
        if (!isset($this->customFields[$id])) {
            $this->customFields[$id] = new CustomField($id);
        }

        return $this->customFields[$id];
    }


    /**
     * @param string|int $nameOrId
     *
     * @return int
     *
     * @throws AmoWrapException
     */
    public function searchCustomFieldsId($nameOrId)
    {
        $customFields = AmoCRM::getCustomFields($this->config['info']);

        if (isset($customFields[$nameOrId])) {
            return $nameOrId;
        }

        foreach ($customFields as $customFieldId => $customFieldName) {
            if (mb_stripos($customFieldName, $nameOrId) !== false) {
                return $customFieldId;
            }
        }

        throw new AmoWrapException('Не удалось найти пользовательское поле');
    }

    /**
     * @param array $data
     *
     * @return BaseEntity|Company|Contact|Lead|Note|Task
     *
     * @throws AmoWrapException
     */
    private function saveBase($data = array())
    {
        if ($this->id === null) {
            $method = 'add';
        } else {
            $method = 'update';
        }
        $requestData[$method] = array($this->getRawBase($data));
        $res = AmoCRM::cUrl("api/v2/{$this->config['url']}", $requestData);
        if ($res !== null && isset($res->_embedded->items[0]->id)) {
            $classFullName = get_class($this);
            /** @var BaseEntity $entity */
            $entity = new $classFullName;
            if ($this->config['info'] === 'note') {
                $entity->setElementType($this->elementType);
            }
            $entity->load($res->_embedded->items[0]->id);

            return $entity;
        }

        throw new AmoWrapException('Не удалось сохранить сущность');
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function getRawBase($data = array())
    {
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->responsibleUserId !== null) {
            $data['responsible_user_id'] = $this->responsibleUserId;
        }
        if ($this->companyId !== null) {
            $data['company_id'] = $this->companyId;
        }
        if (count($this->leadsId) > 0) {
            $data['leads_id'] = $this->leadsId;
        }
        if (count($this->contactsId) > 0) {
            $data['contacts_id'] = $this->contactsId;
        }
        $data['tags'] = implode(',', $this->tags);
        if (count($this->unlink) > 0) {
            $data['unlink'] = $this->unlink;
        }
        if ($this->id === null) {
            $data['created_by'] = isset($data['created_by']) ? $data['created_by'] : 0;
        } else {
            $data['id'] = $this->id;
            $data['updated_at'] = date('U');
            $data['updated_by'] = 0;
        }
        $customFields = $this->customFields;
        if (count($this->phones) > 0) {
            $idPhoneEnums = AmoCRM::getPhoneEnums();
            foreach ($this->phones as $phone) {
                if ($phone->getEnum() === 0) {
                    $phone->setEnum($idPhoneEnums['OTHER']);
                }
            }
            $customFields[] = new CustomField(AmoCRM::getPhoneFieldId(), $this->phones);
        }
        if (count($this->emails) > 0) {
            $idEmailEnums = AmoCRM::getEmailEnums();
            foreach ($this->emails as $email) {
                if ($email->getEnum() === 0) {
                    $email->setEnum($idEmailEnums['OTHER']);
                }
            }
            $customFields[] = new CustomField(AmoCRM::getEmailFieldId(), $this->emails);
        }
        if (count($customFields) > 0) {
            foreach ($customFields as $customFieldObj) {
                $values = array();
                foreach ($customFieldObj->getValues() as $valueObj) {
                    $value = array(
                        'enum' => $valueObj->getEnum(),
                        'value' => $valueObj->getValue(),
                        'subtype' => $valueObj->getSubtype(),
                    );
                    $values[] = $value;
                }
                $data['custom_fields'][] = array(
                    'id' => $customFieldObj->getId(),
                    'values' => $values
                );
            }
        }
        return $data;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getEnums($id)
    {
        $enums = AmoCRM::getCustomFieldsEnums($this->config['info']);

        return isset($enums[$id]) ? $enums[$id] : array();
    }
}