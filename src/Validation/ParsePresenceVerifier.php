<?php

namespace Illuminate\Parse\Validation;

use Closure;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Parse\Query;
use Illuminate\Support\Str;
use Illuminate\Validation\PresenceVerifierInterface;

class ParsePresenceVerifier implements PresenceVerifierInterface
{
    use AppNamespaceDetectorTrait;

    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        $query = $this->table ($collection)->where ([$column => $value]);

        if (!is_null ($excludeId) && $excludeId != 'NULL') {
            $idColumn = ($idColumn != 'id') ? $idColumn : str_replace ('id', 'objectId', $idColumn);
            $query->where ($idColumn, '!=', $excludeId);
        }

        foreach ($extra as $key => $extraValue) {
            if ($extraValue instanceof Closure) {
                $query->where (function (Query $query) use ($extraValue) {
                    $extraValue($query);
                });
            } else {
                $this->addWhere ($query, $key, $extraValue);
            }
        }
        return $query->count ();
    }

    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        $query = $this->table ($collection)->whereIn ($column, $values);

        foreach ($extra as $key => $extraValue) {
            if ($extraValue instanceof Closure) {
                $query->where (function (Query $query) use ($extraValue) {
                    $extraValue($query);
                });
            } else {
                $this->addWhere ($query, $key, $extraValue);
            }
        }
        return $query->count ();
    }

    /**
     * Add a "where" clause to the given query.
     *
     * @param  \Illuminate\Parse\Query $query
     * @param  string $key
     * @param  string $extraValue
     * @return void
     */
    protected function addWhere($query, $key, $extraValue)
    {
        if ($extraValue === 'NULL') {
            $query->whereNull ($key);
        } elseif ($extraValue === 'NOT_NULL') {
            $query->whereNotNull ($key);
        } elseif (Str::startsWith ($extraValue, '!')) {
            $query->where ($key, '!=', mb_substr ($extraValue, 1));
        } else {
            $query->where ([$key => $extraValue]);
        }
    }

    /**
     * Get application namespace
     *
     * @return string
     */
    protected function getNamespace()
    {
        return $this->getAppNamespace ();
    }

    /**
     * Get a query builder for the given table.
     *
     * @param string $table
     * @return \Illuminate\Parse\Query
     */
    protected function table($table)
    {
        $className = $this->getNamespace () . $this->removePrefix ($table, "_");
        return new Query($table, $className);
    }

    /**
     * @param string $text
     * @param string $prefix
     * @return string
     */
    protected function removePrefix($text, $prefix)
    {
        if (0 === strpos ($text, $prefix))
            $text = substr ($text, strlen ($prefix));
        return $text;
    }

    /**
     * Set the connection to be used.
     *
     * @param  string $connection
     * @return void
     */
    public function setConnection($connection)
    {
    }
}
