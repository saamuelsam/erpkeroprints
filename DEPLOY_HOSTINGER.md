# Deploy na VPS Hostinger

Este projeto e um Laravel com MySQL, Nginx e Vite. O caminho mais simples na VPS e subir com Docker Compose.

## 1. Preparar a VPS

Entre por SSH na VPS:

```bash
ssh root@IP_DA_VPS
```

Instale Docker e o plugin do Compose:

```bash
apt update
apt install -y ca-certificates curl git unzip
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" > /etc/apt/sources.list.d/docker.list
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

## 2. Enviar o projeto

Se o projeto estiver em um repositorio Git, clone na VPS:

```bash
mkdir -p /var/www
cd /var/www
git clone URL_DO_REPOSITORIO masterprint
cd masterprint
```

Se nao estiver no Git, compacte o projeto local e envie por SFTP/SSH para `/var/www/masterprint`. Nao envie `vendor`, `node_modules` nem `.env`.

## 3. Criar o `.env` de producao

Na VPS:

```bash
cd /var/www/masterprint
cp .env.production.example .env
nano .env
```

Altere principalmente:

```dotenv
APP_URL=https://seudominio.com.br
DB_PASSWORD=uma_senha_forte
DB_ROOT_PASSWORD=outra_senha_forte
MAIL_*=dados_do_email_se_for_usar_envio
```

Gere a chave do Laravel e cole o resultado em `APP_KEY=` no arquivo `.env`:

```bash
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
nano .env
```

## 4. Subir a aplicacao

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

Abra:

```text
http://IP_DA_VPS
```

## 5. Apontar dominio e SSL

No painel da Hostinger, aponte o registro `A` do dominio para o IP da VPS.

Para SSL, uma opcao simples e instalar Certbot na VPS e colocar um proxy Nginx no host. Se preferir manter tudo em Docker, use Traefik ou Nginx Proxy Manager.

## 6. Atualizar depois

Quando fizer novas alteracoes:

```bash
cd /var/www/masterprint
git pull
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

## Comandos uteis

Ver logs:

```bash
docker compose -f docker-compose.prod.yml logs -f app nginx
```

Entrar no container:

```bash
docker compose -f docker-compose.prod.yml exec app sh
```

Backup do banco:

```bash
docker compose -f docker-compose.prod.yml exec db sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > backup.sql
```
