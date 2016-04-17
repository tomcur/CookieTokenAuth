<?php

namespace Beskhue\CookieTokenAuth\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class CookieTokenAuthController extends AppController
{
    public $autoRender = false;
    
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['index']);
    }
    
    public function index()
    {
    }
}