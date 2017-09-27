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
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
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
    protected $responsibleUserId;
    /**
     * @var int
     */
    protected $linkedCompanyId;
    /**
     * @var string[]
     */
    protected $tags;
    /**
     * @var CustomField[]
     */
    protected $customFields;
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
        $this->customFields = array();
        $this->tags = array();
        $id = (int)$id;
        if (!empty($id)) {
            $this->loadInId($id);
        }
    }

    /**
     * @param array $data
     * @param CustomField[] $customFields
     * @return bool
     */
    public function save($data, $customFields = null)
    {
        $data = array_merge($data, array(
            'name' => $this->name,
            'responsible_user_id' => $this->responsibleUserId,
            'linked_company_id' => $this->linkedCompanyId,
        ));
        if (empty($this->id)) {
            $method = 'add';
            $data['created_user_id'] = 0;

        } else {
            $method = 'update';
            $data['id'] = $this->id;
            $data['last_modified'] = date('U');
            $data['modified_user_id'] = 0;
        }
        if (is_array($this->tags))
            $data['tags'] = implode(',', $this->tags);
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
        $type = mb_strtolower(self::getTypeObj()) . 's';
        $requestData['request'][$type][$method] = array(
            $data
        );
        $res = Amo::cUrl("v2/json/$type/set", 'post', $requestData);
        if ($method == 'update') {
            if ($res->{$type}->update[0]->id == $this->id)
                return true;
        } elseif ($method == 'add') {
            if ($this->loadInId($res->{$type}->add[0]->id))
                return true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    protected function loadInId($id)
    {
        $type = mb_strtolower(self::getTypeObj()) . 's';
        $link = "v2/json/$type/list?id=$id";
        if ($type == 'notes') {
            $note = $this;
            /** @var Note $note */
            $link .= "&type={$note->getElementTypeName()}";
        }
        $res = Amo::cUrl($link, 'get');
        if ($res) {
            $this->loadInStdClass($res->{$type}[0]);
            return true;
        }
        return false;
    }

    /**
     * @param \stdClass $stdClass
     */
    public function loadInStdClass($stdClass)
    {
        $this->id = (int)$stdClass->id;
        $this->name = $stdClass->name;
        $this->createdUserId = (int)$stdClass->created_user_id;
        $dateCreate = new \DateTime();
        $dateCreate->setTimestamp($stdClass->date_create);
        $this->dateCreate = $dateCreate;
        $lastModified = new \DateTime();
        $lastModified->setTimestamp($stdClass->last_modified);
        $this->lastModified = $lastModified;
        $this->responsibleUserId = (int)$stdClass->responsible_user_id;
        $this->linkedCompanyId = (int)$stdClass->linked_company_id;
        if (is_array($stdClass->tags)) {
            foreach ($stdClass->tags as $tag) {
                $this->tags[$tag->id] = $tag->name;
            }
        }
    }

    /**
     * @return string
     */
    private function getTypeObj()
    {
        $type = explode('\\', get_class($this));
        return $type[count($type) - 1];
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
    public function setResponsibleUserId($responsibleUserIdOrName)
    {
        $idUsers = Amo::$info->get('idUsers');
        if (array_key_exists($responsibleUserIdOrName, $idUsers)) {
            $this->responsibleUserId = $responsibleUserIdOrName;
            return true;
        } else {
            foreach ($idUsers as $key => $name) {
                if (stripos($name, $responsibleUserIdOrName) !== false) {
                    $this->responsibleUserId = $key;
                    return true;
                }
            }
        }
        return false;
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
        if ($key = array_search($tag, $this->tags) !== false) {
            unset($this->tags[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param string|int $nameOrId
     * @return string|null;
     */
    public function getCustomField($nameOrId)
    {
        $type = self::getTypeObj();
        $idCustomFields = Amo::$info->get("id{$type}CustomFields");
        if (array_key_exists($nameOrId, $idCustomFields)) {
            $id = $nameOrId;
        } elseif (in_array($nameOrId, $idCustomFields)) {
            $id = array_search($nameOrId, $idCustomFields);
        } else
            return null;
        $values = array();
        foreach ($this->customFields[$id]->getValues() as $value) {
            $values[] = $value->getValue();
        }
        return implode(', ', $values);
    }

    /**
     * @return string[]|null;
     */
    public function getCustomFields()
    {
        $type = self::getTypeObj();
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
     * @param string $value
     * @return bool
     */
    public function setCustomField($customFieldNameOrId, $value = null)
    {
        $type = self::getTypeObj();
        $idCustomFields = Amo::$info->get("id{$type}CustomFields");
        if (array_key_exists($customFieldNameOrId, $idCustomFields)) {
            $customFieldId = $customFieldNameOrId;
            $customFieldName = $idCustomFields[$customFieldNameOrId];
        } elseif (in_array($customFieldNameOrId, $idCustomFields)) {
            $customFieldId = array_search($customFieldNameOrId, $idCustomFields);
            $customFieldName = $customFieldNameOrId;
        } else
            return false;
        if (empty($value)) {
            if (array_key_exists($customFieldId, $this->customFields)) {
                $this->customFields[$customFieldId]->delAllValues();
            }
        } else {
            if (Amo::$info->get("id{$type}CustomFieldsEnums")[$customFieldId]) {
                $enum = array_search($value, Amo::$info->get("id{$type}CustomFieldsEnums")[$customFieldId]);
            } else {
                $enum = null;
            }
            $valueObj = new Value($value, $enum);
            $customFieldObj = new CustomField($customFieldId, array($valueObj), $customFieldName);
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
     * @return bool|string
     */
    private function getElementTypeName()
    {
        if (array_key_exists($this->elementType, Amo::$info->get('elementType')))
            return Amo::$info->get('elementType')[$this->elementType];
        return false;
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
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        $note = new Note();
        $note->setText($text);
        $note->setType($type);
        $note->setElementId($this->id);
        $typeObj = mb_strtolower(self::getTypeObj());
        if (in_array($typeObj, Amo::$info->get('elementType')))
            $note->setElementType(array_search($typeObj, Amo::$info->get('elementType')));
        else
            return false;
        return $note->save();
    }

    /**
     * @param string $text
     * @param \DateTime|null $completeTill
     * @param int|string $typeId
     * @param int|string|null $responsibleUserIdOrName
     * @return bool
     */
    public function addTask($text, $completeTill = null, $typeId = 3, $responsibleUserIdOrName = null)
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
        $task->setResponsibleUserId($responsibleUserIdOrName);
        $task->setElementId($this->id);
        $typeObj = mb_strtolower(self::getTypeObj());
        if (in_array($typeObj, Amo::$info->get('elementType')))
            $task->setElementType(array_search($typeObj, Amo::$info->get('elementType')));
        else
            return false;
        $task->save();
        echo '<pre>';
        var_dump($task);
        echo '</pre>';
        die;
    }
}