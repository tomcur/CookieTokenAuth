<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Cookie token component.
 * @property Component\CookieComponent Cookie
 */
class CookieTokenComponent extends Component
{
    public $components = ['Cookie'];

    /**
     * Returns the cookie path depending on the current config.
     *
     * @return string The cookie path.
     */
    protected function _getCookiePath()
    {
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
        return $path;
    }

    /**
     * Generates a new token cookie.
     * If $token is not given, generates a new series and token hash,
     * saves it, and sends the cookie to the user's browser.
     *
     * If $token is given, generates a new token hash (but uses the
     * same series as in $token), extends the expiration date, saves
     * it, and sends a new cookie to the user's browser.
     *
     * @param $userId int|string The user id.
     * @param $token Entity The token to re-use.
     * @throws \Cake\Core\Exception\Exception
     */
    public function setCookie($userId, Entity $token = null)
    {
        $authTokens = TableRegistry::get('Beskhue/CookieTokenAuth.AuthTokens', $this->_config);

        $expires = new \DateTime();
        $expires->modify($this->getConfig()['cookie']['expires']);

        $series = hash($this->_config['hash'], microtime(true) . mt_rand());
        $t = hash($this->_config['hash'], microtime(true) . mt_rand());
        $tokenHash = (new DefaultPasswordHasher())->hash($t);


        if (!$token) {
            /** @var Entity $token */
            $token = $authTokens->newEntity();
            $token->user_id = $userId;
            $token->series = $series;
        }

        $token->token = $tokenHash;
        $token->expires = $expires;

        $cookieConfig = $this->getConfig('cookie');
        $cookieConfig['path'] = $this->_getCookiePath();
        $this->Cookie->setConfig($cookieConfig);
        $this->Cookie->write($cookieConfig['name'], [
            'series' => $token->series,
            'token' => $t,
        ]);

        $authTokens->save($token);
    }

    /**
     * Remove a token.
     *
     * @param $token Entity The token to remove.
     */
    public function removeToken(Entity $token)
    {
        $this->delete($token);
    }

    /**
     * Remove the token cookie from the user's browser.
     *
     * Rewrites the cookie with dummy values and expires the cookie.
     * @throws \Cake\Core\Exception\Exception
     */
    public function removeCookie()
    {
        $config = $this->getConfig('cookie');
        $config['path'] = $this->_getCookiePath();
        $config['expires'] = '-1day';
        $this->Cookie->setConfig($config);
        $this->Cookie->write($config['name'], []);
    }
}
