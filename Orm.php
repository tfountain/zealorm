<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Orm
{
    protected static $_mapperRegistry;

    /**
     * Returns the mapper registry instance
     *
     * @return Zeal_Mapper_Registry
     */
    static public function getMapperRegistry()
    {
        if (!self::$_mapperRegistry) {
            self::$_mapperRegistry = new Zeal_Mapper_Registry();
        }

        return self::$_mapperRegistry;
    }

    /**
     * Returns the mapper instance for the supplied class
     *
     * This is a function is a shorthand for the function with the same name
     * in the mapper registry, for developer convenience.
     *
     * @param string $class
     * @return Zeal_MapperInterface
     */
    static public function getMapper($class)
    {
        if (!self::$_mapperRegistry) {
            self::$_mapperRegistry = new Zeal_Mapper_Registry();
        }

        return self::$_mapperRegistry->getMapper($class);
    }

    /**
     * Returns any public properties of the supplied object
     *
     * @param object $object
     * @return array
     */
    static public function getPublicProperties($object)
    {
    	return array_keys(get_object_vars($object));
    }
}
