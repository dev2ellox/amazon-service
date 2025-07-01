<?php

namespace ElxDigital\AmazonService\Helpers;

class AWSHeaderCalculator
{
    /** @var string */
    private $host;
    /** @var string */
    private $accessKey;
    /** @var string */
    private $secretKey;
    /** @var string */
    private $method = "GET";
    /** @var string */
    private $region = "auto";
    /** @var string */
    private $service = "s3";
    /** @var array */
    private $headers = [];
    /** @var int */
    private $timestamp;
    /** @var array */
    private $query = [];
    /** @var mixed */
    private $payload = [];
    /** @var string */
    private $uri = "/";

    private const ALGORITHM = "AWS4-HMAC-SHA256";

    public function __construct($host, $accessKey, $secretKey, $region = "auto")
    {
        $this->host = $host;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;
        $this->timestamp = time();
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setService($service)
    {
        $this->service = $service;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function setUri($uri)
    {
        $this->uri = !empty($uri) ? $uri : "/";
    }

    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    private function uriEncode($uri)
    {
        return implode("/", array_map("rawurlencode", explode("/", $uri)));
    }

    private function trim($string)
    {
        return trim($string);
    }

    private function lowercase($string)
    {
        return strtolower($string);
    }

    private function sha256hash($string, $isBinary = false)
    {
        return hash("sha256", $string, $isBinary);
    }

    private function hmacSHA256($string, $key, $isBinary = false)
    {
        return hash_hmac("sha256", $string, $key, $isBinary);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    private function getISO8601DateTime()
    {
        return gmdate('Ymd\THis\Z', $this->timestamp);
    }

    private function getISO8601Date()
    {
        return gmdate('Ymd', $this->timestamp);
    }

    private function createSignedHeaders()
    {
        unset($this->headers['Authorization']);
        unset($this->headers['Host']);
        unset($this->headers['X-Amz-Content-Sha256']);
        unset($this->headers['X-Amz-Date']);

        $this->headers['Host'] = $this->host;
        if (is_string($this->payload)) {
            $this->headers['X-Amz-Content-Sha256'] = $this->sha256hash($this->payload);
        } else {
            $this->headers['X-Amz-Content-Sha256'] = $this->sha256hash("");
        }
        $this->headers['X-Amz-Date'] = $this->getISO8601DateTime();
    }

    private function createCanonicalQuery()
    {
        if (empty($this->query)) return "";
        ksort($this->query);

        $canonicalQuery = [];
        foreach ($this->query as $key => $value) {
            $key = $this->uriEncode($key);
            $value = $this->uriEncode($value);
            $canonicalQuery[$key] = $value;
        }

        return http_build_query($canonicalQuery);
    }

    private function createCanonicalHeaders()
    {
        if (empty($this->headers)) return "";
        ksort($this->headers);

        $canonicalHeadersString = "";
        foreach ($this->headers as $key => $value) {
            $key = $this->lowercase($this->trim($key));
            $value = $this->trim($value);
            $canonicalHeadersString .= $key . ":" . $value . "\n";
        }

        return $canonicalHeadersString;
    }

    private function createCanonicalRequest()
    {
        $httpVerb = $this->method;
        $canonicalUri = $this->uriEncode($this->uri);
        $canonicalQuery = $this->createCanonicalQuery();
        $canonicalHeaders = $this->createCanonicalHeaders();
        $signedHeaders = implode(";", array_map("strtolower", array_keys($this->headers)));
        $hashedPayload = $this->headers['X-Amz-Content-Sha256'];

        return "{$httpVerb}\n{$canonicalUri}\n{$canonicalQuery}\n{$canonicalHeaders}\n{$signedHeaders}\n{$hashedPayload}";
    }

    private function createSignature()
    {
        $dateKey = $this->hmacSHA256($this->getISO8601Date(), "AWS4{$this->secretKey}", true);
        $dateRegionKey = $this->hmacSHA256($this->region, $dateKey, true);
        $dateRegionServiceKey = $this->hmacSHA256($this->service, $dateRegionKey, true);

        return $this->hmacSHA256("aws4_request", $dateRegionServiceKey, true);
    }

    private function calculateSignature()
    {
        $canonicalRequest = $this->createCanonicalRequest();
        $stringToSign = self::ALGORITHM . "\n{$this->getISO8601DateTime()}\n{$this->getISO8601Date()}/{$this->region}/{$this->service}/aws4_request\n{$this->sha256hash($canonicalRequest)}";
        $signingKey = $this->createSignature();

        return $this->hmacSHA256($stringToSign, $signingKey);
    }

    public function generateAuthorizationHeader()
    {
        $this->timestamp = time();
        $this->createSignedHeaders();

        $signature = $this->calculateSignature();
        $signedHeaders = implode(";", array_map("strtolower", array_keys($this->headers)));
        $value = self::ALGORITHM . " Credential={$this->accessKey}/{$this->getISO8601Date()}/{$this->region}/{$this->service}/aws4_request, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $this->addHeader("Authorization", $value);

        return array_map(function ($key, $value) {
            return "{$key}: {$value}";
        }, array_keys($this->headers), $this->headers);
    }
}