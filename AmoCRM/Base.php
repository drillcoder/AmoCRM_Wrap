<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 12.09.17
 * Time: 11:55
 */

namespace AmoCRM;

use AmoCRM\Helpers\Info;
use AmoCRM\Helpers\CustomField;

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
     * @var int
     */
    protected $modifiedUserId;
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
     * @var Info
     */
    protected $info;

    /**
     * @return array
     */
    public abstract function save();

    /**
     * @param string|int $customFieldNameOrId
     * @param string $value
     * @return bool
     */
    public abstract function addCustomField($customFieldNameOrId, $value);

    /**
     * @param string|int $customFieldNameOrId
     * @return bool
     */
    public abstract function delCustomField($customFieldNameOrId);

    /**
     * Contact constructor.
     * @param Info $info
     */
    public function __construct($info)
    {
        $this->info = $info;
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
     * @param int|string $responsibleUser
     * @return bool
     */
    public function setResponsibleUserId($responsibleUser)
    {
        $idUsers = $this->info->get('idUsers');
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


    /**
     * @return CustomField[]
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }
}