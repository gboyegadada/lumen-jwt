<?php

namespace Yega\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use \Firebase\JWT\JWT;

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
     * The name of the query string item from the request containing the API token.
     *
     * @var string
     */
    protected $inputKey;

    /**
     * The name of the token "column" in persistent storage.
     *
     * @var string
     */
    protected $storageKey;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
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
        $jwt_token = $this->getTokenForRequest();

        if (!empty($jwt_token)) {

          try {
              $jwt_key = env('APP_KEY');

              /**
               * You can add a leeway to account for when there is a clock skew times between
               * the signing and verifying servers. It is recommended that this leeway should
               * not be bigger than a few minutes.
               *
               * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
               */
              JWT::$leeway = 60; // $leeway in seconds
              $decoded = JWT::decode($jwt_token, $jwt_key, ['HS512']);
              $user = $this->provider->retrieveById($decoded->data->user_id);

          } catch (Exception $e) {
              $user = null;
          }

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
        if (!$this->request->headers->has('Authorization')) return null;

        list($jwt_token) = sscanf( $this->request->headers->get('Authorization'), 'Bearer %s');

        return $jwt_token;
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
