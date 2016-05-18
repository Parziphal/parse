<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\ObjectModel;
use Illuminate\Support\Collection;

class HasManyArray extends Relation
{
    protected $embeddedClass;
    
    protected $parentObject;
    
    protected $keyName;
    
    protected $collection;
    
    protected $childrenQueue = [];
    
    public function __construct($embeddedClass, $keyName, ObjectModel $parentObject)
    {
        $this->embeddedClass = $embeddedClass;
        $this->parentObject  = $parentObject;
        $this->keyName       = $keyName;
        $this->collection    = new Collection();
        
        $this->createItems();
    }
    
    /**
     * Pass calls to the collection.
     */
    public function __call($method, $params)
    {
        $ret = call_user_func_array([$this->collection, $method], $params);
        
        if ($ret === $this->collection) {
            return $this;
        }
        
        return $ret;
    }
    
    public function getResults()
    {
        return $this;
    }
    
    public function getCollection()
    {
        return $this->collection;
    }
    
    /**
     * Save one or more children to this relation. The children
     * will have a pointer created to the parent in a key named
     * after the parent's parse class name. For example:
     *
     * ```
     * $post->comments()->save($comment);
     * $comment->post; // Instance of Post
     * $comment->getParseObject()->post; // Fetched pointer
     * ```
     *
     * Both parent and children are saved automatically.
     *
     * The children will be added with `addUnique` or `add`
     * depending on the $unique parameter.
     *
     * @param ObjectModel|ObjectModel[]  $others
     * @param bool                       $unique
     */
    public function save($others, $unique = true)
    {
        if (!is_array($others)) {
            $this->addOne($others, $unique);
        } else {
            foreach ($others as $other) {
                $this->addOne($other, $unique);
            }
        }
        
        $this->parentObject->save();
        
        /**
         * Children are related to parent after parent is saved
         * because the following error would occur otherwise:
         *
         * cannot route batch path /1/classes/Bar/ELDKVgsLuL
         */
        $this->relateParentToChildren();
    }
    
    protected function createItems()
    {
        $items = $this->parentObject->getParseObject()->get($this->keyName);
        
        if ($items) {
            $class = $this->embeddedClass;
            
            foreach ($items as $item) {
                $this->collection[] = new $class($item);
            }
        }
    }
    
    protected function addOne(ObjectModel $other, $unique = true)
    {
        $parentParse = $this->parentObject->getParseObject();
        
        $count = count($parentParse->{$this->keyName});
        
        if ($unique) {
            $this->parentObject->addUnique($this->keyName, [$other->getParseObject()]);
        } else {
            $this->parentObject->add($this->keyName, [$other->getParseObject()]);
        }
        
        if ($count < count($parentParse->{$this->keyName})) {
            $this->childrenQueue[] = $other;
        
            $this->collection[] = $other;
        }
    }
    
    protected function relateParentToChildren()
    {
        $key = lcfirst($this->parentObject->parseClassName());
        
        foreach ($this->childrenQueue as $object) {
            $object->set($key, $this->parentObject)->save();
        }
        
        $this->childrenQueue = [];
    }
}
