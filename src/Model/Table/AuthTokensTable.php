<?php

namespace Beskhue\CookieTokenAuth\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\Table;

/**
 * Class AuthTokensTable
 * @package Beskhue\CookieTokenAuth\Model\Table
 */
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
     * @param $user Entity The user for whom tokens should be removed.
     */
    public function deleteAllByUser(Entity $user)
    {
        $this->deleteAll(['user_id' => $user->id]);
    }
}
