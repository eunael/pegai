<?php

namespace App\Controller;

use App\Entity\File;
use App\Interfaces\FileFetcherInterface;
use App\Interfaces\FileUploaderInterface;
use App\Repository\FileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FileController extends AbstractController
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly FileFetcherInterface $fileFetcher,
        private readonly FileUploaderInterface $fileUploader,
    ) {
    }

    #[Route('/file', name: "app_index_file", methods: ['GET'])]
    public function indexFile()
    {
        return $this->json(['oi']);
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST', 'OPTIONS'])]
    public function upload(Request $request): JsonResponse
    {
        $request = $request->toArray();
        $name = $request['name'];
        $size = $request['size'];
        $type = $request['type'];

        $this->fileUploader->uploadFile($name, $size, $type);
        $signedUrl = $this->fileUploader->getPresignedUrl();
        $fileKey = $this->fileUploader->getFileKey();

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
            [$content, $contentType]  = $this->fileFetcher->getFileContent($file);

            return new Response($content, Response::HTTP_OK, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ]);
        } catch (\Exception $e) {
            return new Response('Erro ao buscar o arquivo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
