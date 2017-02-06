<?php

namespace Illuminate\Parse\Test\Models;

use Illuminate\Parse\Model;

class Post extends Model
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
