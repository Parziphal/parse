<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ParseModel;

class User extends ParseModel
{
    public function posts()
    {
        return $this->hasMany (Post::class);
    }
}
