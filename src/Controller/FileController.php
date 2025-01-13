<?php

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Service\S3ClientService;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class FileController extends AbstractController
{
    private S3ClientService $s3ClientService;
    private FileRepository $fileRepository;

    public function __construct(S3ClientService $s3ClientService, FileRepository $fileRepository)
    {
        $this->s3ClientService = $s3ClientService;

        $this->fileRepository = $fileRepository;
    }

    #[Route('/file', name: 'app_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $s3Client = $this->s3ClientService->getClient();

        $contents = $s3Client->listObjectsV2([
            'Bucket' => $this->getParameter('aws.bucket_name')
        ]);

        dd($contents['Content']);

        return $this->json([]);
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $name = $request->request->get('name');
        $size = $request->request->get('size');
        $type = $request->request->get('type');

        $fileKey = uniqid(more_entropy: true) . '-' . $name;

        $s3 = $this->s3ClientService->getClient();

        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => $this->getParameter('aws.bucket_name'),
            'Key' => $fileKey,
            'ContentType' => $type
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        $signedUrl = (string) $request->getUri();

        $file = new File($name, $size, $type, $fileKey);

        $file = $this->fileRepository->add($file);

        return $this->json([
            'signedUrl' => $signedUrl,
            'file' => $file->getId()
        ]);
    }

}
