<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\ParseModel;

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
