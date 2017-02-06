<?php

namespace Illuminate\Parse\Auth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;

class AnyUserProvider extends BaseProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if ($this->isFacebookLogIn ($credentials)) {
            return $this->retrieveByFacebook ($credentials);
        } else {
            return $this->retrieveByUsername ($credentials);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if ($this->isFacebookLogIn ($credentials)) {
            return $this->validateWithFacebook ($user, $credentials);
        } else {
            return $this->validateWithPassword ($user, $credentials);
        }
    }
}
