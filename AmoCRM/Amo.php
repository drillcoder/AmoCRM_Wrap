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
    private $domain;
    /**
     * @var string
     */
    private $userLogin;
    /**
     * @var string
     */
    private $userHash;
    /**
     * @var bool
     */
    private $authorization;
    /**
     * @var Info
     */
    private $info;

    /**
     * Amo constructor.
     * @param string $domain
     * @param string $userLogin
     * @param string $userHash
     */
    public function __construct($domain, $userLogin, $userHash)
    {
        $this->domain = $domain;
        $this->userLogin = $userLogin;
        $this->userHash = $userHash;
        $this->authorization = true;
        $this->authorization = $this->authorization();
        $this->info = new Info($this->loadInfo());
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
     * @return bool
     */
    private function authorization()
    {
        $user = array(
            'USER_LOGIN' => $this->userLogin,
            'USER_HASH' => $this->userHash
        );
        $link = 'auth.php?type=json';
        $res = $this->cUrl($link, 'post', $user);
        return $res->auth;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $data
     * @return mixed|false
     */
    private function cUrl($url, $method, $data = array())
    {
        if ($this->authorization) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
            curl_setopt($curl, CURLOPT_URL, 'https://' . $this->domain . '.amocrm.ru/private/api/' . $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if ($method == 'post') {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }
            curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out);
            return $response->response;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function isAuthorization()
    {
        return $this->authorization;
    }

    /**
     * @param Base $object
     * @return bool
     */
    public function save($object)
    {
        $objectData = $object->save();
        $res = $this->cUrl("v2/json/{$objectData['type']}/set", 'post', $objectData['data']);
        return $res->$objectData['type']->update[0]->id == $object->getId();
    }

    /**
     * @return false|mixed
     */
    private function loadInfo()
    {
        $link = 'v2/json/accounts/current';
        $res = $this->cUrl($link, 'get');
        return $res->account;
    }

    /**
     * @param $phone
     * @param $email
     * @return Contact[]|null
     */
    public function searchContact($phone, $email)
    {
        $link = 'v2/json/contacts/list?query=';
        $contacts = array();
        if (!empty($phone)) {
            $phone = $this->clearPhone($phone);
            $linkPhone = $link . $phone;
            $res = $this->cUrl($linkPhone, 'get');
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = Contact::loadInStdClass($this->info, $stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($email)) {
            $linkEmail = $link . $email;
            $res = $this->cUrl($linkEmail, 'get');
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = Contact::loadInStdClass($this->info, $stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($contacts))
            return $contacts;
        return null;
    }

    /**
     * @param int $id
     * @return Lead|false
     */
    public function loadLeadInId($id)
    {
        $link = "v2/json/leads/list?id=$id";
        $res = $this->cUrl($link, 'get');
        if ($res) {
            $lead = Lead::loadInStdClass($this->info, $res->leads[0]);
            return $lead;
        }
        return false;
    }

    /**
     * @param int $id
     * @return Contact|false
     */
    public function loadContactInId($id)
    {
        $link = "v2/json/contacts/list?id=$id";
        $res = $this->cUrl($link, 'get');
        if ($res) {
            $lead = Contact::loadInStdClass($this->info, $res->contacts[0]);
            return $lead;
        }
        return false;
    }
}