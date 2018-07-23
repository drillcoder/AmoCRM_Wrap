<?php

/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 21.07.17
 * Time: 17:11
 */

namespace DrillCoder\AmoCRM_Wrap;

use DrillCoder\AmoCRM_Wrap\Helpers\Config;
use DrillCoder\AmoCRM_Wrap\Helpers\Info;

/**
 * Class Amo
 * @package DrillCoder\AmoCRM_Wrap
 * @version Version 6.0.4
 */
class AmoCRM
{
    /**
     * Wrap Version
     */
    const VERSION = '6.0.4';
    /**
     * @var string
     */
    private static $domain;
    /**
     * @var string
     */
    private static $userLogin;
    /**
     * @var string
     */
    private static $userAPIKey;
    /**
     * @var bool
     */
    private static $authorization;
    /**
     * @var Info
     */
    private static $info;

    /**
     * Amo constructor.
     * @param string $domain
     * @param string $userLogin
     * @param string $userAPIKey
     * @throws AmoWrapException
     */
    public function __construct($domain, $userLogin, $userAPIKey)
    {
        self::$domain = $domain;
        self::$userLogin = $userLogin;
        self::$userAPIKey = $userAPIKey;
        self::$authorization = true;
        $user = array(
            'USER_LOGIN' => $userLogin,
            'USER_HASH' => $userAPIKey
        );
        $res = self::cUrl('private/api/auth.php?type=json', $user);
        self::$authorization = $res->response->auth;
        if (self::$authorization) {
            $res = AmoCRM::cUrl('api/v2/account?with=custom_fields,users,pipelines,task_types');
            self::$info = new Info($res->_embedded);
        } else {
            throw new AmoWrapException('Данные для авторизации не верны');
        }
    }

    /**
     * @param string $url
     * @param array $data
     * @param \DateTime|null $modifiedSince
     * @param bool $ajax
     * @return mixed|null
     * @throws AmoWrapException
     */
    public static function cUrl($url, $data = array(), \DateTime $modifiedSince = null, $ajax = false)
    {
        if (self::$authorization) {
            $url = 'https://' . self::$domain . '.amocrm.ru/' . $url;
            $isUnsorted = stripos($url, 'incoming_leads') !== false;
            if ($isUnsorted) {
                $url .= '&login=' . self::$userLogin . '&api_key=' . self::$userAPIKey;
            } else {
                if (strripos($url, '?') === false) {
                    $url .= '?';
                }
                $url .= '&USER_LOGIN=' . self::$userLogin . '&USER_HASH=' . self::$userAPIKey;
            }
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            $headers = array();
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, true);
                if ($ajax) {
                    $headers[] = 'X-Requested-With: XMLHttpRequest';
                } else {
                    if ($isUnsorted) {
                        $data = http_build_query($data);
                    } else {
                        $headers[] = 'Content-Type: application/json';
                        $data = json_encode($data);
                    }
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            if (!empty($modifiedSince)) {
                $headers[] = 'IF-MODIFIED-SINCE: ' . $modifiedSince->format(\DateTime::RFC1123);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out);
            if ($response) {
                return $response;
            }
        } else {
            throw new AmoWrapException('Требуется авторизация');
        }
        return null;
    }

    /**
     * @return bool
     */
    public static function isAuthorization()
    {
        return self::$authorization;
    }

    /**
     * @return Info
     * @throws AmoWrapException
     */
    public static function getInfo()
    {
        if (self::$info !== null) {
            return self::$info;
        } else {
            throw new AmoWrapException('Требуется авторизация');
        }
    }

    /**
     * @param $phone
     * @param $email
     * @return Contact[]
     * @throws AmoWrapException
     */
    public function searchContact($phone, $email = null)
    {
        $link = 'api/v2/contacts/?query=';
        $contacts = array();
        if (!empty($phone)) {
            $phone = self::clearPhone($phone);
            $linkPhone = $link . $phone;
            $res = self::cUrl($linkPhone);
            if ($res !== null) {
                foreach ($res->_embedded->items as $raw) {
                    $contact = new Contact();
                    $contact->loadInRaw($raw);
                    if (in_array($phone, $contact->getPhones())) {
                        $contacts[$contact->getId()] = $contact;
                    }
                }
            }
        }
        if (!empty($email)) {
            $linkEmail = $link . $email;
            $res = self::cUrl($linkEmail);
            if ($res !== null) {
                foreach ($res->_embedded->items as $raw) {
                    $contact = new Contact();
                    $contact->loadInRaw($raw);
                    if (in_array($email, $contact->getEmails())) {
                        $contacts[$contact->getId()] = $contact;
                    }
                }
            }
        }
        return $contacts;
    }

    /**
     * @param string $phone
     * @return integer
     */
    public static function clearPhone($phone)
    {
        return preg_replace("/[^0-9]/", '', $phone);
    }

    /**
     * @param string $query
     * @return Company[]
     * @throws AmoWrapException
     */
    public function searchCompany($query)
    {
        $res = AmoCRM::cUrl("api/v2/companies?query=$query");
        $companies = array();
        if ($res !== null) {
            foreach ($res->_embedded->items as $raw) {
                $company = new Company();
                $company->loadInRaw($raw);
                $companies[$company->getId()] = $company;
            }
        }
        return $companies;
    }

