<?php

namespace App\Controller;

use App\Document\File;
use App\DTO\FileDTO;
use App\Interfaces\FileFetcherInterface;
use App\Interfaces\FileUploaderInterface;
use App\Repository\FileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FileController extends AbstractController
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly FileFetcherInterface $fileFetcher,
        private readonly FileUploaderInterface $fileUploader,
        private readonly RateLimiterFactory $anonymousApiLimiter,
    ) {
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST', 'OPTIONS'])]
    public function upload(Request $request, ValidatorInterface $validator): JsonResponse
    {
        //region Validate Rate limite
        $limiter = $this->anonymousApiLimiter->create($request->getClientIp());

        if(false === $limiter->consume(1)->isAccepted()) {
            return $this->json([
                'message' => 'Too many request. Please try again soon.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        //endregion

        //region Validate request data
        $data = $request->toArray();

        $fileDTO = new FileDTO;
        $fileDTO->name = $data['name'] ?? null;
        $fileDTO->size = $data['size'] ?? null;
        $fileDTO->type = $data['type'] ?? null;

        $errors = $validator->validate($fileDTO);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return new JsonResponse(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }
        //endregion

        $this->fileUploader->uploadFile($fileDTO->name, $fileDTO->size, $fileDTO->type);
        $signedUrl = $this->fileUploader->getPresignedUrl();
        $fileKey = $this->fileUploader->getFileKey();

        $file = new File($fileDTO->name, $fileDTO->size, $fileDTO->type, $fileKey);
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

            // Tipos de áudio suportados
            $audioMimeTypes = [
                'audio/mpeg',  // MP3
                'audio/ogg',   // Ogg Vorbis
                'audio/wav',   // WAV
            ];

            if (in_array($contentType, $audioMimeTypes)) {
                // Cabeçalhos específicos para áudio
                return new Response($content, Response::HTTP_OK, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="' . $file->getKey() . '"',
                    'Accept-Ranges' => 'bytes', // Necessário para reprodução progressiva
                ]);
            }

            // Tipos de vídeo suportados
            $videoMimeTypes = [
                'video/mp4',  // MP4
                'video/webm', // WebM
                'video/ogg',  // Ogg
            ];

            if (in_array($contentType, $videoMimeTypes)) {
                // Cabeçalhos específicos para vídeos
                return new Response($content, Response::HTTP_OK, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="' . $file->getKey() . '"',
                    'Accept-Ranges' => 'bytes', // Necessário para reprodução de vídeos
                ]);
            }

            $inlineDisplayTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain',
                'application/json',
            ];
    
            $disposition = in_array($contentType, $inlineDisplayTypes)
                ? 'inline'
                : 'attachment';

            return new Response($content, Response::HTTP_OK, [
                'Content-Type' => $contentType,
                'Content-Disposition' => $disposition . '; filename="' . $file->getKey() . '"',
                'Accept-Ranges' => 'bytes',
            ]);
        } catch (\Exception $e) {
            return new Response('Erro ao buscar o arquivo: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
