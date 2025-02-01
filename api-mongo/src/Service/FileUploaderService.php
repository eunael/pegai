<?php

namespace App\Service;

use App\Interfaces\CloudflareClientInterface;
use App\Interfaces\FileUploaderInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileUploaderService implements FileUploaderInterface
{
    public function __construct(
        protected string $url,
        protected string $key,
        protected readonly CloudflareClientInterface $cloudflareClient,
        protected readonly ParameterBagInterface $params
    ) {
    }
    public function uploadFile(string $name, string $size, string $type): void
    {
        $this->key = Uuid::uuid4() . '-' . $name;

        $s3 = $this->cloudflareClient->getClient();
        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => $this->params->get('aws.bucket_name'),
            'Key' => $this->key,
            'ContentType' => $type
        ]);
        
        $presignedRequest = $s3->createPresignedRequest($cmd, '+60 seconds');

        $this->url = (string) $presignedRequest->getUri();
    }
    public function getPresignedUrl(): string
    {
        return $this->url;
    }
    public function getFileKey(): string
    {
        return $this->key;
    }
}
