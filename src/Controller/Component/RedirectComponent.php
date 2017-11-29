<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Controller;
use Cake\Routing\Router;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * Redirect component.
 *
 * @property string query_string_redirect
 * @property Controller $controller
 */
class RedirectComponent extends Component
{
    public $components = ['Auth'];

    /**
     * The controller that this collection was initialized with.
     *
     * @var \Cake\Controller\Controller
     */
    private $controller;

    /**
     * The query string key used for remembering
     * the referred page when getting redirected to login.
     *
     * @var string
     */
    private $query_string_redirect = 'redirect';

    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();

        if (defined("\Cake\Controller\Component\AuthComponent::QUERY_STRING_REDIRECT")) {
            $this->query_string_redirect = AuthComponent::QUERY_STRING_REDIRECT;
        }
    }

    /**
     * Redirect the client to the cookie authentication page.
     * If a url is given, set Auth to return to that url after authentication.
     *
     * @param string $url The url to return the client to after authentication.
     * @throws \Cake\Core\Exception\Exception
     */
    public function redirectToAuthenticationPage($url = null)
    {           
        $route = [
            'plugin' => 'Beskhue/CookieTokenAuth', 
            'controller' => 'CookieTokenAuth',
            'prefix' => false,
            '_base' => false
        ];
        
        if ($url) {
            $route['?'][$this->query_string_redirect] = $url;
        } else {
            $route['?'][$this->query_string_redirect] = Component::getController()->request->getRequestTarget();
        }

        $resp = $this->controller->redirect($route);

        // Send the response and stop further processing. This is in part to prevent
        // authentication failure flash messages from showing. The page will be
        // processed as per normal when the user is redirected after the token cookie
        // has been checked.
        $resp->send();
        $resp->stop();
    }
    
    /**
     * Redirect the client back to where they were before they were
     * sent to the cookie authentication page, or to the page specified
     * when calling the redirectToAuthenticationPage method.
     *
     * @param \Cake\Network\Request  $request  Request to get authentication information from.
     * @param \Cake\Network\Response $response A response object that can have headers added.
     */
    public function redirectBack(Request $request, Response $response)
    {
        if (method_exists($request, 'getQuery')) {
            $redirectUrl = $request->getQuery($this->query_string_redirect);
        } else {
            $redirectUrl = $request->query[$this->query_string_redirect];
        }
        
        $this->controller->redirect($redirectUrl);
    }
        
}
