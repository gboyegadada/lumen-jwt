<?php

namespace Yega\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use \Firebase\JWT\JWT;
use \Yega\Auth\JWTHelper;

class JWTGuard implements Guard
{
    use GuardHelpers;

    /**
     * The name of the Guard. Typically "session".
     *
     * Corresponds to guard name in authentication configuration.
     *
     * @var string
     */
    protected $name;

    /**
     * The user we last attempted to retrieve.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The JWT Helper Object.
     *
     * @var \Yega\Auth\JWTHelper
     */
    protected $jwt;

    /**
     * The current User.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;



    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($name, JWTHelper $jwt, UserProvider $provider, Request $request)
    {
        $this->name = $name;
        $this->jwt = $jwt;
        $this->request = $request;
        $this->provider = $provider;

        $this->getTokenForRequest();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        if ($this->jwt->isHealthy()) {
            $id = $this->jwt->getPayload()->user_id;
            $user = $this->provider->retrieveById($id);
        }

        return $this->user = $user;
    }

    /**
     * Return the currently cached user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function setUser(AuthenticatableContract $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        if (!$this->jwt->isHealthy() && $this->request->headers->has('Authorization')) {
          list($jwt_token) = sscanf( $this->request->headers->get('Authorization'), 'Bearer %s');
          $this->jwt->setToken($jwt_token);
        }

        return $this->jwt->getToken();
    }

    /**
     * Generate new token by ID.
     *
     * @param mixed $id
     *
     * @return string|null
     */
    public function generateTokenFromUser()
    {
      $payload =  [
            "context" => "market",
            "user_id" => $this->user->id,
            "email" => $this->user->email,
            "name" => $this->user->getFullName()
        ];

      return $this->jwt->newToken($this->user, $payload);
    }



    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param array $credentials
     * @param bool  $login
     *
     * @return mixed
     */
    public function attempt(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        if ($this->hasValidCredentials($user, $credentials)) {
          return $this->login($user);
        }
        return false;
    }


    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return $this->attempt($credentials);
    }

    /**
     * Create a token for a user.
     *
     * @param JWTSubject $user
     *
     * @return string
     */
    public function login(AuthenticatableContract $user)
    {
        $this->setUser($user);
        return $this->generateTokenFromUser();
    }

    /**
     * Invalidate token for a user.
     *
     *
     * @return string
     */
    public function logout()
    {
        $this->user = null;
        return $this->jwt->isHealthy()
                ? $this->jwt->invalidateToken()
                : null;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     * @param array $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials(AuthenticatableContract $user, $credentials)
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
