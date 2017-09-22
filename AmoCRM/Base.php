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
        $type = mb_strtolower(self::getType()) . 's';
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
        $type = mb_strtolower(self::getType()) . 's';
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
    private function getType()
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

//TODO создать метод возвращающий имя ответственного

    /**
     * @param int|string $responsibleUser
     * @return bool
     */
    public function setResponsibleUserId($responsibleUser)
    {
        $idUsers = Amo::$info->get('idUsers');
        if (array_key_exists($responsibleUser, $idUsers)) {
            $this->responsibleUserId = $responsibleUser;
            return true;
        } else {
            foreach ($idUsers as $key => $name) {
                if (stripos($name, $responsibleUser) !== false) {
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

    //TODO Переделать в возвращение масива или конкретного поля а не класс хэлпер
//    /**
//     * @return CustomField[]
//     */
//    public function getCustomFields()
//    {
//        return $this->customFields;
//    }

    /**
     * @param string|int $customFieldNameOrId
     * @param string $value
     * @return bool
     */
    public function setCustomField($customFieldNameOrId, $value = null)
    {
        $type = self::getType();
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
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        $note = new Note();
        $note->setElementId($this->id);
        $note->setText($text);
        $note->setType($type);
        $typeObj = mb_strtolower($this->getType());
        if (in_array($typeObj, Amo::$info->get('ElementType')))
            $note->setElementType(array_search($typeObj, Amo::$info->get('ElementType')));
        else
            return false;
        return $note->save();
    }
}