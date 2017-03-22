<?php

namespace Parziphal\Parse\Auth;

use Parse\ParseUser;
use Parse\ParseClient;
use Parse\ParseSession;
use Parse\ParseException;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\SessionGuard as Base;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * All "once*" methods aren't supported. We could make them work by
 * registering a shutdown function that will logout the user from Parse.
 * All "*UsingId" methods can't work properly as we can't log in a user
 * to Parse just with their ID.
 */
class SessionGuard extends Base
{
    /**
     * @var Recaller
     */
    protected $recaller;

    public function user()
    {
        $user = parent::user();

        if ($user && !ParseUser::getCurrentUser()) {
            $sessionToken = $this->recaller->sessionId();

            if ($sessionToken) {
                try {
                    ParseUser::become($sessionToken);
                    ParseClient::getStorage()->set('user', ParseUser::getCurrentUser());
                } catch (ParseException $e) {
                }
            }

            if (!ParseUser::getCurrentUser()) {
                // Laravel knows the user but Parse doesn't and we don't have
                // the session token to login the user to Parse.
                return null;
            }
        }

        return $user;
    }

    public function logout()
    {
        parent::logout();
        ParseUser::logOut();
    }

    protected function recaller()
    {
        if (is_null($this->request)) {
            return;
        } elseif ($this->recaller) {
            return $this->recaller;
        }

        if ($recaller = $this->request->cookies->get($this->getRecallerName())) {
            $this->recaller = new Recaller($recaller);
        }

        return $this->recaller;
    }

    protected function queueRecallerCookie(AuthenticatableContract $user)
    {
        $this->getCookieJar()->queue($this->createRecaller(
            $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.ParseSession::getCurrentSession()->getSessionToken()
        ));
    }
}