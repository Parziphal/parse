<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ParseModel;

class Category extends ParseModel
{
    public function posts()
    {
        return $this->hasManyArray (Post::class);
    }
}
