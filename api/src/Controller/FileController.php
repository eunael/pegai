<?php

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Service\FileFetcherService;
use App\Service\S3ClientService;
use Dompdf\Dompdf;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileController extends AbstractController
{
    private S3ClientService $s3ClientService;
    private FileRepository $fileRepository;
    private FileFetcherService $fileFetcher;

    public function __construct(
        S3ClientService $s3ClientService,
        FileRepository $fileRepository,
        FileFetcherService $fileFetcher,
    ) {
        $this->s3ClientService = $s3ClientService;

        $this->fileRepository = $fileRepository;

        $this->fileFetcher = $fileFetcher;
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST', 'OPTIONS'])]
    public function upload(Request $request): JsonResponse
    {
        $request = $request->toArray();

        $name = $request['name'];
        $size = $request['size'];
        $type = $request['type'];

        $fileKey = uniqid(more_entropy: true) . '-' . $name;

        $s3 = $this->s3ClientService->getClient();

        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => $this->getParameter('aws.bucket_name'),
            'Key' => $fileKey,
            'ContentType' => $type
        ]);

        $presignedRequest = $s3->createPresignedRequest($cmd, '+5 minutes');

        $signedUrl = (string) $presignedRequest->getUri();

        $file = new File($name, $size, $type, $fileKey);

        $file = $this->fileRepository->add($file);

        return $this->json([
            'signedUrl' => $signedUrl,
            'file' => $file->getId()
        ]);
    }

    #[Route('/preview/{file}', name: 'file_preview', methods: ['GET'])]
    public function preview(File $file)
    {
        // TODO: tratar outros tipos de arquivos além do PDF

        try {
            [$content, $contentType]  = $this->fileFetcher->getContent($file);

            return new Response($content, Response::HTTP_OK, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ]);
        } catch (\Exception $e) {
            return new Response('Erro ao buscar o arquivo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
