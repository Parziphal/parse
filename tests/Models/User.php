<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\Model;

class User extends Model
{
    public function posts()
    {
        return $this->hasMany (Post::class);
    }
}
