<?php

/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 21.07.17
 * Time: 17:11
 */

namespace AmoCRM;

use AmoCRM\Helpers\Info;

/**
 * Class Amo
 * @package AmoCRM
 * @version Version 5.1
 */
class Amo
{
    /**
     * Version Wrap
     */
    const VERSION = '5.1';
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
    public static $authorization;
    /**
     * @var Info
     */
    public static $info;

    /**
     * Amo constructor.
     * @param $domain
     * @param $userLogin
     * @param $userAPIKey
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
        if (file_exists(__DIR__ . '/cookie.txt')) {
            self::$authorization = $res->response->auth;
            if (self::$authorization) {
                $res = Amo::cUrl('api/v2/account?with=custom_fields,users,pipelines,task_types');
                self::$info = new Info($res->_embedded);
            }
        } else {
            echo 'Недостаточно прав для создания файлов!';
            self::$authorization = false;
        }
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
     * @return string
     */
    public static function getVersion()
    {
        return Amo::VERSION;
    }

    /**
     * @param string $url
     * @param array $data
     * @param \DateTime|null $modifiedSince
     * @param bool $ajax
     * @return mixed|null
     */
    public static function cUrl($url, $data = array(), $modifiedSince = null, $ajax = false)
    {
        if (self::$authorization) {
            $url = 'https://' . self::$domain . '.amocrm.ru/' . $url;
            $isUnsorted = stripos($url, 'incoming_leads') !== false;
            if ($isUnsorted) {
                $url .= '?login=' . self::$userLogin . '&api_key=' . self::$userAPIKey;
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
            curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out);
            if ($ajax) {
                return $response;
            }
            if ($response)
                return $response;
        } else {
            echo 'Необходима авторизация в ЦРМ';
        }
        return null;
    }

    /**
     * @return boolean
     */
    public function isAuthorization()
    {
        return self::$authorization;
    }

    /**
     * @param $phone
     * @param $email
     * @return Contact[]|null
     */
    public function searchContact($phone, $email = null)
    {
        $link = 'api/v2/contacts/?query=';
        $contacts = array();
        if (!empty($phone)) {
            $phone = self::clearPhone($phone);
            $linkPhone = $link . $phone;
            $res = self::cUrl($linkPhone);
            if ($res) {
                foreach ($res->_embedded->items as $raw) {
                    $contact = new Contact();
                    $contact->loadInRaw($raw);
                    $contacts[$contact->getAmoId()] = $contact;
                }
            }
        }
        if (!empty($email)) {
            $linkEmail = $link . $email;
            $res = self::cUrl($linkEmail);
            if ($res) {
                foreach ($res->_embedded->items as $raw) {
                    $contact = new Contact();
                    $contact->loadInRaw($raw);
                    $contacts[$contact->getAmoId()] = $contact;
                }
            }
        }
        if (!empty($contacts))
            return $contacts;
        return null;
    }

    /**
     * @param string $query
     * @return Company[]|null
     */
    public function searchCompany($query)
    {
        $res = Amo::cUrl("api/v2/companies?query=$query");
        if (!empty($res)) {
            $companies = array();
            foreach ($res->_embedded->items as $raw) {
                $company = new Company();
                $company->loadInRaw($raw);
                $companies[$company->getAmoId()] = $company;
            }
            return $companies;
        }
        return null;
    }

    /**
     * @param string $query
     * @return Lead[]|null
     */
    public function searchLead($query)
    {
        $res = Amo::cUrl("api/v2/leads?query=$query");
        if (!empty($res)) {
            $leads = array();
            foreach ($res->_embedded->items as $raw) {
                $lead = new Lead();
                $lead->loadInRaw($raw);
                $leads[$lead->getAmoId()] = $lead;
            }
            return $leads;
        }
        return null;
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function contactsList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function leadsList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function companyList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function tasksList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function notesContactList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function notesLeadList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function notesCompanyList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    public function notesTaskList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null, $isRaw = false)
    {
        return $this->getList('Note-Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince, $isRaw);
    }

    /**
     * @param string $type
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @param bool $isRaw
     * @return Base[]|\stdClass[]|false|null
     */
    private function getList($type, $query, $limit, $offset, $responsibleUsersIdOrName, \DateTime $modifiedSince = null, $isRaw = false)
    {
        $offset = (int)$offset;
        $limit = (int)$limit;
        switch ($type) {
            case 'Contact':
                $class = $type;
                $typeForUrl = 'contacts';
                $typeForUrlType = 'contact';
                break;
            case 'Lead':
                $class = $type;
                $typeForUrl = 'leads';
                break;
            case 'Company':
                $class = $type;
                $typeForUrl = 'company';
                $typeForUrlType = 'company';
                break;
            case 'Task':
                $class = $type;
                $typeForUrl = 'tasks';
                break;
            case 'Note-Contact':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'contact';
                break;
            case 'Note-Lead':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'lead';
                break;
            case 'Note-Company':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'company';
                break;
            case 'Note-Task':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'task';
                break;
        }
        if (isset($typeForUrl) && isset($class)) {
            $typeObj = "AmoCRM\\$class";
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
                        $responsibleUserId = Amo::$info->getUserIdFromIdOrName($responsibleUserIdOrName);
                        $url .= "&responsible_user_id[]=$responsibleUserId";
                    }
                } else {
                    $responsibleUserId = Amo::$info->getUserIdFromIdOrName($responsibleUsersIdOrName);
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
                $res = Amo::cUrl($url . "&limit_rows=$requestLimit&limit_offset=$offset", null, $modifiedSince);
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
            if (empty($result)) {
                return null;
            } else {
                if ($isRaw) {
                    return $result;
                } else {
                    $baseObjects = array();
                    foreach ($result as $baseRaw) {
                        /** @var Base $baseObj */
                        $baseObj = new $typeObj();
                        $baseObj->loadInRaw($baseRaw);
                        $baseObjects[] = $baseObj;
                    }
                    return $baseObjects;
                }
            }
        }
        return false;
    }

    /**
     * @param string $directory
     */
    public function backup($directory)
    {
        $this->createBackupFile($directory, 'contacts.backup', $this->contactsList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'leads.backup', $this->leadsList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'company.backup', $this->companyList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'tasks.backup', $this->tasksList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-contacts.backup', $this->notesContactList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-leads.backup', $this->notesLeadList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-company.backup', $this->notesCompanyList(null, 0, 0, array(), null, true));
        $this->createBackupFile($directory, 'notes-tasks.backup', $this->notesTaskList(null, 0, 0, array(), null, true));
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
}