<?php

namespace Parziphal\Parse\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * This trait can be used in AuthController to register
 * and authenticate users with Facebook.
 */
trait AuthenticatesWithFacebook
{
    /**
     * Registers a new user or log in if the user exists.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logInOrRegisterWithFacebook(Request $request)
    {
        $user = $this->logInWithFacebook($request);

        Auth::guard($this->getGuard())->login($user);

        return redirect($this->redirectPath());
    }

    /**
     * Accepts both username|email/password and Facebook registration requests.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerAny(Request $request)
    {
        if ($request->id && $request->auth_token) {
            return $this->registerWithFacebook($request);
        } else {
            return $this->register($request);
        }
    }

    /**
     * Handles a registration using Facebook.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerWithFacebook(Request $request)
    {
        $this->logInWithFacebook($request);

        return redirect($this->redirectPath());
    }

    /**
     * Registers a new user or log in into Parse if the user exists.
     * Returns null if an error occured.
     * The ParseException is not catched as it normally shouldn't happen.
     *
     * @param Request  $request
     * @return \Parziphal\Parse\UserModel|null
     * @throws ParseException
     */
    protected function logInWithFacebook(Request $request)
    {
        $class = config('auth.providers.users.model');

        return $class::logInWithFacebook($request->id, $request->access_token);
    }
}
