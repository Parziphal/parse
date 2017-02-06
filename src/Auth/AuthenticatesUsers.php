<?php

namespace Parziphal\Parse\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Parse\ParseException;

trait AuthenticatesUsers
{
    use RedirectsUsers, ThrottlesLogins;

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view ('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin ($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts ($request)) {
            $this->fireLockoutEvent ($request);

            return $this->sendLockoutResponse ($request);
        }

        if ($this->attemptLogin ($request)) {
            return $this->sendLoginResponse ($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts ($request);

        return $this->sendFailedLoginResponse ($request);
    }

    public function loginOrRegisterWithFacebook(Request $request)
    {
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts ($request)) {
            $this->fireLockoutEvent ($request);

            return $this->sendLockoutResponse ($request);
        }

        if ($user = $this->loginWithFacebook ($request)) {
            $this->guard ()->login ($user);
        }
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts ($request);

        return $this->sendFailedLoginResponse ($request);
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $userClass = $this->userClass ();
        $userClass::logOut ();

        $this->guard ()->logout ();

        $request->session ()->flush ();
        $request->session ()->regenerate ();

        return redirect ('/');
    }

    /**
     * Get user model class
     *
     * @return mixed
     */
    public function userClass()
    {
        return config ('auth.providers.users.model');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $userClass = $this->userClass ();
        $this->validate ($request, $userClass::LOGIN_RULES);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $credentials = $this->credentials ($request);
        return $this->guard ()->attempt ($credentials, $request->input ('remember'));
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only ($this->username (), 'password');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session ()->regenerate ();

        $this->clearLoginAttempts ($request);

        return $this->authenticated ($request, $this->guard ()->user ())
            ?: redirect ()->intended ($this->redirectPath ());
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //
    }

    /**
     * Registers a new user or log in into Parse if the user exists.
     * Returns null if an error occured.
     * The ParseException is not catched as it normally shouldn't happen.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     * @throws ParseException
     */
    protected function loginWithFacebook(Request $request)
    {
        try {
            $userClass = $this->userClass ();
            return $userClass::logInWithFacebook ($request->id, $request->access_token);
        } catch (ParseException $e) {
            return null;
        }
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect ()->back ()
            ->withInput ($request->only ($this->username (), 'remember'))
            ->withErrors ([
                $this->username () => Lang::get ('auth.failed'),
            ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard ();
    }

    /**
     * @return string
     */
    protected function username()
    {
        $userClass = $this->userClass ();
        return $userClass::USERNAME;
    }
}