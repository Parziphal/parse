<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ObjectModel;

class User extends ObjectModel
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
