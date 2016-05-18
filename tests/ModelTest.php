<?php

namespace Parziphal\Parse\Test;

use Parse\ParseObject;
use Parziphal\Parse\ObjectModel;
use Parziphal\Parse\Test\Models\Foo;
use Parziphal\Parse\Test\Models\Bar;
use Parziphal\Parse\Test\Models\Baz;
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
        
        $foo = new Foo($data);
        
        $this->assertSame($data['n'], $foo->n);
        $this->assertNull($foo->id);
        
        $foo->save();
        
        $this->assertNotNull($foo->id);
        
        $stored = Foo::findOrFail($foo->id);
        
        $this->assertSame($stored->id, $foo->id);
        
        $foo->add('arr', 4);
        $foo->update(['n' => 2]);
        
        $foo = Foo::findOrFail($foo->id);
        
        $this->assertSame(2, $foo->n);
        $this->assertSame(4, count($foo->arr));
        
        $foo->destroy();
        
        $destroyed = false;
        
        try {
            Foo::findOrFail($foo->id);
        } catch (ModelNotFoundException $e) {
            $destroyed = true;
        }
        
        $this->assertSame(true, $destroyed);
    }
    
    public function testHasManyArray()
    {
        // Create Foo
        $foo = Foo::create(['n' => 2]);
        
        // Create Bar
        $bar = Bar::create(['n' => 1]);
        
        // Save $bar to 'bars' relation. This will
        // relate $bar to $foo.
        $foo->bars()->save($bar);
        
        // Get stored $foo.
        $stored = Foo::with('bars')->findOrFail($foo->id());
        
        // Check bars.
        $this->assertSame($bar->id, $stored->bars->first()->id);
        $this->assertSame($bar->foo->id, $stored->id);
        $this->assertSame(1, $stored->bars->first()->n);
        $this->assertSame(1, $stored->bars->count());
        
        $this->assertSame(2, $stored->bars->first()->foo->fetch()->n);
    }
    
    public function testHasMany()
    {
        // Create $foo
        $foo = Foo::create(['n' => 2]);
        
        for ($i = 0; $i < 3; $i++) {
            // Create baz and relate them to $foo
            $baz = Baz::create([
                'n' => $i,
                'foo' => $foo
            ]);
        }
        
        $this->assertSame(3, $foo->bazs->count());
        $this->assertSame($baz->id, $foo->bazs->last()->id);
        
        $foo = Foo::with('bazs')->findOrFail($foo->id);
        
        $this->assertSame(0, $foo->bazs[0]->n);
        $this->assertSame(1, $foo->bazs[1]->n);
        $this->assertSame(2, $foo->bazs[2]->n);
    }
}
