<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Registry
{
    protected $_instances = array();

    /**
     * Returns the Mapper class name for $class
     *
     * @param string $class
     * @return string
     */
    public function getMapperClassName($class)
    {
        if (substr($class, strpos($class, '_') + 1, 6) == 'Model_') {
            // replace 'Model' with 'Mapper'
            $mapperClassName = substr_replace($class, 'Mapper_', strpos($class, '_') + 1, 6);

        } else if (strpos($class, '_') !== false) {
            // insert Mapper just after the namespace
            $mapperClassName = substr_replace($class, 'Mapper_', strpos($class, '_') + 1, 0);

        } else {
            $mapperClassName = $class.'Mapper';
        }

        return $mapperClassName;
    }

    /**
     * Returns true if a mapper for the supplied class exists
     *
     * Note: calling this method will attempt to autoload the class if appropriate
     *
     * @param string|object $class
     * @return boolean
     */
    public function hasMapper($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (isset($this->_instances[$class]) || class_exists($this->getMapperClassName($class))) {
            return true;
        }

        return false;
    }

    /**
     * Returns a data mapper instance for the supplied class
     *
     * @param string|object $class
     * @return Zeal_MapperInterface
     */
    public function getMapper($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset($this->_instances[$class])) {
            $mapperClassName = $this->getMapperClassName($class);

            $this->_instances[$class] = new $mapperClassName();
        }

        return $this->_instances[$class];
    }

    /**
     * Register a data mapper for a class
     *
     * @param Zeal_MapperInterface $mapper
     * @param string $className
     * @return void
     */
    public function registerMapper(Zeal_MapperInterface $mapper, $className)
    {
        if (isset($this->_instances[$className])) {
            throw new Zeal_Mapper_Exception('Unable to register a data mapper for \''.htmlspecialchars($className).'\' as one already exists');
        }

        $this->_instances[$className] = $mapper;
    }

    /**
     * Clears all registered data mapper instances
     *
     * @return void
     */
    public function clear()
    {
        $this->_instances = array();
    }
}
