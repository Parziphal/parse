# Laravel Parse

This library pretends to make Parse usable in a Eloquent-like manner. For Laravel 5.2+.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an Eloquent-like interface.
* Enabled to work with Parse's relations.
* User authentication with username/password combinations and/or with Facebook.
* Command to create ObjectModels (`artisan parse:model SomeModel`).

## Setup

Install the library with Composer:

    composer require parziphal/parse

Add the service provider in your `config/app.php` file:

```php
'providers' => [
    // etc...
    Parziphal\Parse\ParseServiceProvider::class,
],
```

Publish the configuration file by running:

    php artisan vendor:publish --tag=parse

The command creates a file at `config/parse.php`, where you can set your Parse server configuration, but instead of editing that file, you can set your configuration in your `.env` file by setting the following variables:

    PARSE_APP_ID=Your_App_ID
    PARSE_REST_KEY=REST_API_key
    PARSE_MASTER_KEY=Master_key
    PARSE_SERVER_URL=http://127.0.0.1:1337
    PARSE_MOUNT_PATH=/parse

The `REST_API_key` variable is optional as Parse doesn't require that key anymore.

## ObjectModels

Create models extending the `Parziphal\Parse\ObjectModel` class:

```php
namespace App;

use Parziphal\Parse\ObjectModel;

class Post extends ObjectModel
{
}
```

And that's it. However, remember that you can use the Artisan command `php artisan parse:model SomeModel` to easily create a model.

ObjectModels behave just as an Eloquent model, so you can do stuff like:

```php
// Instantiate with data
$post = new Post(['title' => 'Some Title']);

// Create
$post = Post::create(['title' => 'Some Title', 'acl' => $acl]);

// Get objectId
echo $post->id;   // EWFppWR4qf
echo $post->id(); // EWFppWR4qf

// Update
$post->title = 'New Title';
$post->save();
// or
$post->update(['foo' => true]);

// Find or fail
$post = Post::findOrFail($id);

// Delete is like Eloquent's delete: it will delete the object
$post->delete();
// To remove a key (ParseObject's `delete` method), use `removeKey`
$post->removeKey($someKey);

// Create a pointer object
$pointer = Post::pointer($postId);
```

## Relations

Supported relations are:

* `belongsTo` and its complement `hasMany`
* `belongsToMany`, which stores parents ids in an array, and its complement `hasManyArray`

You use them like this:

```php

use Parziphal\Parse\ObjectModel;

class Post extends ObjectModel
{
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Having the above class where categories() is a `belongsToMany` relation,
// the class Category would have a posts() relation of type `hasManyArray`:
class Category extends ObjectModel
{
    public function posts()
    {
        return $this->hasManyArray(Post::class);
    }
}

// This would be the User class:
class User extends ObjectModel
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Relate a post with a category (belongsToMany):
$post->categories()->save($someCategory);

// Relate a category with posts (inverse of above, hasManyArray):
$category->posts()->save($post);
$category->posts()->create($arrayWithPostData);

// Relate a post with a user (belongsTo):
$post->user = $user;
$post->save();

// Relate a use with a post (inverse of above, hasMany):
$user->posts()->create($arrayWithPostData);
```

## Queries

`Parziphal\Parse\Query` is a wrapper for `Parse\ParseQuery`, which also behaves like Eloquent Builder:

```php
// Note that `get` is like Eloquent Builder's `get`, which executes the query,
// and not like ParseQuery's `get` which finds an object by id.
$posts = Post::where('createdAt', '<=', $date)->descending('score')->get();

$posts = Post::where([
    ['status', '=', '1'],
    ['subscribed', '<>', '1']
  ])
  ->get();

$posts = Post::where([
    'creator' => $user,
    'title' => $title
  ])
  ->containedIn('foo', $foos)
  ->get();

$post = Post::firstOrCreate($data);

// Load relations (ParseQuery::include())
$posts = Post::with('creator', 'comments.user')->get();
```

## Using Master Key

Objects and queries can be configured to use Master Key with the `$useMasterKey` property. This can be done at class level, at instantiation, or by using the setter method:

```php
// In objects, pass a second parameter when instantiating:
$post = new Post($data, true);
// or use the setter method:
$post->useMasterKey(true);

// Passing an anonymous function will set useMasterKey to true,
// then execute the function, then useMasterKey will be set to false.
$post->useMasterKey(function($post) {
    $post->increment('views')->save();
});

// When creating queries, pass as parameter:
$query = Post::query(true);
// or use the setter method:
$query->useMasterKey(true);

// Other object methods that accept a $useMasterKey value are:
$post  = Post::create($data, true);
$posts = Post::all(true);

// To configure a single model to _always_ use master key, define
// a protected static property `$defaultUseMasterKey`:
class Post extends ObjectModel
{
    protected static $defaultUseMasterKey = true;
}

// Finally, you can make all models use master key with this:
Parziphal\Parse\ObjectModel::setDefaultUseMasterKey(true);
```

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

### Log in with Facebook

You can use the `Parziphal\Parse\Auth\AuthenticatesWithFacebook` trait in your auth controller along with (not instead of) Laravel's `Illuminate\Foundation\Auth\AuthenticatesUsers` trait. The `AuthenticatesWithFacebook` trait has methods to handle Facebook authentication/registration. Just bind the method (or methods) you need to a route and you're ready to go.

Below is the interface of the authentication/registration trait. Note that it can respond in two ways: with a redirection (the \*Redirect methods), or with JSON (the \*Api methods), which will respond with the `$apiResponse` array, which is there so you can customize it.

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

The trait expects to find the user's Facebook ID as the `id` parameter, and their access token as the `access_token` parameter.

### Log in with username/password

There are things to take into consideration regarding this:

* The validator returned by the `validator` method of Laravel's default registration controller has a `unique` constraint on the `email` parameter, which will trigger database searches, leading to an error; make sure to remove that `unique` constraint.

* You'll also have to change the `create` method according to your needs. It could look like this:

```php
protected function create(array $data)
{
    $user = new User();
    $user->name = $data['name'];
    $user->username = $data['email'];
    $user->password = $data['password'];
    $user->signUp();

    return $user;
}
```

Notice that the email is stored as the username, this is because on Parse, the `username` field is the login name of the user, so if you require users to login using their email, you'll have to store their email under the `username` key.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)

## License

MIT
