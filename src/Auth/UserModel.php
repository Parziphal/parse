<?php

namespace Parziphal\Parse\Auth;

use Illuminate\Auth\Authenticatable;
use Parziphal\Parse\UserModel as BaseUserModel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

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
        return $this->id();
    }

    public function __construct($data = null, $useMasterKey = null)
    {
        parent::__construct($data, $useMasterKey);

        $this->rememberTokenName = 'rememberToken';
    }
}
