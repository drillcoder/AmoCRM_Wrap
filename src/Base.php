<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 2019-01-04
 * Time: 01:13
 */

namespace DrillCoder\AmoCRM_Wrap;

use DateTime;
use DrillCoder\AmoCRM_Wrap\Helpers\Config;
use stdClass;

/**
 * Class Base
 * @package DrillCoder\AmoCRM_Wrap
 */
abstract class Base
{
    /**
     * @var string
     */
    protected static $domain;

    /**
     * @var string
     */
    protected static $userLogin;

    /**
     * @var string
     */
    protected static $userAPIKey;

    /**
     * @var bool
     */
    protected static $authorization = false;

    /**
     * @param string        $url
     * @param array         $data
     * @param DateTime|null $modifiedSince
     * @param bool          $ajax
     *
     * @return stdClass|null
     *
     * @throws AmoWrapException
     */
    protected static function cUrl($url, $data = array(), DateTime $modifiedSince = null, $ajax = false)
    {
        $url = 'https://' . self::$domain . '.amocrm.ru/' . $url;
        $isUnsorted = mb_stripos($url, 'incoming_leads') !== false;
        if ($isUnsorted) {
            $url .= '&login=' . self::$userLogin . '&api_key=' . self::$userAPIKey;
        } else {
            if (mb_strpos($url, '?') === false) {
                $url .= '?';
            }
            $url .= '&USER_LOGIN=' . self::$userLogin . '&USER_HASH=' . self::$userAPIKey;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'DrillCoder AmoCRM_Wrap/v' . AmoCRM::VERSION);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        $headers = array();
        if (count($data) > 0) {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($ajax) {
                $headers[] = 'X-Requested-With: XMLHttpRequest';
                $dataStr = $data;
            } elseif ($isUnsorted) {
                $dataStr = http_build_query($data);
            } else {
                $headers[] = 'Content-Type: application/json';
                $dataStr = json_encode($data);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $dataStr);
        }
        if ($modifiedSince !== null) {
            $headers[] = 'IF-MODIFIED-SINCE: ' . $modifiedSince->format(DateTime::RFC1123);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $json = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($json);
        if (isset($result->response->error) || (isset($result->title) && $result->title === 'Error')) {
            $errorCode = isset($result->status) ? (int)$result->status : (int)$result->response->error_code;
            $errorMessage = isset(Config::$errors[$errorCode]) ? Config::$errors[$errorCode] : $result->response->error;

            throw new AmoWrapException($errorMessage, $errorCode);
        }

        return $result;
    }

    /**
     * @param string $var
     *
     * @return string
     */
    protected static function onlyNumbers($var)
    {
        return preg_replace('/\D/', '', $var);
    }
}