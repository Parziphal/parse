# Laravel Parse

This library pretends to make Parse usable in a more Eloquent-like manner. For Laravel 5.2.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an Eloquent-like interface.
* Enabled to work with Parse's relations (for now supporting "belongs to", "has many" and "has many in array").
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

    php artisan vendor:publish

Set your configuration in `config/parse.php`, or in your `.env` file by setting the following envs:

    PARSE_APP_ID     - App ID
    PARSE_REST_KEY   - REST API key
    PARSE_MASTER_KEY - Master key
    PARSE_SERVER_URL - Server URL (e.g. http://127.0.0.1:1337)
    PARSE_MOUNT_PATH - Server mount path (e.g. /parse)

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

## ObjectModels

The `Parziphal\Parse\ObjectModel` class is a wrapper for `Parse\ParseObject`. It behaves as an Eloquent model, so you could do stuff like:

```php
// Instantiate with data
$post = new Post(['title' => 'Some Title']);

// Create
$post = Post::create(['title' => 'Some Title']);

// Get objectId
echo $post->id;   // EWFppWR4qf
echo $post->id(); // EWFppWR4qf

// Update
$post->title = "New Title";
$post->save();

$post->update(['foo' => true]);

// Find or fail
$post = Post::findOrFail($id);

// Get all records
$posts = Post::all();

// Using master key
// Pass as second parameter when instantiating
$post = new Post($data, true);
// or set later
$post->useMasterKey(true)->save();
```

## Queries

`Parziphal\Parse\Query` is a wrapper for `Parse\ParseQuery`, which also behaves like a Eloquent Builder:

```php
// Note that `get` is like Eloquent Builder's `get`, which executes the query,
// and not like ParseQuery's `get` which finds an object by id.
$posts = Post::where('createdAt', '<=', $date)->descending('score')->get();

// Using master key, same as with ObjectModel
// Pass as parameter in ObjectModel::query()
$query = Post::query(true)->containedIn('foo', $foos);
// or call the instance method
$query->useMasterKey(true)->findOrFail($id);
// Models created by Query will have the same `useMasterKey` value as the Query.
```

## Relations

Supported relations are `belonogsTo`, `hasMany` and `hasManyArray` (which stores pointers in an array attribute).

Please check the tests for examples.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)

## License

MIT
