<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Orm
{
    /**
    * @var Zeal_Mapper_Registry
     */
    protected static $mapperRegistry;

    /**
     * @var array
     */
    protected static $fieldTypes = array();

    /**
     * Returns the mapper registry instance
     *
     * @return Zeal_Mapper_Registry
     */
    static public function getMapperRegistry()
    {
        if (!self::$mapperRegistry) {
            self::$mapperRegistry = new Zeal_Mapper_Registry();
        }

        return self::$mapperRegistry;
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
        if (!self::$mapperRegistry) {
            self::$mapperRegistry = new Zeal_Mapper_Registry();
        }

        return self::$mapperRegistry->getMapper($class);
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

    /**
     *
     * @param $fieldType
     * @param $closure
     * @return void
     */
    static public function registerFieldType($fieldType, $closure)
    {
        if (isset(self::$fieldTypes[$fieldType])) {
            throw new Zeal_Exception('Field type \''.htmlspecialchars($fieldType).'\' is already registered');
        }

        self::$fieldTypes[$fieldType] = $closure;
    }

    /**
     *
     * @return array
     */
    static public function getFieldTypes()
    {
        return self::$fieldTypes;
    }
}
