# Laravel Parse

This library pretends to make Parse usable in a Eloquent-like manner. For Laravel 5.2+.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an Eloquent-like interface.
* Enabled to work with Parse's relations.
* User authentication with username/password combinations and/or with Facebook.
* Command to create ObjectModels (`artisan parse:model Foo`).

## Setup

Add the service provider in your `config/app.php` file:

```php
'providers' => [
    // etc...
    Parziphal\Parse\ParseServiceProvider::class,
],
```

Publish the configuration file by running:

    php artisan vendor:publish --tag=parse

Set your Parse server configuration in `config/parse.php`, or preferably in your `.env` file by setting the following envs:

    PARSE_APP_ID=App_ID
    PARSE_REST_KEY=REST_API_key
    PARSE_MASTER_KEY=Master_key
    PARSE_SERVER_URL=http://127.0.0.1:1337
    PARSE_MOUNT_PATH=/parse

## Log in with Parse

> Note: On Laravel 5.4 the web middleware group has an entry for `\Illuminate\Session\Middleware\AuthenticateSession` (which is disabled by default). Activating this middleware will cause the "remember me" feature to fail.

Make sure your User class extends `Parziphal\Parse\UserModel`. You could extend instead from `Parziphal\Parse\Auth\UserModel`, which is a authentication-ready User class:

```php
namespace App;

use Parziphal\Parse\Auth\UserModel;

class User extends UserModel
{
}

```

Now we have to configure both the web guard and the users provider, so open `config/auth.php`, and make the following changes:

```php
    'guards' => [
        'web' => [
            'driver' => 'session-parse',
            'provider' => 'users',
        ],
        // ...
    ],

    'providers' => [
        'users' => [
            'driver' => 'parse',
            'model'  => App\User::class,
        ],
        // ...
    ],
```

There are 3 provider drivers available:

* `parse` which requires users to have a username and a password
* `parse-facebook` which requires users to identify using their Facebook account
* `parse-any` which lets users authenticate with either username/password or Facebook

You can use the `Parziphal\Parse\Auth\AuthenticatesWithFacebook` trait in your auth controller along with (not instead of) Laravel's `Illuminate\Foundation\Auth\AuthenticatesUsers` trait. The `AuthenticatesWithFacebook` trait has methods to handle Facebook authentication/registration. Just bind the method (or methods) you need to a route and you're ready to go.

Below is the interface of the authentication trait. Note that it can respond in two ways: with a redirection (the \*Redirect methods), or with JSON (the \*Api methods), which will respond with the `$apiResponse` array.

```php
trait AuthenticatesWithFacebook
{
    protected $apiResponse = ['ok' => true];

    public function logInOrRegisterWithFacebookApi(Request $request);

    public function logInOrRegisterWithFacebookRedirect(Request $request);

    public function registerWithFacebookApi(Request $request);

    public function registerWithFacebookRedirect(Request $request);

    public function registerAny(Request $request);

    public function logoutApi(Request $request);

    // For logout with redirection simply use logout().
}
```

For Facebook login, send the user's Facebook ID as the `id` parameter, and their access token as the `access_token` parameter.

## ObjectModels

The `Parziphal\Parse\ObjectModel` class is a wrapper for `Parse\ParseObject`. It behaves as an Eloquent model, so you could do stuff like:

```php
// Instantiate with data
$post = new Post(['title' => 'Some Title']);

// Create
$post = Post::create(['title' => 'Some Title', 'acl' => $acl]);

// Get objectId
echo $post->id;   // EWFppWR4qf
echo $post->id(); // EWFppWR4qf

// Update
$post->title = "New Title";
$post->save();
// or
$post->update(['foo' => true]);

// Find or fail
$post = Post::findOrFail($id);

// Get all records
$posts = Post::all();

// Delete is like Eloquent's delete: it will delete the object
$post->delete();
// To remove a key (ParseObject's `delete` method), use `removeKey`
$post->removeKey($someKey);

// Create a pointer object
$pointer = Post::pointer($postId);
```

## Queries

`Parziphal\Parse\Query` is a wrapper for `Parse\ParseQuery`, which also behaves like a Eloquent Builder:

```php
// Note that `get` is like Eloquent Builder's `get`, which executes the query,
// and not like ParseQuery's `get` which finds an object by id.
$posts = Post::where('createdAt', '<=', $date)->descending('score')->get();

$posts = Post::where([
    'creator' => $user,
    'title' => $title
  ])
  ->containedIn('foo', $foos)
  ->get();

$post = Post::firstOrCreate($data);
```

## Using Master Key

Objects and queries can be configured to use Master Key with the `$useMasterKey` property. This can be done at class level, at instantiation, or by using the setter method:

```php
// In objects, pass a second parameter when instantiating:
$post = new Post($data, true);
// or use the setter method:
$post->useMasterKey(true)->save();

// When creating queries, pass as parameter:
$query = Post::query(true);
// or use the setter method:
$query->userMasterKey(true);

// Other object methods that accept a $useMasterKey value are:
$post  = Post::create($data, true);
$posts = Post::all(true);

// To configure a model to always use Master Key, define
// a protected static property `$defaultUseMasterKey`:
class Post extends ObjectModel
{
    protected static $defaultUseMasterKey = true;
}

// Or use this to make all models use master key by default
ObjectModel::setDefaultUseMasterKey(true);
```

## Relations

Supported relations are:

* `belongsTo` and its complement `hasMany`
* `belongsToMany`, which stores parents ids in an array, and its complement `hasManyArray`

Please check the tests for examples on relations.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)

## License

MIT
