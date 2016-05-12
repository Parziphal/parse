<?php

namespace Parziphal\Parse;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Parziphal\Parse\Auth\User as UserModel;
use Parse\ParseException;

class ParseUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return UserModel::query()->get($identifier) ?: null;
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
        return UserModel::query()->where([
            '_id' => $identifier,
            'remember_token' => $token
        ])->first(true) ?: null;
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
        $user->update(['remember_token' => $token], true);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $username = $this->getUsernameFromCredentials($credentials);
        
        return UserModel::where(['username' => $username])->first(true) ?: null;
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
