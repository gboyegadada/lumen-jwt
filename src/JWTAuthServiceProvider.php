<?php

namespace Yega\Auth;

use Illuminate\Support\ServiceProvider;
use \Yega\Auth\JWTHelper;

class JWTAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            $guard = new JWTGuard(
                $name,
                new JWTHelper(),
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            // Return an instance of Illuminate\Contracts\Auth\Guard...
            return $guard;
        });
    }
}
