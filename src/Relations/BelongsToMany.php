<?php

namespace Parziphal\Parse\Relations;

use Illuminate\Support\Collection;
use Parziphal\Parse\ParseModel;

class BelongsToMany extends Relation
{
    protected $embeddedClass;

    protected $parentObject;

    protected $keyName;

    protected $collection;

    protected $childrenQueue = [];

    public function __construct($embeddedClass, $keyName, ParseModel $parentObject)
    {
        $this->embeddedClass = $embeddedClass;
        $this->parentObject = $parentObject;
        $this->keyName = $keyName;
        $this->collection = new Collection();

        $this->createItems ();
    }

    protected function createItems()
    {
        $items = $this->parentObject->getParseObject ()->get ($this->keyName);

        if ($items) {
            $class = $this->embeddedClass;

            foreach ($items as $item) {
                $this->collection[] = new $class($item);
            }
        }
    }

    /**
     * Pass calls to the collection.
     */
    public function __call($method, $params)
    {
        $ret = call_user_func_array ([$this->collection, $method], $params);

        if ($ret === $this->collection) {
            return $this;
        }

        return $ret;
    }

    public function getResults()
    {
        return $this->collection;
    }

    /**
     * Save one or more parents to this relation.
     *
     * ```
     * $post->comments()->save($comment);
     * ```
     *
     * This object is saved automatically.
     *
     * The children will be added with `addUnique` or `add`
     * depending on the $unique parameter.
     *
     * @param ParseModel|ParseModel[] $others
     * @param bool $unique
     */
    public function save($others, $unique = true)
    {
        if (!is_array ($others)) {
            $this->addOne ($others, $unique);
        } else {
            foreach ($others as $other) {
                $this->addOne ($other, $unique);
            }
        }

        $this->parentObject->save ();
    }

    protected function addOne(ParseModel $other, $unique = true)
    {
        $parentParse = $this->parentObject->getParseObject ();

        $count = count ($parentParse->{$this->keyName});

        if ($unique) {
            $this->parentObject->addUnique ($this->keyName, [$other->getParseObject ()]);
        } else {
            $this->parentObject->add ($this->keyName, [$other->getParseObject ()]);
        }

        if ($count < count ($parentParse->{$this->keyName})) {
            $this->childrenQueue[] = $other;

            $this->collection[] = $other;
        }
    }
}
