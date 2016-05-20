# Laravel Parse

This library pretends to make Parse usable in a more Eloquent-ish manner. For Laravel 5.2+.

## Features

* Initialize Parse automatically.
* Use facade classes that wraps Parse's classes, exposing an (again) Eloquent-ish interface.
* Enabled to work with Parse's relations (for now supporting "belongs to", "has many" and "has many in array").
* Login using Laravel's authentication framework with Parse users.
* Command to create ObjectModels (`parse:model Foo`).

## Setup

Publish the configuration file by running:

    php artisan vendor:publish

Set your configuration either in `config/parse.php`, or in your `.env` file by setting the following envs:

    PARSE_APP_ID     - App ID
    PARSE_REST_KEY   - REST key
    PARSE_MASTER_KEY - Master key
    PARSE_SERVER_URL - Server URL

## Login with Parse

In `config/auth.php`, set `parse` as users driver, and set the class of the User model you'd like to use (it must extend from `Parziphal\Parse\User`). The config would look like this:

```php
'providers' => [
    'users' => [
        'driver' => 'parse',
        'model' => App\User::class,
    ],
],
```

You could just make the default `App\User` class to extend `Parziphal\Parse\Auth\User`, which is a User class ready to be used for authentication.

## ObjectModels

The `Parziphal\Parse\ObjectModel` class is a wrapper for `Parse\ParseObject`. It behaves as a Eloquent model, so you could do stuff like:

```php
// Instantiate with data
$post = new Post(['title' => 'Some Title']);

// Create
$post = Post::create(['title' => 'Some Title']);

// Get objectId
echo $post->id;   // EWFppWR4qf
echo $post->id(); // EWFppWR4qf

$post->title = "New Title";
$post->save();

$post->update(['foo' => true]);

$post = Post::findOrFail($id);

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
```

## Relations

Supported relations are `belonogsTo`, `hasMany` and `hasManyArray` (which stores pointers in an array attribute).

Please check the tests for examples.

## Inspiration from

* GrahamCampbell's [Laravel-Parse](https://github.com/GrahamCampbell/Laravel-Parse/)
* HipsterJazzbo's [LaraParse](https://github.com/HipsterJazzbo/LaraParse)

## License

MIT
