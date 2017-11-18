# CookieTokenAuth

This is a plugin for CakePHP to allow long-term login sessions for users using cookies. The sessions are identified by two variables: a random series variable, and a token. The sessions are stored in the database and linked to the users they belong to. The token variables are stored hashed. 

## Why use CookieTokenAuth?

CookieTokenAuth is more secure than storing a username and (hashed) password in a cookie. 

### No passwords (nor password hashes) in cookies
If a session cookie were to be leaked, the user's password hash would be available. There also would be no method of invalidating the session.

### Control over sessions
This method is more secure than storing a username and a token in a cookie. Firstly, we now have distinct sessions for different browsers. When the user logs out in one browser, that session can be removed from the database. Secondly, when a session theft is attempted we'd ideally invalidate the users' sessions. Implementing this without series means that a denial of service for specific users can be performed by simply presenting cookies with their username. Here, an attacker would first have to guess the (random) series variable.

### Tokens are stored securely
A valid token grants almost as much access as a valid password, and thus it should be treated as one. By storing only token hashes in the database, attackers cannot get access to user accounts when the session database is leaked. 

### Cookie exposure is minimized
For added security, the token cookie is only sent to the server on a special authentication page. This page is only accessed once per per session by the client. As such, opportunity for cookie theft is minimized. This behaviour can be disabled, e.g. to improve site load time for the first visit per session.

### Encrypted by CakePHP
On top of all these security measures, the token cookies are naturally encrypted by CakePHP.

# Installation
Place the following in your `composer.json`:
```
"require": {
    "beskhue/cookietokenauth": "1.2.0"
}
```

and run:
```
php composer.phar update
```

## Database
Setup the plugin database using [the official migrations plugin for CakePHP](https://github.com/cakephp/migrations).

```
cake migrations migrate -p Beskhue/CookieTokenAuth
```

If you have a specific need, such as a different user model, different table name, different data type of the primary key (pay attention to signed vs. unsigned integers if migration fails), or have a different primary key altogether, you have to change the migration file located at `config/Migrations/20170510221552_CreateAuthTokens.php`.

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

## Set up `AuthComponent`
Update your AuthComponent configuration to use CookieTokenAuth. For example, if you also use the Form authentication to log users in, you could write:
```
$this->loadComponent('Auth', [
    'authenticate' => [
        'Beskhue/CookieTokenAuth.CookieToken',
        'Form'
    ]
]);
```

If the user model or user fields are named differently than the defaults, you can configure the plugin:

```
$this->loadComponent('Auth', [
    'authenticate' => [
        'Beskhue/CookieTokenAuth.CookieToken' => [
            'fields' => ['username' => 'email', 'password' => 'passwd'],
            'userModel' => 'Members'
        ],
        'Form' => [
            'fields' => ['username' => 'email', 'password' => 'passwd'],
            'userModel' => 'Members'
        ],
    ]
]);
```

### Configuration 

The full default configuration is as follows:

```
'fields' => [
    'username' => 'username',
    'password' => 'password',
],
'userModel' => 'Users',
'hash' => 'sha256',
'cookie' => [
    'name' => 'userdata',
    'expires' => '+10 weeks',
    'encryption' => 'aes',
    'httpOnly' => true
],
'minimizeCookieExposure' => true,
'setCookieAfterIdentify' => true,
'tokenError' => __('A session token mismatch was detected. You have been logged out.')
```

Note that `hash` is used only for generating tokens -- the token stored in the database is hashed with the DefaultPasswordHasher. Its value can be any [PHP hash algorithm](https://php.net/manual/en/function.hash-algos.php).

If `minimizeCookieExposure` is set to `false`, the client will not be redirected twice at the start of a session to attempt to log them in using a token cookie. Instead, the token cookie is now sent by the client's browser on each request. This is less secure.

## Validate cookies
Next, you probably want to validate user authentication of non-logged in users in all controllers (note: authentication is only attempted once per session). This makes sure that a user with a valid token cookie will be logged in. To do that, place something like the following in your `AppController`'s `beforeFilter`. Note that you might also have to make changes to the current identification method you are performing. See the [next section](#create-token-cookies).

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

## Create token cookies
In most cases, CookieTokenAuth automatically generates token cookies for you. No further configuration and integration would be required.

When a user logs in with a conventional method (Form, Ldap, etc.) we need to create a token cookie such that the user can be identified by CookieTokenAuth when they return. CookieTokenAuth automatically handles identification performed by authentication adapters that are *not* persistent and *not* stateless. This means that from the included authentication adapters in CakePHP only `FormAuthenticate` will automatically generate a token cookie. The reason for this is that persistent or stateless identification methods identify the user each request, and would lead to the creation of a new cookie token on each request.

### Handle stateless and persistent authentication
If you want to handle persistent or stateless authentication identification as well, you could do something as follows. This will create a token, add it to the database, and the user's client will receive a cookie for the token. You would probably want to make sure the user is identified only once per session.

```
public function identify()
{
    $user = $this->Auth->user();
    if ($user) {
        $this->loadComponent(
            'Beskhue/CookieTokenAuth.CookieToken',
            $this->Auth->getConfig('authenticate')['Beskhue/CookieTokenAuth.CookieToken']
        );
        $this->CookieToken->setCookie($user);
    }
}
```


### Disable automatic generation of token cookies
You might want to create token cookies only in specific cases, such as when a user checked a ``remember me" checkbox. To do this, start by setting the `setCookieAfterIdentify` option to `false` (see the [Configuration](#configuration) section). You will now need to create token cookies manually.

To accomplish this, something like the following could be added to the login action:
```
public function login()
{
    // ...
    $user = $this->Auth->user();
    if($user) {
         $this->Auth->setUser($user);
         
         if($this->request->getData('remember_me')) {
            $cookieTokenComponent = $this->Auth->getAuthenticate('Beskhue/CookieTokenAuth.CookieToken')->getCookieTokenComponent();
            $cookieTokenComponent->setCookie($user['id']);
         }
    }
}
```

And add the following to your login template:
```
<?= $this->Form->checkbox('remember_me', ['id' => 'remember-me']) ?>
<?= $this->Form->label('remember_me', __('Remember me')) ?>
```
