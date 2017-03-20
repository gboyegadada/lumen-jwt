# lumen-jwt

JWT auth guard for Lumen 5.4

## Install

```bash
$ composer require gboyegadada/lumen-jwt
```

## Setup

```php
# edit: bootstrap/app.php

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

```bash
$ mkdir config
$ cp vendor/laravel/lumen-framework/config/auth.php config/
```

```php
# edit: config/auth.php
/*
|--------------------------------------------------------------------------
| Authentication Guards
|--------------------------------------------------------------------------
| ........
|
*/

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users'
    ]
],

/*
|--------------------------------------------------------------------------
| User Providers
|--------------------------------------------------------------------------
| ..............
|
*/

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ]
],

```

## Configure

```text
# edit: .env

JWT_KEY=XXXXXXXXXXXXXXXXXXXXX
JWT_EXPIRE_AFTER=7200
JWT_ISSUER=myappname-or-domain

```

## Use

```php
# edit: routes/web.php

// Wrap protected routes with this...
$app->group(['middleware' => 'auth:api' ], function($app)  {
    // Protected route...
    $app->get('test', function (Request $request) use ($app) {
        return "Yayyy! I'm so safe! Not!"
    });
});

```
