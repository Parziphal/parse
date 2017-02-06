<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ParseModel;

class Post extends ParseModel
{
    public function categories()
    {
        return $this->belongsToMany (Category::class);
    }

    public function user()
    {
        return $this->belongsTo (User::class);
    }
}
