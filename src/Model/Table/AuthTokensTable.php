<?php
namespace CookieTokenAuth\Model\Table;

use Cake\ORM\Table;

class AuthTokensTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        
        $this->belongsTo('Users');
    }
    
    public function removeExpired()
    {
        $this->deleteAll(['expires < now()']);
    }
    
    public function deleteAllByUser($user)
    {
        $this->deleteAll(['user_id' => $user->id]);
    }
}
