<?php

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Service\S3ClientService;
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
    private HttpClientInterface $httpClient;

    public function __construct(S3ClientService $s3ClientService, FileRepository $fileRepository, HttpClientInterface $httpClient)
    {
        $this->s3ClientService = $s3ClientService;

        $this->fileRepository = $fileRepository;
        
        $this->httpClient = $httpClient;
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

    #[Route('/download/{file}', name: 'app_download', methods: ['GET'])]
    public function download(File $file): Response
    {
        $fileKey = $file->getKey();

        $s3 = $this->s3ClientService->getClient();

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $this->getParameter('aws.bucket_name'),
            'Key' => $fileKey
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');
        
        // retorna uma url de download, mas eu quero fazer um preview
        $signedUrl = (string) $request->getUri();

        #####
        try {
            $response = $this->httpClient->request('GET', $signedUrl);
            // conteudo do arquivo, mas qnd é pdf retorna o binário
            $content = $response->getContent();
            $contentType = $response->getHeaders()['content-type'][0] ?? 'application/pdf';

            // Retorne o conteúdo com o cabeçalho adequado para exibição
            return new Response($content, Response::HTTP_OK, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ]);
        } catch (\Exception $e) {
            return new Response('Erro ao buscar o arquivo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'signedUrl' => $signedUrl
        ]);
    }
}
