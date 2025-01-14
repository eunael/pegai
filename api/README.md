# Pegaí

O Pegaí é um sistema que lhe permite fazer upload de arquivo e gerar um link de compartilhamento para você enviar para quem precisa ter acesso ao arquivo de forma fácil e descomplicada.

## API

Esse é o diretório do back-end do projeto. Aqui temos uma API feita com o framework PHP Synfony.

A API tem dois endpoints.
| Endpoint  | Método | Descrição |
| --------  | ------ | --------- |
| /upload   | POST   | Retorna o ID do arquivo e uma url assinada para fazer upload ao informar os seguintes das do arquivo: nome, tamanho, tipo do conteúdo. |
| /download/{id} | GET    | Passe o ID do arquivo e retornará uma url assinada para buscar o arquivo do storage. |

Todas as URL assinada tem validade de **5 minuto** antes de expirarem.

## Como executar o projeto

Primeiro, faça o clone desse repositório, entre no diretório `api` e siga os passos.

1. Crie o arquivo `.env.local` e cole o seguinte trecho:
```
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=
###< symfony/framework-bundle ###

DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"

AWS_BUCKET_NAME=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_ENDPOINT=
```

2. Instale as dependências
```
composer install
```

3. Configure o banco de dados
```
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

4. Configure o Cloudflare R2
- Crie um bucket
- Gere um API token do tipo "Leitura/gravação para objeto", aplique para um bucket específico e gere
- Com os dados do token e o nome do bucket, preecha as suas variáveis de ambientes AWS_BUCKET_NAME, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_ENDPOINT

5. Suba o serve da aplicação
```
php -S localhost:8000 -t public/
```

6. Agora é só testar!
