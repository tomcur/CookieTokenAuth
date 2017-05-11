<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Routing\Router;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * Redirect component.
 */
class RedirectComponent extends Component
{
    
    
    public $components = ['Auth'];
    
    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        
        // The query string key used for remembering the referrered page when getting redirected to login.
        if (defined("\Cake\Controller\Component\AuthComponent::QUERY_STRING_REDIRECT")) {
            $this->query_string_redirect = \Cake\Controller\Component\AuthComponent::QUERY_STRING_REDIRECT;
        } else {
            $this->query_string_redirect = 'redirect';
        }
    }

    /**
     * Redirect the client to the cookie authentication page. 
     * If a url is given, set Auth to return to that url after authentication.
     *
     * @param string $url The url to return the client to after authentication.
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
            $route['?'][$this->query_string_redirect] = $this->request->here(false);
        }
        
        $resp = $this->controller->redirect(Router::url($route));
        
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
