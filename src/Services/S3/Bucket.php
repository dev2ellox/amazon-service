<?php

namespace ElxDigital\AmazonService\Services\S3;

use ElxDigital\AmazonService\AmazonService;
use Exception;

class Bucket
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
     * @return mixed
     * @throws Exception
     */
    public function listBuckets()
    {
        AmazonService::setEndpoint("/");
        AmazonService::setParams('');
        AmazonService::get();

        return AmazonService::getCallback();
    }

    /**
     * @param string $bucketName
     * @return bool
     * @throws Exception
     */
    public function createBucket($bucketName)
    {
        if (!$bucketName) {
            throw new Exception("Bucket name is required");
        }

        AmazonService::setEndpoint("/{$bucketName}");
        AmazonService::setParams('');
        AmazonService::put();

        return AmazonService::getCallback();
    }

    /**
     * @param string $bucketName
     * @return bool
     * @throws Exception
     */
    public function deleteBucket($bucketName)
    {
        if (!$bucketName) {
            throw new Exception("Bucket name is required");
        }

        AmazonService::setEndpoint("/{$bucketName}");
        AmazonService::setParams('');
        AmazonService::delete();

        return AmazonService::getCallback();
    }
}
