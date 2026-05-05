# ProjectCenter

Application de gestion de projets type Kanban / Scrum (projet fil rouge YNOV).

## Stack

- Backend : PHP 8.4 + Symfony 8 + API Platform 4 + Doctrine
- Base de données : PostgreSQL 16
- Auth : JWT (LexikJWTAuthenticationBundle)
- Frontend : Angular (paquet séparé)

## Modèle

`User → Project → Sprint → Task`. Chaque ressource n'est accessible qu'à son propriétaire.

## Endpoints principaux

- `POST /api/register` — création d'un compte
- `POST /api/login_check` — récupération d'un token JWT
- `GET /api/me` — utilisateur courant
- `GET|POST|PATCH|DELETE /api/projects`
- `GET|POST|PATCH|DELETE /api/sprints`
- `GET|POST|PATCH|DELETE /api/tasks`

Doc Swagger interactive : `/api/docs`.

## Installation (backend)

```bash
cd app/backend
cp .env .env.local   # ajuster DATABASE_URL et JWT_PASSPHRASE
composer install
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve
```

## Tests

```bash
cd app/backend
vendor/bin/phpunit
```
