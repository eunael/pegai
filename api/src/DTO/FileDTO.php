<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class FileDTO
{
    #[Assert\NotBlank(message: "O nome do arquivo é obrigatório")]
    #[Assert\Length(
        max: 255,
        maxMessage: "O nome do arquivo deve ter no máximo {{ limit }} caracteres"
    )]
    public ?string $name;

    #[Assert\NotBlank(message: "O tamanho do arquivo é obrigatório")]
    #[Assert\LessThanOrEqual(
        value: 209715200,
        message: "O tamanho do arquivo não pode exceder 200MB"
    )]
    public ?int $size;

    #[Assert\NotBlank(message: "O tipo MIME do arquivo é obrigatório")]
    #[Assert\Regex(
        pattern: '/\w+\/[-+.\w]+/',
        message: "O tipo MIME deve seguir o padrão correto"
    )]
    public ?string $type;

    #[Assert\Callback]
    public function validateMimeType($context)
    {
        $invalidMimeTypes = [
            'application/x-msdownload',
            'application/x-msdos-program',
            'application/x-shellscript',
            'application/x-sh',
            'application/x-cgi',
            'application/java-archive',
            'application/x-executable',
            'application/x-java-archive',
            'application/x-ms-dos-executable',
            'application/octet-stream',
            'application/x-httpd-cgi',
            'application/x-diskcopy'
        ];

        if (in_array($this->type, $invalidMimeTypes)) {
            $context->buildViolation("O tipo MIME do arquivo não é permitido")
                ->atPath('type')
                ->addViolation();
        }
    }
}