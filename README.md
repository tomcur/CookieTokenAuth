# CookieTokenAuth

This is a plugin for CakePHP to allow long-term login sessions for users using cookies. The sessions are identified by two variables: a random series variable, and a token. The sessions are stored in the database and linked to the users they belong to. The token variables are stored hashed. 

## Why Use CookieTokenAuth?

CookieTokenAuth is more secure than storing a username and (hashed) password in a cookie. 

### No Passwords (nor Password Hashes) in Cookies
If a session cookie were to be leaked, the user's password hash would be available. There also would be no method of invalidating the session.

### Control Over Sessions
This method is more secure than storing a username and a token in a cookie. Firstly, we now have distinct sessions for different browsers. When the user logs out in one browser, that session can be removed from the database. Secondly, when a session theft is attempted we'd ideally invalidate the users' sessions. Implementing this without series means that a denial of service for specific users can be performed by simply presenting cookies with their username. Here, an attacker would first have to guess the (random) series variable.

### Tokens Are Stored Securely
A valid token grants almost as much access as a valid password, and thus it should be treated as one. By storing only token hashes in the database, attackers cannot get access to user accounts when the session database is leaked. 

### Cookie Exposure Is Minimized
For added security, the token cookie is only sent to the server on a special authentication page. This page is only accessed once per per session by the client. As such, opportunity for cookie theft is minimized.

### Encrypted by CakePHP
On top of all these security measures, the token cookies are naturally encrypted by CakePHP.

# Installation
Place the following in your `composer.json`:
```
"require": {
    "beskhue/cookietokenauth": "0.3.0"
}
```

and run:
```
php composer.phar update
```

## Database
The plugin needs to store data in a database. You can find the database structure dump [here](https://github.com/Beskhue/CookieTokenAuth/blob/master/db.sql).

# Usage
## Bootstrap
Place the following in your `config/bootstrap.php` file:
```
Plugin::load('Beskhue/CookieTokenAuth', ['routes' => true]);
```

or use bake:
```
"bin/cake" plugin load --routes Beskhue/CookieTokenAuth
```

## Set Up `AuthComponent`
Update your AuthComponent configuration to use CookieTokenAuth. For example, if you also use the Form authentication to log users in, you could write:
```
$this->loadComponent('Auth', [
    'authenticate' => [
        'Beskhue/CookieTokenAuth.CookieToken',
        'Form'
    ]
]);
```

If the user model or username field are named differently than the defaults, you can configure the plugin:

```
$this->loadComponent('Auth', [
    'authenticate' => [
        'Beskhue/CookieTokenAuth.CookieToken' => [
            'fields' => ['username' => 'email'],
            'userModel' => 'Members'
        ],
        'Form' => [
            'fields' => ['username' => 'email', 'password' => 'passwd'],
            'userModel' => 'Members'
        ],
    ]
]);
```

## Validate Cookies
Next, you probably want to validate user authentication of non-logged in users in all controllers (note: authentication is only attempted once per session). This makes sure that a user with a valid token cookie will be logged in. To do that, place something like the following in your `AppController`'s `beforeFilter`. Note that you will also have to change the current identification you are performing (probably in `UsersController`). See the next section.

```
if(!$this->Auth->user())
{
    $user = $this->Auth->identify();
    if ($user) {
        $this->Auth->setUser($user);
        return $this->redirect($this->Auth->redirectUrl());
    } 
}  
```

## Create Token Cookies
When a user logs in with a conventional method (Form, Ldap, etc) we need to create a token cookie. For a Form login, you could do something as follows. This will create a token, add it to the database, and the user's client will receive a cookie.

```
public function login()
{
    $this->loadComponent('Beskhue/CookieTokenAuth.CookieToken');

    if ($this->request->is('post')) {
        $user = $this->Auth->user();
        if ($user) {
            $this->CookieToken->setCookie($user);
        } else {
            $this->Flash->error(__('Username or password is incorrect.'));
        }
    }
}
```
