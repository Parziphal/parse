# Laravel Parse

This library pretends to make Parse usable in a Eloquent-like manner. For Laravel 5.2+.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an Eloquent-like interface.
* Enabled to work with Parse's relations.
* User authentication using username/password combinations and/or with Facebook.
* Command to create ObjectModels (`parse:model Foo`).

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

Set your Parse server configuration in `config/parse.php`, or in your `.env` file by setting the following envs:

    PARSE_APP_ID=App_ID
    PARSE_REST_KEY=REST_API_key
    PARSE_MASTER_KEY=Master_key
    PARSE_SERVER_URL=http://127.0.0.1:1337
    PARSE_MOUNT_PATH=/parse

## Log in with Parse

In `config/auth.php`, set your desired users driver (see below), and set the class of the User model you'd like to use (it must extend from `Parziphal\Parse\UserModel`). You could just make the default `App\User` class to extend `Parziphal\Parse\Auth\UserModel`, which is a User class ready to be used for authentication.

The config would look like this:

```php
'providers' => [
    'users' => [
        'driver' => 'parse',
        'model'  => App\User::class,
    ],
],
```

There are 3 users providers available:

* `parse` which requires users to have a username and a password
* `parse-facebook` which requires users to identify using their Facebook account
* `parse-any` which lets users authenticate with either username/password or Facebook

You can use the `Parziphal\Parse\Auth\AuthenticatesWithFacebook` trait in your auth controller (`App\Http\Controllers\Auth\AuthController` by default). The trait has methods to handle Facebook authentication/registration. Just bind them to a route and you're ready to go.

Note that the trait can respond in two ways: with a redirect, or with JSON. The JSON response can be configured:

```php
// Interface of Parziphal\Parse\Auth\AuthenticatesWithFacebook
// The *Api methods respond with $apiResponse.
trait AuthenticatesWithFacebook
{
    protected $apiResponse = ['ok' => true];

    public function logInOrRegisterWithFacebookApi(Request $request);

    public function logInOrRegisterWithFacebookRedirect(Request $request);

    public function registerWithFacebookRedirect(Request $request);

    public function registerWithFacebookApi(Request $request);

    public function registerAny(Request $request);
}
```

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

$post = Post::where($data)->firstOrCreate();
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

Supported relations are `belongsTo`, `belongsToMany`, `hasMany`, and `hasManyArray` (which is the complement of `belongsToMany`).

Please check the tests for examples.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)

## License

MIT
