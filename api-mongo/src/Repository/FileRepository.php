<?php

namespace App\Repository;

use App\Document\File;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class FileRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function add(File $entity): File
    {
        $this->getDocumentManager()->persist($entity);

        $this->getDocumentManager()->flush();

        return $entity;
    }
}
