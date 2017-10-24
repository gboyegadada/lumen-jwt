<?php

namespace Yega\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use \Firebase\JWT\JWT;

class JWTHelper
{

    /**
     * The JWT (decoded).
     *
     * @var StdObject
     */
    protected $token;

    /**
     * [$decoded description]
     * @var [type]
     */
    protected $decoded;

    /**
     * [$key description]
     * @var string
     */
    protected $key;

    /**
     * [$includes fields in USER instance to include in JWT]
     * @var string[]
     */
    protected $includes;

    /**
     * JWT payload property to use when looking up users by primary key.
     * @var string
     */
    protected $id_field;

    /**
     * Expire (in seconds)
     * @var string
     */
    protected $expire_after;

    /**
     * The JWT Issuer
     * @var string
     */
    protected $issuer;

    /**
     * Delay in seconds before token will be valid
     * @var integer
     */
    protected $notBefore_delay;


    /**
     * Create a new helper.
     */
    public function __construct()
    {
      $this->token = null;

      $this->key = env('JWT_KEY');
      if (is_null($this->key)) throw new \RuntimeException("Please set 'JWT_KEY' in Lumen env file.");

      $this->expire_after = env('JWT_EXPIRE_AFTER');
      if (is_null($this->expire_after)) throw new \RuntimeException("Please set 'JWT_EXPIRE_AFTER' in Lumen env file.");

      $this->issuer = env('JWT_ISSUER');
      if (is_null($this->issuer)) throw new \RuntimeException("Please set 'JWT_ISSUER' in Lumen env file.");

      $this->id_field = env('JWT_ID_FIELD');
      if (is_null($this->id_field)) {
        $this->id_field = 'id';
      }

      $includes = env('JWT_INCLUDE');
      $this->includes = is_null($includes) ? [$this->id_field] : explode(",", $includes);
      if (!in_array($this->id_field, $this->includes)) $this->includes[] = $this->id_field; // always add user id

      $this->notBefore_delay = env('JWT_NBF_DELAY', 10);
    }

    /**
     * Check if helper has a token.
     * @return boolean
     */
    public function isHealthy()
    {
      return ($this->getDecoded() !== null);
    }

    /**
     * [setToken description]
     * @param String $token
     */
    public function setToken($token)
    {
      $this->token = $token;
      $this->decoded = null;

      return $this;
    }

    /**
     * Get Token String
     * @return string Token String
     */
    public function getToken()
    {
      return $this->token;
    }

    /**
     * Get decoded token.
     * @return StdObject JSON Object
     */
    public function getDecoded()
    {
      if (is_null($this->token)) return null;
      if (is_null($this->decoded)) {
        try {
          $this->decoded = JWT::decode($this->token, $this->key, ['HS512']);
        }
        catch (\Exception $e) {}
        catch (\DomainException $e) {}
      }
      return  $this->decoded;
    }

    /**
     * Generate new token.
     * @param  AuthenticatableContract $user.
     * @return string JWT token string.
     */
    public function newToken(AuthenticatableContract $user)
    {
      $this->decoded = null;

      $tokenId = md5(uniqid($user->email, true));
      $issuedAt   = time();
      $notBefore  = $issuedAt + $this->notBefore_delay;
      $expire     = $notBefore + $this->expire_after;
      $jwt_key = $this->key;
      $issuer = $this->issuer;
      $subject = $user->{$this->id_field};

      $data = [];
      foreach ($this->includes as $k) $data[$k] = $user->{$k};

      $token = [
          "iss" => $this->issuer,
          "jti" => $tokenId,
          "iat" => $issuedAt,
          "nbf" => $notBefore,
          "exp" => $expire,
          "data" => $data,
          "sub" => $subject
        ];

      $this->decoded = (object) $token;

      return $this->token = JWT::encode($token, $jwt_key, 'HS512');
    }

    /**
     * Get value stored in token id field
     * @return string
     */
    public function getId()
    {
      $decoded = $this->getDecoded();
      return !is_null($decoded)
                ? $decoded->data->{$this->id_field}
                : null;
    }

    /**
     * Refresh token
     * @return string New Token
     */
    public function refresh()
    {
      $decoded = (array) $this->getDecoded();
      $decoded['data'] = (array) $decoded['data'];
      $this->decoded = null;

      $issuedAt   = time();
      $notBefore  = $issuedAt + 10; //Adding 10 seconds
      $expire     = $notBefore + $this->expire_after;

      $decoded["iat"] = $issuedAt;
      $decoded["nbf"] = $notBefore;
      $decoded["exp"] = $expire;


      return $this->token = JWT::encode($decoded, $this->key, 'HS512');

    }

    /**
     * Invalidate token
     * @return string New Token (dead on arrival)
     */
    public function invalidateToken()
    {
      $decoded = (array) $this->getDecoded();
      $decoded['data'] = (array) $decoded['data'];
      $this->decoded = null;

      $issuedAt   = time() - $this->expire_after - 7200; // less {expiry} and 2 hours
      $notBefore  = $issuedAt + 10; //Adding 10 seconds - no need, I know
      $expire     = $notBefore + 1; //Adding 1 second - no need, I know

      $decoded["iat"] = $issuedAt;
      $decoded["nbf"] = $notBefore;
      $decoded["exp"] = $expire;


      return $this->token = JWT::encode($decoded, $this->key, 'HS512');

    }




}
