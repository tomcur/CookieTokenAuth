<?php

namespace Beskhue\CookieTokenAuth\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Auth\DefaultPasswordHasher;

class CookieTokenAuthenticate extends BaseAuthenticate
{   
    public $components = ['Cookie'];

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Cake\Network\Request $request Request to get authentication information from.
     * @param \Cake\Network\Response $response A response object that can have headers added.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        return $this->getUser($request);
    }
    
    /**
     * Get a user based on information in the request. Primarily used by stateless authentication
     * systems like basic and digest auth.
     *
     * @param \Cake\Network\Request $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(Request $request)
    {
        return $this->_getUser();
    }
    
    /**
     * Get a user based on cookie data.
     *
     * @return mixed Either false or an array of user information
     */
    private function _getUser()
    {
        $cookieTokenComponent = $this->_registry->load('Beskhue/CookieTokenAuth.CookieToken');
        $flashComponent = $this->_registry->load('Flash');
        $authTokens = \Cake\ORM\TableRegistry::get('Beskhue/CookieTokenAuth.AuthTokens');
        
        $authTokens->removeExpired();
        
        $data = $this->getCookieData();
        if(!$data)
        {
            return false;
        }
        
        $series = $data['series'];
        $token = $data['token'];
        
        $tokenEntity = $authTokens->findBySeries($series)->contain('Users')->first();
        if(!$tokenEntity)
        {
            // The series was not found. 
            $cookieTokenComponent->removeCookie();
            return false;
        }
        
        $user = $tokenEntity->user;
        
        if(!(new DefaultPasswordHasher)->check($token, $tokenEntity->token))
        {
            // Tokens don't match. Probably attempted theft!
            $flashComponent->error('A session token mismatch was detected. You have been logged out.');
            $authTokens->deleteAllByUser($user);
            $cookieTokenComponent->removeCookie();
            return false;
        }
        
        // Generate new token
        $cookieTokenComponent->setCookie($user, $tokenEntity);
        
        return $this->_findUser($user->username);
    }
    
    /**
     * Called when the user logs out. Remove the token from the database and 
     * delete the cookie.
     * 
     * @param \Cake\Event\Event $event The logout event.
     * @param array $user The user data.
     * @return void
     */
    public function logout(\Cake\Event\Event $event, array $user)
    {
        $cookieTokenComponent = $this->_registry->load('Beskhue/CookieTokenAuth.CookieToken');
        $authTokens = \Cake\ORM\TableRegistry::get('Beskhue/CookieTokenAuth.AuthTokens');
        
        // Check if cookie is valid
        if($this->_getUser())
        {
            // Remove token from database
            $data = $this->getCookieData();
            if($data)
            {
                $series = $data['series'];
                
                $tokenEntity = $authTokens->findBySeries($series)->first();
                $authTokens->delete($tokenEntity);
            }
        }
        
        // Remove cookie
        $cookieTokenComponent->removeCookie();
    }
    
    /**
     * Get and validate the cookie data.
     * 
     * @return mixed Either false or an array of cookie token data.
     */
    private function getCookieData()
    {
        $cookieComponent = $this->_registry->load('Cookie');
        $data = $cookieComponent->read('userdata');
        if(!$data || !isset($data['series']) || !isset($data['token']))
        {
            // Cookie does not exist or is malformed.
            return false;
        }
        return $data;
    }

    /**
     * Returns a list of all events that this authenticate class will listen to.
     *
     * An authenticate class can listen to following events fired by AuthComponent:
     *
     * - `Auth.afterIdentify` - Fired after a user has been identified using one of
     *   configured authenticate class. The callback function should have signature
     *   like `afterIdentify(Event $event, array $user)` when `$user` is the
     *   identified user record.
     *
     * - `Auth.logout` - Fired when AuthComponent::logout() is called. The callback
     *   function should have signature like `logout(Event $event, array $user)`
     *   where `$user` is the user about to be logged out.
     *
     * @return array List of events this class listens to. Defaults to `[]`.
     */
    public function implementedEvents()
    {
        return ['Auth.logout' => 'logout'];
    }
}