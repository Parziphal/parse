<?php

namespace Parziphal\Parse\Test;

use Parse\ParseObject;
use Parziphal\Parse\ObjectModel;
use Parziphal\Parse\Test\Models\Post;
use Parziphal\Parse\Test\Models\User;
use Parziphal\Parse\Test\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function testPersistance()
    {
        $data = [
            'n'   => 1,
            'b'   => true,
            'arr' => [1, 2, 3]
        ];

        $post = new Post($data);

        $this->assertSame($data['n'], $post->n);
        $this->assertNull($post->id);

        $post->save();

        $this->assertNotNull($post->id);

        $stored = Post::findOrFail($post->id);

        $this->assertSame($stored->id, $post->id);

        $post->add('arr', 4);
        $post->update(['n' => 2]);

        $post = Post::findOrFail($post->id);

        $this->assertSame(2, $post->n);
        $this->assertSame(4, count($post->arr));

        $post->destroy();

        $destroyed = false;

        try {
            Post::findOrFail($post->id);
        } catch (ModelNotFoundException $e) {
            $destroyed = true;
        }

        $this->assertSame(true, $destroyed);
    }

    public function testBelongsToMany()
    {
        $laravelCategory = Category::create(['name' => 'Laravel']);
        $parseCategory   = Category::create(['name' => 'Parse']);

        $post = Post::create(['title' => 'New post']);

        $post->categories()->save([
            $laravelCategory,
            $parseCategory
        ]);

        $post = Post::with('categories')->findOrFail($post->id);

        $this->assertSame(2, $post->categories->count());
        $this->assertSame($laravelCategory->id, $post->categories->first()->id);
        $this->assertSame($parseCategory->id, $post->categories[1]->id);
    }

    public function testHasManyArray()
    {
        $category = Category::create(['name' => 'Programming']);

        $postA = Post::create(['title' => 'Timeless post']);
        $postA->categories()->save($category);

        $postB = Post::create(['title' => 'Pressing buttons']);
        $postB->categories()->save($category);

        $category = Category::with('posts')->findOrFail($category->id);

        $this->assertSame(2, $category->posts->count());
        $this->assertSame($postA->id, $category->posts[0]->id);
        $this->assertSame($postB->id, $category->posts[1]->id);
    }

    public function testBelongsToAndHasMany()
    {
        $user = User::create(['name' => 'admin']);

        $post = Post::create([
            'user'  => $user,
            'title' => 'Admin post'
        ]);

        $post = Post::with('user')->findOrFail($post->id);

        $this->assertSame($user->id, $post->user->id);
        // User Has many users
        $this->assertSame($post->id, $user->posts[0]->id);
    }
}
