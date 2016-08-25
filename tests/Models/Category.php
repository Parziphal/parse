<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ObjectModel;

class Category extends ObjectModel
{
    public function posts()
    {
        return $this->hasManyArray(Post::class);
    }
}
