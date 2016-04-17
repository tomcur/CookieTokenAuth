<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Routing\Router;

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
    }

    /**
     * Redirect the client to the cookie authentication page. 
     * If a url is given, set Auth to return to that url after authentication.
     *
     * @param string $url The url to return the client to after authentication.
     */
    public function redirectToAuthenticationPage($url = null)
    {
        if ($url) {
            $this->Auth->redirectUrl($url);
        } else {
            $this->Auth->redirectUrl($this->request->here(false));
        }
        
        $this->controller->redirect(Router::url([
            'plugin' => 'Beskhue/CookieTokenAuth', 
            'controller' => 'CookieTokenAuth'
        ]));
    }
    
    /**
     * Redirect the client back to where they were before they were
     * sent to the cookie authentication page, or to the page specified
     * when calling the redirectToAuthenticationPage method.
     */
    public function redirectBack()
    {
        $this->controller->redirect($this->Auth->redirectUrl());
    }
        
}
