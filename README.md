# lumen-jwt

JWT auth guard for Lumen 5.4

## Installation

```bash
$ composer require gboyegadada/lumen-jwt
```

## Setup

```php
# bootstrap/app.php

// 1. Uncomment next 2 lines...
$app->withFacades();
$app->withEloquent();

// 2. Uncomment next 3 lines...
$app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
]);

// 3. Register Auth Service Provider
$app->register(Yega\Auth\JWTAuthServiceProvider::class);

```

## Configure

```text
# .env

JWT_KEY=XXXXXXXXXXXXXXXXXXXXX
JWT_EXPIRE_AFTER=7200
JWT_ISSUER=myappname-or-domain

```
