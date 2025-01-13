<?php
namespace App\Service;

use App\Entity\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileFetcherService
{
    private S3ClientService $s3Client;
    private HttpClientInterface $httpClient;
    private $params;

    public function __construct(
        S3ClientService $s3Client,
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->s3Client = $s3Client;
        $this->httpClient = $httpClient;
        $this->params = $params;
    }

    public function test()
    {
        return $this->params->get('aws.bucket_name');
    }

    public function getUrl(File $file): string
    {
        $fileKey = $file->getKey();

        $s3 = $this->s3Client->getClient();

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $this->params->get('aws.bucket_name'),
            'Key' => $fileKey
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        return (string) $request->getUri();
    }

    public function getContent(File $file): array
    {
        $signedUrl = $this->getUrl($file);

        $response = $this->httpClient->request('GET', $signedUrl);

        $content = $response->getContent();
        $contentType = $response->getHeaders()['content-type'][0] ?? 'application/pdf';

        return [$content, $contentType];
    }
}