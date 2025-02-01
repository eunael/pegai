<?php

namespace App\Service;

use App\Document\File;
use App\Interfaces\CloudflareClientInterface;
use App\Interfaces\FileFetcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileFetcherService implements FileFetcherInterface
{
    public function __construct(
        protected readonly CloudflareClientInterface $cloudflareClient,
        protected readonly HttpClientInterface $httpClient,
        protected readonly ParameterBagInterface $params
    ) {
    }

    public function getPresignedUrl(File $file): string
    {
        $fileKey = $file->getKey();

        $s3 = $this->cloudflareClient->getClient();

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $this->params->get('aws.bucket_name'),
            'Key' => $fileKey
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        return (string) $request->getUri();
    }

    public function getFileContent(File $file): array
    {
        $signedUrl = $this->getPresignedUrl($file);

        $response = $this->httpClient->request('GET', $signedUrl);

        $content = $response->getContent();
        $contentType = $response->getHeaders()['content-type'][0] ?? 'application/pdf';

        return [$content, $contentType];
    }
}
