# Laravel Parse

This library pretends to make Parse usable in a Eloquent-like manner. For Laravel 5.3+.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an Eloquent-like interface.
* Enabled to work with Parse's relations.
* User authentication using username/password combinations and/or with Facebook.
* Command to create Models (`parse:model Foo`).

## Setup Parse server

Set your configuration in `config.json`

It's important to specify this setting: "userSensitiveFields": ["email", "username"]

## Setup

Add the service provider in your `config/app.php` file:

```php
'providers' => [
    // etc...
    Illuminate\Parse\ParseServiceProvider::class,
],
```

Publish the configuration file by running:

    php artisan vendor:publish

Set your configuration in `config/parse.php`, or in your `.env` file by setting the following envs:

    PARSE_APP_ID     - App ID
    PARSE_REST_KEY   - REST API key
    PARSE_MASTER_KEY - Master key
    PARSE_SERVER_URL - Server URL (e.g. http://127.0.0.1:1337)
    PARSE_MOUNT_PATH - Server mount path (e.g. /parse)

Create a model PasswordResets, : `parse:model PasswordResets`

## User model

The model would look like this:

```php
   
namespace App;
   
use Illuminate\Parse\Auth\UserModel;

/**
 * App\User
 *
 * @property string         $name
 * @property string         $username
 * @property string         $email
 * @property string         $password
 * @property bool           $emailVerified
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property \Carbon\Carbon $deletedAt
 */
class User extends UserModel
{
    const REGISTER_RULES = [
        'name' => 'required|max:255',
        'username' => 'required|min:6|max:255|unique:_User',
        'password' => 'required|min:8|confirmed',
    ];

    const LOGIN_RULES = [
        'username' => 'required|min:6',
        'password' => 'required'
    ];

    const FORGOT_RULES = [
        'username' => 'required|min:6'
    ];

    const RESET_RULES = [
        'token' => 'required',
        'username' => 'required|min:6',
        'password' => 'required|confirmed|min:8',
    ];

    const USERNAME = 'username';
}
```

## Log in with Parse

In `config/auth.php`, set your desired users driver (see below), and set the class of the User model you'd like to use (it must extend from `Illuminate\Parse\UserModel`). You could just make the default `App\User` class to extend `Illuminate\Parse\Auth\UserModel`, which is a User class ready to be used for authentication.

The config would look like this:

```php
'providers' => [
    'users' => [
        'driver' => 'parse-any',
        'model'  => App\User::class,
    ],
],
```

There are 3 users providers available:

* `parse` which requires users to have a username and a password
* `parse-facebook` which requires users to identify using their Facebook account
* `parse-any` which lets users authenticate with either username/password or Facebook

You can use the `Illuminate\Parse\Auth\AuthenticatesUsers` trait in your auth controller (`App\Http\Controllers\Auth\AuthController` by default). The trait has methods to handle Facebook authentication/registration. Just bind them to a route and you're ready to go.

## Registration with Parse

You can use the `Illuminate\Parse\Auth\RegistersUsers` trait in your register controller (`App\Http\Controllers\Auth\RegisterController` by default).

## Forgot password with Parse

You can use the `Illuminate\Parse\Auth\SendsPasswordResetEmails` trait in your forgot password controller (`App\Http\Controllers\Auth\ForgotPasswordController` by default).

## Reset password in with Parse

You can use the `Illuminate\Parse\Auth\ResetsPasswords` trait in your reset password controller (`App\Http\Controllers\Auth\ResetPasswordController` by default).

## Models

The `Illuminate\Parse\Model` class is a wrapper for `Parse\ParseObject`. It behaves as an Eloquent model, so you could do stuff like:

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

`Illuminate\Parse\Query` is a wrapper for `Parse\ParseQuery`, which also behaves like a Eloquent Builder:

```php
// Note that `get` is like Eloquent Builder's `get`, which executes the query,
// and not like ParseQuery's `get` which finds an object by id.
$posts = Post::where('createdAt', '<=', $date)->descending('score')->get();

$posts = Post::where([
    'creator' => $user,
    'title' => $title
  ])
  ->whereIn('foo', $foos)
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
class Post extends Model
{
    protected static $defaultUseMasterKey = true;
}

// Or use this to make all models use master key by default
Model::setDefaultUseMasterKey(true);
```

## Relations

Supported relations are `belongsTo`, `belongsToMany`, `hasMany`, and `hasManyArray` (which is the complement of `belongsToMany`).

Please check the tests for examples.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)
* Parziphal [parse](https://github.com/Parziphal/parse)

## License

MIT
