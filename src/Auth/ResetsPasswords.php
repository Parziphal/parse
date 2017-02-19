<?php

namespace Illuminate\Parse\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\Request;
use Illuminate\Parse\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

trait ResetsPasswords
{
    use RedirectsUsers;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string|null $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        $class = $this->userClass ();
        return view ('auth.passwords.reset')->with (
            ['token' => $token, $class::USERNAME => $request->username]
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $class = $this->userClass ();
        $this->validate ($request, $class::RESET_RULES, $this->validationErrorMessages ());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker ()->reset (
            $this->credentials ($request),
            function ($user, $password) {
                $this->resetPassword ($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse ($response)
            : $this->sendResetFailedResponse ($request, $response);
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $class = $this->userClass ();
        return $request->only (
            $class::USERNAME, 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Parse\Model $user
     * @param  string $password
     * @return void
     */
    protected function resetPassword(Model $user, $password)
    {
        $user->update ([
            'password' => $password,
            'rememberToken' => Str::random (60),
        ]);

        $this->guard ()->login ($user);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetResponse($response)
    {
        return redirect ($this->redirectPath ())
            ->with ('status', trans ($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request
     * @param  string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        $class = $this->userClass ();
        return redirect ()->back ()
            ->withInput ($request->only ($class::USERNAME))
            ->withErrors ([$class::USERNAME => trans ($response)]);
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
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker ();
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard ();
    }
}