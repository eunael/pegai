<?php

namespace App\Service;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class S3ClientService
{
    private S3Client $s3Client;

    public function __construct(
        private string $bucketName,
        string $accessKeyId,
        string $secretAccessKey,
        string $endpoint
    )
    {
        $credentials = new Credentials($accessKeyId, $secretAccessKey);

        $this->s3Client = new S3Client([
            'region' => 'auto',
            'endpoint' => $endpoint,
            'version' => 'latest',
            'credentails' => $credentials
        ]);
    }

    public function getClient(): S3Client
    {
        return $this->s3Client;
    }
}