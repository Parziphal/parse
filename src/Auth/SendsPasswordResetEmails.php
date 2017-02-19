<?php

namespace Illuminate\Parse\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

trait SendsPasswordResetEmails
{
    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view ('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        /**
         * @var \App\User $userClass
         */
        $userClass = $this->userClass ();
        $this->validate ($request, $userClass::FORGOT_RULES);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker ()->sendResetLink (
            $request->only ($userClass::USERNAME)
        );

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse ($response)
            : $this->sendResetLinkFailedResponse ($request, $response);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetLinkResponse($response)
    {
        return back ()->with ('status', trans ($response));
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  \Illuminate\Http\Request
     * @param  string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        /**
         * @var \App\User $userClass
         */
        $userClass = $this->userClass ();
        return back ()->withErrors (
            [$userClass::USERNAME => trans ($response)]
        );
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
     * Get user model class
     *
     * @return \App\User
     */
    public function userClass()
    {
        return config ('auth.providers.users.model');
    }

}