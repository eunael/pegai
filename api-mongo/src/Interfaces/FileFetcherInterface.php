<?php

namespace App\Interfaces;

use App\Document\File;

interface FileFetcherInterface
{
    public function getPresignedUrl(File $file): string;
    public function getFileContent(File $file): array;
}
