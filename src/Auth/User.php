<?php

namespace Parziphal\Parse\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Parziphal\Parse\UserModel;

class User extends UserModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;
    
    public function getKeyName()
    {
        return 'objectId';
    }
    
    public function getKey()
    {
        return $this->id();
    }
    
    public function getRememberTokenName()
    {
        return 'rememberToken';
    }
}
