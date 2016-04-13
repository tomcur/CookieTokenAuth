This is a plugin for CakePHP to allow long-term login sessions for users using cookies. The sessions are identified by two variables: a random series variable, and a token. The sessions are stored in the database and linked to the users they belong to. The token variables are stored hashed. 

This is more secure than storing a username and (hashed) password in a cookie. If a session cookie were to be leaked, the user's password hash would be available. There also would be no method of invalidating the session.

This method is also more secure than storing a username and a token in a cookie. Firstly, we now have distinct sessions for different browsers. When the user logs out in one browser, that session can be removed from the database. Secondly, when a session theft is attempted we'd ideally invalidate the users' sessions. Implementing this without series means that a denial of service for specific users can be performed by simply presenting cookies with their username. Here, an attacker would first have to guess the (random) series variable.

A valid token grants almost as much access as a valid password, and thus it should be treated as one. By storing only token hashes in the database, attackers cannot get access to user accounts when the session database is leaked. 

# Installation
Place the following in your `composer.json`:
```
"require": {
    "beskhue/cookietokenauth": "0.1.1"
}
```

and run:
```
php composer.phar update
```

## Database
The plugin needs to store data in a database. You can find the database structure dump [here](https://github.com/Beskhue/CookieTokenAuth/blob/master/db.sql).

---

# Usage
## Bootstrap
Place the following in your `config/bootstrap.php` file:
```
Plugin::load('Beskhue/CookieTokenAuth');
```

or use bake:
```
"bin/cake" plugin load Beskhue/CookieTokenAuth
```

## Set Up `AuthComponent`
Update your AuthComponent configuration to use CookieTokenAuth. For example, if you also use the Form authentication to log users in, your could write:
```
$this->loadComponent('Auth', [
    'authenticate' => [
        'Beskhue/CookieTokenAuth.CookieToken',
        'Form'
    ]
]);
```

## Validate Cookies
Next, you probably want to validate user authentication of non-logged in users each request. This makes sure that a user with a valid token cookie will be logged in. To do that, place something like the following in your `AppController->beforeFilter`. Note that you will also have to change the current identification you are doing (probably in `UsersController`). See the next section.

```
if(!$this->Auth->user())
        {
            $user = $this->Auth->identify();
			if ($user) 
            {
				$this->Auth->setUser($user);
				$redirectUri = $this->Auth->redirectUrl();
				return $this->redirect($redirectUri);
			} 
        }  
```

## Create Token Cookies
We need to create token cookies when users log in with a conventional method (Form, Ldap, etc). For a Form login, you could do something as follows. This will create a token, add it do the database, and the user will create a cookie.

```
public function login()
{
    $this->loadComponent('Beskhue/CookieTokenAuth.CookieToken');

    if ($this->request->is('post')) 
    {
        $user = $this->Auth->user();
        if($user)
        {
            $this->CookieToken->setCookie($user);
        }
        else
        {
            $this->Flash->error(__('Username or password is incorrect.'));
        }
    }
}
```

# Limitations
Currently, the plugin assumes your users are stored in the `Users` models and that the user table in the database is called `users`. It would not be too hard to change the plugin to your application, but a more general solution should be implemented.