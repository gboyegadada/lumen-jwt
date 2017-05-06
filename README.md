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
JWT_INCLUDE=id,email,avatar,full_name,first_name,last_name

```

## Use (server side): Lumen

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


```php
# edit: app/Http/Controllers/AuthController.php

/**
 * post: /login
 * @return string
 */
public function postLogin(Request $req)
{

    $credentials = $req->only('email', 'password');

    /**
     * Token on success | false on fail
     *
     * @var string | boolean
     */
    $token = Auth::attempt($credentials);

    return ($token !== false)
            ? json_encode(['jwt' => $token])
            : response('Unauthorized.', 401);

}

```

## Use (client side): JavaScript

# 1. Login to get a token:

```javascript

const url = 'http://localhost:8000/login';

// Login credentials
let data = {
    email: 'boyega@gmail.com',
    password: 'areacode234'
}

// Create our request constructor with all the parameters we need
var request = new Request(url, {
    method: 'POST',
    body: data
});

fetch(request)
.then(reponse) {
  if(response.ok) {
    return response.json();
  }
  throw new Error('Network response was not ok.');
}
.then(function(json) {
    localStorage.setItem('token', json.jwt);
});

```

# 2. Make subsequent requests using our JWT token:

```javascript

const url = 'http://localhost:8000/test';

// Add our token in the Authorization header
var token = localStorage.getItem('token');
var myHeaders = new Headers();
myHeaders.append("Authorization", "Bearer "+token);

/* !! important: make sure there is [:space:] between "Bearer" and token !! */

// Create our request constructor with all the parameters we need
var request = new Request(url, {
    method: 'POST',
    body: data,
    headers: myHeaders    
});

fetch(request)
.then(reponse) {
  if(response.ok) {
    return response.json();
  }
  throw new Error('Network response was not ok.');
}
.then(function(json) {
    console.log(json);
})

```
