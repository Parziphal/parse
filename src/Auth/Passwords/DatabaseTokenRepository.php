<?php

namespace Illuminate\Parse\Auth\Passwords;

use Carbon\Carbon;
use Illuminate\Parse\Model;
use Illuminate\Parse\Query;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    /**
     * The token database table.
     *
     * @var string
     */
    protected $table;

    /**
     * The hashing key.
     *
     * @var string
     */
    protected $hashKey;

    /**
     * The number of seconds a token should last.
     *
     * @var int
     */
    protected $expires;

    /**
     * Create a new token repository instance.
     *
     * @param  string  $table
     * @param  string  $hashKey
     * @param  int  $expires
     */
    public function __construct($table, $hashKey, $expires = 60)
    {
        $this->table = $table;
        $this->hashKey = $hashKey;
        $this->expires = $expires * 60;
    }

    /**
     * Create a new token record.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        // We will create a new, random token for the user so that we can e-mail them
        // a safe link to the password reset form. Then we will insert a record in
        // the database so that we can verify the token within the actual reset.
        $token = $this->createNewToken();

        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     */
    protected function deleteExisting(CanResetPasswordContract $user)
    {
        $this->getTable()->where(['email' => $user->getEmailForPasswordReset()])->delete(false);
    }

    /**
     * Build the record payload for the table.
     *
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($email, $token)
    {
        return ['email' => $email, 'token' => $token, Model::CREATED_AT => new \DateTime()];
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, $token)
    {
        $email = $user->getEmailForPasswordReset();
        $model = $this->getTable()->where(['email' => $email])->where(['token' => $token])->first();

        return $model && ! $this->tokenExpired($model);
    }

    /**
     * Determine if the token has expired.
     *
     * @param  Model $model
     * @return bool
     */
    protected function tokenExpired(Model $model)
    {
        $expiresAt = Carbon::parse($model->getCreatedAt())->addSeconds($this->expires);
        return $expiresAt->isPast();
    }

    /**
     * Delete a token record by token.
     *
     * @param  string  $token
     * @return void
     */
    public function delete($token)
    {
        $this->getTable()->where(['token' => $token])->delete(false);
    }

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired()
    {
        $expiredAt = Carbon::now()->subSeconds($this->expires);

        $this->getTable()->where(Model::CREATED_AT, '<', $expiredAt)->delete(false);
    }

    /**
     * Create a new token for the user.
     *
     * @return string
     */
    public function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * @return mixed
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

    /**
     * Begin a new database query against the table.
     *
     * @return \Illuminate\Parse\Query
     */
    protected function getTable()
    {
        $className = $this->getAppNamespace() . $this->removePrefix ($this->table, "_");
        return new Query($this->table, $className, true);
    }

    /**
     * @param string $text
     * @param string $prefix
     * @return string
     */
    protected function removePrefix($text, $prefix)
    {
        if (0 === strpos ($text, $prefix))
            $text = substr ($text, strlen ($prefix));
        return $text;
    }

}
