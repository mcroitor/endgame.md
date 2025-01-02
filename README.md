# endgame.md

The `https://endgame.md` source.

For starting need to have PHP installed. Tested on PHP 8.1, necessary extensions are:

- `gd`
- `pdo_sqlite`

Changed to be started with Docker Compose.

## Local run

```bash
php -S localhost:8000 -t site
```

## Docker Compose

Site can be run with Docker Compose.

```bash
docker-compose up -d
```
