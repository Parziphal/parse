<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\Model;

class Category extends Model
{
    public function posts()
    {
        return $this->hasManyArray (Post::class);
    }
}
