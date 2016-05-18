<?php

namespace Parziphal\Parse;

use Parse\ParseUser;

class UserModel extends ObjectModel
{
    protected static $parseClassName = '_User';
    
    /**
     * These static methods in ParseUser return a new
     * instance of that class.
     */
    protected static $parseUserStaticMethods = [
        'logIn',
        'logInWithFacebook',
        'loginWithAnonymous',
        'become',
    ];
    
    public static function __callStatic($method, array $params)
    {
        if (in_array($method, self::$parseUserStaticMethods)) {
            return new static(call_user_func_array(ParseUser::class . '::' . $method, $params)); 
        }
        
        return parent::__callStatic($method, $params);
    }
    
    public function linkWithFacebook($id, $accessToken, $expirationDate = null, $useMasterKey = false)
    {
        return new static($this->parseObject->loginWithAnonymous(
            $id,
            $accessToken,
            $expirationDate,
            $useMasterKey
        ));
    }
}
