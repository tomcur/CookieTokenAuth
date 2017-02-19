<?php

namespace Beskhue\CookieTokenAuth\Model\Table;

use Cake\ORM\Table;

class AuthTokensTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->belongsTo($config['userModel']);
    }

    /**
     * Remove expired tokens from the database.
     */
    public function removeExpired()
    {
        $this->deleteAll(['expires < now()']);
    }

    /**
     * Delete all tokens belonging to a specific user.
     *
     * @param $user The user for whom tokens should be removed.
     */
    public function deleteAllByUser($user)
    {
        $this->deleteAll(['user_id' => $user->id]);
    }
}
