<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\ParseModel;

class Category extends ParseModel
{
    public function posts()
    {
        return $this->hasManyArray (Post::class);
    }
}
