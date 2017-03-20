<?php

namespace Yega\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use \Firebase\JWT\JWT;
use \Yega\Auth\JWTHelper;

class JWTGuard implements Guard
{
    use GuardHelpers;

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
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(JWTHelper $jwt, UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->jwt = $jwt;
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
            $user = $this->provider->retrieveById($this->jwt->getPayload()->user_id);
        }

        return $this->user = $user;
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
    public function generateTokenByID($id)
    {
      $user = $this->provider->retrieveById($id);
      $payload =  [
            "context" => "market",
            "user_id" => $user->id,
            "email" => $user->email,
            "name" => $user->getFullName()
        ];

      return $this->jwt->newToken($payload);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return $this->attempt($credentials, false, false);
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
