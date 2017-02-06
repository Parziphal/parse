<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\ParseModel;

class User extends ParseModel
{
    public function posts()
    {
        return $this->hasMany (Post::class);
    }
}
