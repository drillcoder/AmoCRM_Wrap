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
 */
class Amo
{
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
    private static $userHash;
    /**
     * @var bool
     */
    private static $authorization;
    /**
     * @var Info
     */
    public static $info;

    /**
     * Amo constructor.
     * @param $domain
     * @param $userLogin
     * @param $userHash
     */
    public function __construct($domain, $userLogin, $userHash)
    {
        self::$domain = $domain;
        self::$userLogin = $userLogin;
        self::$userHash = $userHash;
        self::$authorization = true;
        $user = array(
            'USER_LOGIN' => $userLogin,
            'USER_HASH' => $userHash
        );
        $res = self::cUrl('private/api/auth.php?type=json', $user);
        if (file_exists(__DIR__ . '/cookie.txt')) {
            self::$authorization = $res->auth;
            if (self::$authorization) {
                self::$info = new Info(self::loadInfo());
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
        return (int)preg_replace("/[^0-9]/", '', $phone);
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
            if (stripos($url, 'unsorted') !== false) {
                $url .= '?login=' . self::$userLogin . '&api_key=' . self::$userHash;
            }
            $url = 'https://' . self::$domain . '.amocrm.ru/' . $url;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
            $headers = array();
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, true);
                if ($ajax) {
                    $headers[] = 'X-Requested-With: XMLHttpRequest';
                } else {
                    $headers[] = 'Content-Type: application/json';
                    $data = json_encode($data);
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
                return $response->response;
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
     * @return false|mixed
     */
    private function loadInfo()
    {
        $res = Amo::cUrl('private/api/v2/json/accounts/current');
        return $res->account;
    }

    /**
     * @param $phone
     * @param $email
     * @return Contact[]|null
     */
    public function searchContact($phone, $email = null)
    {
        $link = 'private/api/v2/json/contacts/list?query=';
        $contacts = array();
        if (!empty($phone)) {
            $phone = self::clearPhone($phone);
            $linkPhone = $link . $phone;
            $res = self::cUrl($linkPhone);
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = new Contact();
                    $contact->loadInStdClass($stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($email)) {
            $linkEmail = $link . $email;
            $res = self::cUrl($linkEmail);
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = new Contact();
                    $contact->loadInStdClass($stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($contacts))
            return $contacts;
        return null;
    }

    /**
     * @param string $query
     * @return Lead[]|null
     */
    public function searchLead($query)
    {
        $res = Amo::cUrl("
}private/api/v2/json/leads/list?query=$query");
        if (!empty($res)) {
            $leads = array();
            foreach ($res->leads as $stdClass) {
                $lead = new Lead();
                $lead->loadInStdClass($stdClass);
                $leads[$lead->getId()] = $lead;
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
     * @return Base[]|false|null
     */
    public function contactsList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function leadsList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function companyList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function tasksList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function notesContactList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Note-Contact', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function notesLeadList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Note-Lead', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function notesCompanyList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Note-Company', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param null $query
     * @param int $limit
     * @param int $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    public function notesTaskList($query = null, $limit = 500, $offset = 0, $responsibleUsersIdOrName = array(), \DateTime $modifiedSince = null)
    {
        return $this->getList('Note-Task', $query, $limit, $offset, $responsibleUsersIdOrName, $modifiedSince);
    }

    /**
     * @param string $type
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @param array $responsibleUsersIdOrName
     * @param \DateTime|null $modifiedSince
     * @return Base[]|false|null
     */
    private function getList($type, $query, $limit, $offset, $responsibleUsersIdOrName, \DateTime $modifiedSince = null)
    {
        switch ($type) {
            case 'Contact':
                $class = $type;
                $typeForUrl = 'contacts';
                $typeRes = $typeForUrl;
                $typeForUrlType = 'contact';
                break;
            case 'Lead':
                $class = $type;
                $typeForUrl = 'leads';
                $typeRes = $typeForUrl;
                break;
            case 'Company':
                $class = $type;
                $typeForUrl = 'company';
                $typeRes = 'contacts';
                $typeForUrlType = 'company';
                break;
            case 'Task':
                $class = $type;
                $typeForUrl = 'tasks';
                $typeRes = $typeForUrl;
                break;
            case 'Note-Contact':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'contact';
                $typeRes = $typeForUrl;
                break;
            case 'Note-Lead':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'lead';
                $typeRes = $typeForUrl;
                break;
            case 'Note-Company':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'company';
                $typeRes = $typeForUrl;
                break;
            case 'Note-Task':
                $class = 'Note';
                $typeForUrl = 'notes';
                $typeForUrlType = 'task';
                $typeRes = $typeForUrl;
                break;
        }
        if (isset($typeForUrl) && isset($typeRes) && isset($class)) {
            $typeObj = "AmoCRM\\$class";
            $url = "private/api/v2/json/$typeForUrl/list?limit_rows=$limit&limit_offset=$offset";
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
            $res = Amo::cUrl($url, null, $modifiedSince);
            if ($res === null) {
                return null;
            } else {
                $baseObjects = array();
                foreach ($res->$typeRes as $baseRaw) {
                    /** @var Base $baseObj */
                    $baseObj = new $typeObj();
                    $baseObj->loadInStdClass($baseRaw);
                    $baseObjects[] = $baseObj;
                }
                return $baseObjects;
            }
        }
        return false;
    }
}