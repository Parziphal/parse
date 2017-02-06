<?php

namespace Illuminate\Parse;

use Illuminate\Support\Facades\Session;
use Parse\ParseStorageInterface;

class SessionStorage implements ParseStorageInterface
{
    /**
     * Sets a key-value pair in storage.
     *
     * @param string $key The key to set
     * @param mixed $value The value to set
     *
     * @return null
     */
    public function set($key, $value)
    {
        Session::set ($key, $value);
    }

    /**
     * Remove a key from storage.
     *
     * @param string $key The key to remove.
     *
     * @return null
     */
    public function remove($key)
    {
        Session::remove ($key);
    }

    /**
     * Gets the value for a key from storage.
     *
     * @param string $key The key to get the value for
     *
     * @return mixed
     */
    public function get($key)
    {
        return Session::get ($key);
    }

    /**
     * Clear all the values in storage.
     *
     * @return null
     */
    public function clear()
    {
        Sessions::clear ();
    }

    /**
     * Save the data, if necessary.    This would be a no-op when using the
     * $_SESSION implementation, but could be used for saving to file or
     * database as an action instead of on every set.
     *
     * @return null
     */
    public function save()
    {
        Session::save ();
    }

    /**
     * Get all keys in storage.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys ($this->getAll ());
    }

    /**
     * Get all key-value pairs from storage.
     *
     * @return array
     */
    public function getAll()
    {
        return Session::all ();
    }
}
