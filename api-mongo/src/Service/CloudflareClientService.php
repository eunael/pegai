<?php

namespace App\Service;

use App\Interfaces\CloudflareClientInterface;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class CloudflareClientService implements CloudflareClientInterface
{
    private S3Client $s3Client;

    public function __construct(
        private readonly string $bucketName,
        string $accessKeyId,
        string $secretAccessKey,
        string $endpoint
    ) {
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
