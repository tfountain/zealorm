<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Identity_Map
{
    static protected $_objects = array();

    /**
     * Stores an object in the Identity Map
     *
     * @param mixed $object
     * @param mixed $key
     * @return mixed
     */
    static public function store($object, $key)
    {
        $class = get_class($object);
        self::$_objects[$class][$key] = $object;
    }

    /**
     * Retreive an object from the Identity Map
     *
     * @param string $className
     * @param mixed $key
     * @return mixed|null
     */
    static public function get($className, $key)
    {
        if (self::isCached($className, $key)) {
            return self::$_objects[$className][$key];
        }

        return null;
    }

    /**
     * Returns whether or not an object with the supplied params exists in the map
     *
     * @param string $class
     * @param mixed $key
     * @return boolean
     */
    static public function isCached($class, $key)
    {
        return (isset(self::$_objects[$class]) && isset(self::$_objects[$class][$key]));
    }

    /**
     * Clears all cached objects from the identity map
     *
     * Used mainly to aid unit testing
     *
     * @return void
     */
    static public function clearAll()
    {
        self::$_objects = array();
    }

    /**
     * Var dumps all cached objects
     *
     * For debugging purposes only
     *
     * @return unknown_type
     */
    static public function dumpAll()
    {
        var_dump(self::$_objects);
    }
}