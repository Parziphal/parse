<?php

namespace Parziphal\Parse\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

trait RegistersUsers
{
    use RedirectsUsers;

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view ('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator ($request->all ())->validate ();

        event (new Registered($user = $this->create ($request->all ())));

        $this->guard ()->login ($user);

        return $this->registered ($request, $user)
            ?: redirect ($this->redirectPath ());
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
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $class = $this->userClass ();
        return Validator::make ($data, $class::REGISTER_RULES);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function create(array $data)
    {
        $class = $this->userClass ();

        $user = [];
        foreach ($class::REGISTER_RULES as $key => $value) {
            $user[$key] = $data[$key];
        }

        $user['emailVerified'] = false;
        return $class::create ($user);
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard ();
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }
}