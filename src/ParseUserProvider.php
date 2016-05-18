<?php

namespace Parziphal\Parse;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Parziphal\Parse\Auth\User as UserModel;
use Parse\ParseException;

class ParseUserProvider implements UserProvider
{
    protected $userClass;
    
    /**
     * @param string $userClass
     */
    public function __construct($userClass)
    {
        $this->userClass = $userClass;
    }
    
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $class = $this->userClass;
        
        return $class::query(true)->find($identifier) ?: null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed   $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $class = $this->userClass;
        
        return $class::query(true)->where([
            'objectId'      => $identifier,
            'rememberToken' => $token
        ])->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->update(['rememberToken' => $token], true);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $class = $this->userClass;
        
        $username = $this->getUsernameFromCredentials($credentials);
        
        return $class::query(true)->where(['username' => $username])->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $username = $this->getUsernameFromCredentials($credentials);
        
        try {
            $user->logIn($username, $credentials['password']);
        } catch (ParseException $e) {
            return false;
        }
        
        return true;
    }
    
    protected function getUsernameFromCredentials(array $credentials)
    {
        $username = null;
        
        if (empty($credentials['username'])) {
            if (!empty($credentials['email'])) {
                $username = $credentials['email'];
            }
        } else {
            $username = $credentials['username'];
        }
        
        return $username;
    }
}
