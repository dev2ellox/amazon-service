<?php

namespace ElxDigital\AmazonService\Services\S3;

use ElxDigital\AmazonService\AmazonService;
use Exception;
use GuzzleHttp\Psr7\Utils;

class File
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!AmazonService::getHost()) {
            throw new Exception("AmazonService instance not found");
        }

        AmazonService::setService('s3');
    }

    /**
     * @param string $bucketName
     * @return mixed
     * @throws Exception
     */
    public function listObjects($bucketName)
    {
        if (!$bucketName) {
            throw new Exception("Bucket name is required");
        }

        AmazonService::setEndpoint("/{$bucketName}");
        AmazonService::setParams('');
        AmazonService::get();

        return AmazonService::getCallback();
    }

    /**
     * @param string $bucketName
     * @param string $objectName
     * @param string $objectContent
     * @return bool
     * @throws Exception
     */
    public function createObject($bucketName, $objectName, $objectContent)
    {
        if (!$bucketName) {
            throw new Exception("Bucket name is required");
        }

        if (!$objectName) {
            throw new Exception("Object name is required");
        }

        if (!$objectContent) {
            throw new Exception("Object content is required");
        }

        $context = $objectContent;

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_buffer($fileInfo, $objectContent);
        finfo_close($fileInfo);

        AmazonService::addHeader("Content-Type", $fileType);
        AmazonService::setEndpoint("/{$bucketName}/{$objectName}");
        AmazonService::setParams($context);
        AmazonService::put();

        return AmazonService::getCallback();
    }

    /**
     * @param string $bucketName
     * @param string $objectName
     * @return bool
     * @throws Exception
     */
    public function deleteObject($bucketName, $objectName)
    {
        if (!$bucketName) {
            throw new Exception("Bucket name is required");
        }

        if (!$objectName) {
            throw new Exception("Object name is required");
        }

        AmazonService::setEndpoint("/{$bucketName}/{$objectName}");
        AmazonService::setParams('');
        AmazonService::delete();

        return AmazonService::getCallback();
    }
}