    /**
     * @param string $query
     * @return Lead[]
     * @throws AmoWrapException
     */
    public function searchLead($query)
    {
        $res = AmoCRM::cUrl("api/v2/leads?query=$query");
        $leads = array();
        if ($res !== null) {
            foreach ($res->_embedded->items as $raw) {
                $lead = new Lead();
                $lead->loadInRaw($raw);
                $leads[$lead->getId()] = $lead;
            }
        }
        return $leads;
    }

    /**
     * @param string $directory
     * @throws AmoWrapException
     */
    public function backup($directory)
    {
        $this->createBackupFile($directory, 'contacts.backup', $this->getContactsList(null, 0,
            0, array(), null, true));
        $this->createBackupFile($directory, 'leads.backup', $this->getLeadsList(null, 0, 0,
            array(), null, true));
        $this->createBackupFile($directory, 'company.backup', $this->getCompanyList(null, 0, 0,
            array(), null, true));
        $this->createBackupFile($directory, 'tasks.backup', $this->getTasksList(null, 0, 0,
            array(), null, true));
        $this->createBackupFile($directory, 'notes-contacts.backup', $this->notesContactList(null,
            0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-leads.backup', $this->getNotesLeadList(null, 0,
            0, array(), null, true));
        $this->createBackupFile($directory, 'notes-company.backup', $this->getNotesCompanyList(null,
            0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-tasks.backup', $this->getNotesTaskList(null, 0,
            0, array(), null, true));
    }

    /**
     * @param string $directory
     * @param string $nameFile
     * @param mixed $var
     */
    private function createBackupFile($directory, $nameFile, $var)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $f = fopen("$directory/$nameFile", 'w+');
        fwrite($f, serialize($var));
        fclose($f);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Contact[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getContactsList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                    \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param string $type
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Company[]|Contact[]|Lead[]|\stdClass[]
     * @throws AmoWrapException
     */
    private function getList($type, $query, $limit, $offset, $responsibleUsersIdOrName, \DateTime $modifiedSince = null,
                             $isRaw)
    {
        $offset = (int)$offset;
        $limit = (int)$limit;
        switch ($type) {
            case 'Company':
                $className = $type;
                $typeForUrlType = 'company';
                break;
            case 'Contact':
                $className = $type;
                $typeForUrlType = 'contact';
                break;
            case 'Lead':
                $className = $type;
                break;
            case 'Note-Contact':
                $className = 'Note';
                $typeForUrlType = 'contact';
                break;
            case 'Note-Lead':
                $className = 'Note';
                $typeForUrlType = 'lead';
                break;
            case 'Note-Company':
                $className = 'Note';
                $typeForUrlType = 'company';
                break;
            case 'Note-Task':
                $className = 'Note';
                $typeForUrlType = 'task';
                break;
            case 'Task':
                $className = $type;
                break;
        }
        if (isset($className)) {
            $typeObj = "AmoCRM\\$className";
            $config = new Config();
            $typeForUrl = $config->{strtolower($className)}['url'];
            $url = "api/v2/$typeForUrl?";
            if (!empty($query)) {
                $url .= "&query=$query";
            }
            if (!empty($typeForUrlType)) {
                $url .= "&type=$typeForUrlType";
            }
            if (!empty($responsibleUsersIdOrName)) {
                if (is_array($responsibleUsersIdOrName)) {
                    foreach ($responsibleUsersIdOrName as $responsibleUserIdOrName) {
                        $responsibleUserId = AmoCRM::$info->getUserIdFromIdOrName($responsibleUserIdOrName);
                        $url .= "&responsible_user_id[]=$responsibleUserId";
                    }
                } else {
                    $responsibleUserId = AmoCRM::$info->getUserIdFromIdOrName($responsibleUsersIdOrName);
                    $url .= "&responsible_user_id=$responsibleUserId";
                }
            }
            $totalCount = $limit;
            $isNext = true;
            $result = array();
            $i = 0;
            while ($isNext) {
                $i++;
                if ($i > 15)
                    break;
                if ($totalCount > 500 || $limit == 0) {
                    $requestLimit = 500;
                } else {
                    $requestLimit = $totalCount;
                }
                $res = AmoCRM::cUrl($url . "&limit_rows=$requestLimit&limit_offset=$offset", null, $modifiedSince);
                if ($res === null) {
                    break;
                } else {
                    $result = array_merge($result, $res->_embedded->items);
                    if ($limit != 0) {
                        $totalCount -= count($res->_embedded->items);
                        if ($totalCount <= 0) {
                            break;
                        }
                    }
                    $offset += 500;
                }
            }
            if ($isRaw) {
                return $result;
            } else {
                $baseObjects = array();
                foreach ($result as $baseRaw) {
                    /** @var Company|Contact|Lead|Note|Task $baseObj */
                    $baseObj = new $typeObj();
                    $baseObj->loadInRaw($baseRaw);
                    $baseObjects[] = $baseObj;
                }
                return $baseObjects;
            }
        }
        return array();
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Lead[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getLeadsList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                 \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Company[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getCompanyList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                   \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Task[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getTasksList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                 \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Note[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getNotesContactList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                     \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Note[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getNotesLeadList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                     \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Note[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getNotesCompanyList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                        \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array|string|int $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Note[]|\stdClass[]
     * @throws AmoWrapException
     */
    public function getNotesTaskList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(),
                                     \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }
}