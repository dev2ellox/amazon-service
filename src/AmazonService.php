<?php

namespace ElxDigital\AmazonService;

use Exception;
use ElxDigital\AmazonService\Helpers\AWSHeaderCalculator;

class AmazonService
{
    /** @var string */
    private static $endpoint;
    /** @var mixed */
    private static $params = '';
    /** @var string */
    private static $query = "";
    /** @var string */
    private static $host;
    /** @var mixed */
    private static $callback;
    /** @var AWSHeaderCalculator */
    private static $AWSHeaderCalculator;

    public function __construct($host, $accessKey, $secretKey, $region = 'auto')
    {
        self::$AWSHeaderCalculator = new AWSHeaderCalculator(
            $host,
            $accessKey,
            $secretKey,
            $region
        );

        self::$host = $host;
    }

    public static function setEndpoint($endpoint)
    {
        self::$endpoint = $endpoint;
        self::$AWSHeaderCalculator->setUri($endpoint);
    }

    /**
     * @return mixed
     */
    public static function getCallback()
    {
        return self::$callback;
    }

    /**
     * @param mixed $params
     */
    public static function setParams($params)
    {
        self::$params = $params;
        self::$AWSHeaderCalculator->setPayload(self::$params);
    }

    public static function setQuery(array $query)
    {
        self::$query = '?' . http_build_query($query);
        self::$AWSHeaderCalculator->setQuery($query);
    }

    public static function setService($service)
    {
        self::$AWSHeaderCalculator->setService($service);
    }

    public static function getHost()
    {
        return self::$host;
    }

    public static function addHeader($key, $value)
    {
        self::$AWSHeaderCalculator->addHeader($key, $value);
    }

    /**
     * @throws Exception
     */
    public static function get()
    {
        if (empty(self::$endpoint)) {
            throw new Exception("Endpoint is required");
        }

        self::$AWSHeaderCalculator->setMethod("GET");
        $uri = "https://" . self::$host . self::$endpoint . self::$query;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => self::$AWSHeaderCalculator->generateAuthorizationHeader(),
        ]);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpStatus >= 200 && $httpStatus < 300) {
            $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            self::$callback = json_decode($json);
        } else {
            self::$callback = false;
        }

        curl_close($curl);
    }

    /**
     * @throws Exception
     */
    public static function put()
    {
        if (empty(self::$endpoint)) {
            throw new Exception("Endpoint is required");
        }

        self::$AWSHeaderCalculator->setMethod("PUT");
        $uri = "https://" . self::$host . self::$endpoint . self::$query;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => self::$params,
            CURLOPT_HTTPHEADER => self::$AWSHeaderCalculator->generateAuthorizationHeader(),
        ]);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpStatus < 200 || $httpStatus >= 300) {
            var_dump([
                'http_status' => $httpStatus,
                'response' => $response,
                'curl_error' => curl_error($curl)
            ]);
            die('Debug Cloudflare R2 - resposta da API');
        }

        self::$callback = ($httpStatus >= 200 && $httpStatus < 300);

        curl_close($curl);
    }

    /**
     * @throws Exception
     */
    public static function delete()
    {
        if (empty(self::$endpoint)) {
            throw new Exception("Endpoint is required");
        }

        self::$AWSHeaderCalculator->setMethod("DELETE");
        $uri = "https://" . self::$host . self::$endpoint . self::$query;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => self::$AWSHeaderCalculator->generateAuthorizationHeader(),
        ]);

        curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        self::$callback = ($httpStatus >= 200 && $httpStatus < 300);

        curl_close($curl);
    }
}