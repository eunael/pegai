<?php

namespace App\Interfaces;

use Aws\S3\S3Client;

interface CloudflareClientInterface
{
    public function getClient(): S3Client;
}
