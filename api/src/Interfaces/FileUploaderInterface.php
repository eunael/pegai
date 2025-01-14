<?php

namespace App\Interfaces;

interface FileUploaderInterface
{
    public function getFileKey(): string;
    public function getPresignedUrl(): string;
    public function uploadFile(string $name, string $size, string $type): void;
}
