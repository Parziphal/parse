<?php

namespace Illuminate\Parse\Auth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Parse\ParseException;

abstract class BaseProvider implements UserProvider
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
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        /**
         * @var \Illuminate\Parse\Model $class
         */
        $class = $this->userClass;

        return $class::query (true)->find ($identifier) ?: null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        /**
         * @var \Illuminate\Parse\Model $class
         */
        $class = $this->userClass;

        return $class::query (true)->where ([
            'objectId' => $identifier,
            'rememberToken' => $token
        ])->first ();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        /**
         * @var \Illuminate\Parse\Model $user
         */
        $user->update (['rememberToken' => $token]);
    }

    protected function validatePassword(Authenticatable $user, array $credentials)
    {
        $username = $this->getUsernameFromCredentials ($credentials);

        try {
            /**
             * @var \Parse\ParseUser $user
             */
            $user->logIn ($username, $credentials['password']);
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

    protected function isFacebookLogIn(array $credentials)
    {
        return array_key_exists ('access_token', $credentials) && array_key_exists ('id', $credentials);
    }

    protected function retrieveByUsername(array $credentials)
    {
        /**
         * @var \Illuminate\Parse\Model $class
         */
        $class = $this->userClass;
        $username = $this->getUsernameFromCredentials ($credentials);

        return $class::query (true)->where (['username' => $username])->orWhere (['email' => $username])->first ();
    }

    protected function retrieveByFacebook(array $credentials)
    {
        /**
         * @var \Illuminate\Parse\Model $class
         */
        $class = $this->userClass;

        // Check if the user exists first. If we call logInWithFacebook right away,
        // the user would be created.
        $user = $class::query (true)->where (['authData.facebook.id' => $credentials['id']])->first ();

        if (!$user) {
            return null;
        }

        try {
            /**
             * @var \Parse\ParseUser $class
             */
            return $class::logInWithFacebook ($credentials['id'], $credentials['access_token']);
        } catch (ParseException $e) {
            return null;
        }
    }

    protected function validateWithPassword(Authenticatable $user, array $credentials)
    {
        return $this->validatePassword ($user, $credentials);
    }

    protected function validateWithFacebook(Authenticatable $user, array $credentials)
    {
        /**
         * If we got here, it means that retrieveByFacebook() returned successfully
         * in retrieveByCredentials().
         */
        return true;
    }
}
