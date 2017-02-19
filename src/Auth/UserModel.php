<?php

namespace Illuminate\Parse\Auth;

use Illuminate\Auth\Authenticatable;

use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Parse\UserModel as BaseUserModel;
use Illuminate\Parse\Auth\Passwords\CanResetPassword;

class UserModel extends BaseUserModel implements
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
        return $this->id ();
    }

    public function getRememberTokenName()
    {
        return 'rememberToken';
    }
}
