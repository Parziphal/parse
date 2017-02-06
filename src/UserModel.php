<?php

namespace Illuminate\Parse;

use Parse\ParseUser;

class UserModel extends ParseModel
{
    protected static $parseClassName = '_User';

    /**
     * These static methods in ParseUser return a new
     * instance of that class.
     */
    protected static $parseUserStaticMethods = [
        'logIn',
        'logOut',
        'signUp',
        'logInWithFacebook',
        'loginWithAnonymous',
        'become',
    ];

    /**
     * @param ParseUser|array $data
     * @param bool $useMasterKey
     */
    public function __construct($data = null, $useMasterKey = null)
    {
        if ($data != null && !$data instanceof ParseUser && !is_array ($data)) {
            $type = is_object ($data) ? get_class ($data) : gettype ($data);

            throw new Exception(
                sprintf ("Either a ParseUser or an array must be passed to instantiate a UserModel, %s passed", $type)
            );
        }

        if ($data instanceof ParseUser) {
            $this->parseObject = $data;
        } else {
            $this->parseObject = new ParseUser(static::parseClassName ());

            if ($data) {
                $this->fill ($data);
            }
        }

        $this->useMasterKey = $useMasterKey !== null ? $useMasterKey : static::$defaultUseMasterKey;
    }

    public static function __callStatic($method, array $params)
    {
        if (in_array ($method, self::$parseUserStaticMethods)) {
            return new static(call_user_func_array (ParseUser::class . '::' . $method, $params));
        }

        return parent::__callStatic ($method, $params);
    }

    /**
     * In order to create ParseUsers we have to call signUp instead of save.
     */
    public static function create($data, $useMasterKey = null)
    {
        if ($useMasterKey === null) {
            $useMasterKey = static::$defaultUseMasterKey;
        }

        $model = new static($data, $useMasterKey);
        $model->signUp ();

        return $model;
    }

    public function linkWithFacebook($id, $accessToken, $expirationDate = null, $useMasterKey = false)
    {
        return new static($this->parseObject->loginWithAnonymous (
            $id,
            $accessToken,
            $expirationDate,
            $useMasterKey
        ));
    }
}
