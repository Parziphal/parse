<?php

namespace Parziphal\Parse;

use Parse\ParseUser;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class UserModel extends ParseUser implements Jsonable, Arrayable
{
    use ModelMethods;
    
    protected $keyName = '_id';
    
    protected static $currentUserModel;
    
    public function getKeyName()
    {
        return $this->keyName;
    }
    
    public function getKey()
    {
        return $this->getObjectId();
    }
    
    public static function setCurrentUserModel($fullClassName)
    {
        self::$currentUserModel = $fullClassName;
    }
    
    public static function getCurrentUser()
    {
        $user = parent::getCurrentUser();
        
        if ($user) {
            $sessionToken = $user->getSessionToken();
            
            if (self::$currentUserModel) {
                $class = self::$currentUserModel;
                
                $user = $class::createExisting($user);
            } else {
                $user = static::createExisting($user);
            }
            
            $user->_sessionToken = $sessionToken;
        }
        
        return $user;
    }
}
