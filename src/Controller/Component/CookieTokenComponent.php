<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Routing\Router;

/**
 * Cookie token component.
 */
class CookieTokenComponent extends Component
{
    public $components = ['Cookie'];

    /**
     * Generates a new token cookie.
     * If $token is not given, generates a new series and token hash,
     * saves it, and sends the cookie to the user's browser.
     *
     * If $token is given, generates a new token hash (but uses the
     * same series as in $token), extends the expiration date, saves
     * it, and sends a new cookie to the user's browser.
     *
     * @param $user  The user data.
     * @param $token The token to re-use.
     */
    public function setCookie($user, $token = null)
    {
        $authTokens = \Cake\ORM\TableRegistry::get('Beskhue/CookieTokenAuth.AuthTokens', $this->_config);
        
        $expires = new \DateTime();
        $expires->modify($this->getConfig()['cookie']['expires']);

        $series = hash($this->_config['hash'], microtime(true).mt_rand());
        $t = hash($this->_config['hash'], microtime(true).mt_rand());
        $tokenHash = (new DefaultPasswordHasher())->hash($t);


        if (!$token) {
            $token = $authTokens->newEntity();
            $token->user_id = $user['id'];
            $token->series = $series;
        }

        $token->token = $tokenHash;
        $token->expires = $expires;

        if ($this->_config['minimizeCookieExposure']) {
            // We are minimizing token cookie exposure, so tell the browser to only send the token cookie
            // on the token cookie authentication page
            $path = Router::url([
                'plugin' => 'Beskhue/CookieTokenAuth', 
                'controller' => 'CookieTokenAuth',
            ]);   
        } else { 
            // We are not minimizing token cookie exposure, tell the browser to always send the token cookie
            $path = '/';
        }
        
        $this->Cookie->config([
            'path' => $path,
            'encryption' => 'aes',
            'expires' => $this->getConfig()['cookie']['expires'],
        ]);
        $this->Cookie->write($this->getConfig()['cookie']['name'], [
            'series' => $token->series,
            'token' => $t,
        ]);

        $authTokens->save($token);
    }

    /**
     * Remove a token.
     *
     * @param $token The token to remove.
     */
    public function removeToken($token)
    {
        $this->delete($token);
    }

    /**
     * Remove the token cookie from the user's browser.
     *
     * Rewrites the cookie with dummy values and expires the cookie.
     */
    public function removeCookie()
    {
        $this->Cookie->config([
            'path' => Router::url([
                'plugin' => 'Beskhue/CookieTokenAuth', 
                'controller' => 'CookieTokenAuth',
                'prefix' => false,
            ]),
            'encryption' => 'aes',
            'expires' => '-1 day',
        ]);
        $this->Cookie->write($this->getConfig()['cookie']['name'], []);
    }
}
