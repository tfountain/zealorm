<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_Model_AssociationAbstract implements Zeal_Model_AssociationInterface
{
    /**
     * The invoking model that this association was created by
     *
     * @var Zeal_ModelInterface
     */
    protected $_model;

    /**
     * The data mapper for the model that this association
     * was created by
     *
     * @var unknown_type
     */
    protected $_modelMapper;

    /**
     * The shortname this association was created with
     *
     * @var string
     */
    protected $_shortname;

    /**
     * Class that this association returns
     *
     * @var string
     */
    protected $_className;

    /**
     * The data mapper for the target class
     *
     * @var Zeal_MapperInterface
     */
    protected $_mapper;

    /**
     * Options passed in to the association
     *
     * @var unknown_type
     */
    protected $_options = array();

    /**
     * Constructor
     *
     * @param array|null $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options) {
            $this->_options = $options;
        }
    }

    /**
     * Returns true if the supplied option exists
     *
     * @param string $key
     * @return boolean
     */
    public function hasOption($key)
    {
        return array_key_exists($key, $this->_options);
    }

    /**
     * Retreives the option with the specified key,
     *
     * @param string $key
     * @param string|null $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (array_key_exists($key, $this->_options)) {
            return $this->_options[$key];
        } else {
            return $default;
        }
    }

    /**
     * Returns the association type, which is one of the constants
     * defined in Zeal_Model_AssociationInterface
     *
     * @return integer
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Stores the class that created this association
     *
     * @param $model
     * @return Zeal_Model_AssociationInterface
     */
    public function setModel(Zeal_ModelInterface $model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Gets the model
     *
     * @return Zeal_ModelInterface
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Sets the association shortname
     *
     * @param string $shortname
     * @return Zeal_Model_AssociationInterface
     */
    public function setShortname($shortname)
    {
        $this->_shortname = $shortname;

        return $this;
    }

    /**
     * Gets the association shortname
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->_shortname;
    }

    /**
     * Sets the class name
     *
     * @param string $className
     * @return Zeal_Model_AssociationInterface
     */
    public function setClassName($className)
    {
        $this->_className = $className;

        return $this;
    }

    /**
     * Gets the class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->_className;
    }

    /**
     * Sets the data mapper
     *
     * @param $mapper
     * @return Zeal_Model_AssociationInterface
     */
    public function setMapper(Zeal_MapperInterface $mapper)
    {
        $this->_mapper = $mapper;

        return $this;
    }

    /**
     * Gets the data mapper for the class that this association returns
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        if (!$this->_mapper) {
            if ($this->hasOption('mapper')) {
                $this->_mapper = $this->getOption('mapper');

            } else {
	            $className = $this->getClassName();
	            if (!$className) {
    	            throw new Zeal_Model_Exception('Unable to retrieve mapper for association \''.htmlspecialchars($className).'\' as the class name has not been set');
	            }

	            $this->_mapper = Zeal_Orm::getMapper($className);
            }
        }

        return $this->_mapper;
    }

    /**
     * Sets the data mapper for the class that the association was
     * created by
     *
     * @param Zeal_MapperInterface $mapper
     * @return Zeal_Model_AssociationInterface
     */
    public function setModelMapper(Zeal_MapperInterface $mapper)
    {
        $this->_modelMapper = $mapper;

        return $this;
    }

    /**
     * Gets the data mapper for the class that the association was
     * created by
     *
     * @return Zeal_MapperInterface
     */
    public function getModelMapper()
    {
        if (!$this->_modelMapper) {
            if ($this->_model) {
                $this->_modelMapper = Zeal_Orm::getMapper(get_class($this->_model));
            } else {
                throw new Zeal_Model_Exception('Unable to determine model mapper when Zeal_Model_AssociationAbstract::_model is not set');
            }
        }

        return $this->_modelMapper;
    }

    /**
     *
     * @param $object
     * @return object
     */
    public function populateObject($object)
    {
        return $this->getMapper()->getAdapter()->populateObjectForAssociation($object, $this);
    }
}
