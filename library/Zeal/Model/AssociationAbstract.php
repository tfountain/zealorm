<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_Model_AssociationAbstract implements Zeal_Model_AssociationInterface
{
    /**
     * The invoking model that this association was created by
     *
     * @var Zeal_ModelInterface
     */
    protected $model;

    /**
     * The data mapper for the model that this association
     * was created by
     *
     * @var Zeal_MapperInterface
     */
    protected $modelMapper;

    /**
     * The shortname this association was created with
     *
     * @var string
     */
    protected $shortname;

    /**
     * Class that this association returns
     *
     * @var string
     */
    protected $className;

    /**
     * The data mapper for the target class
     *
     * @var Zeal_MapperInterface
     */
    protected $mapper;

    /**
     * Options passed in to the association
     *
     * @var unknown_type
     */
    protected $options = array();

    /**
     * A query object
     *
     * @var Zeal_Mapper_QueryInterface
     */
    protected $query;


    /**
     * Constructor
     *
     * @param array|null $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options) {
            $this->options = $options;
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
        return array_key_exists($key, $this->options);
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
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        } else {
            return $default;
        }
    }

    /*
     * Sets an option
     *
     * @param string $key
     * @param mixed $value
     * @return Zeal_Model_AssociationAbstract
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }


    /**
     * (non-PHPdoc)
     * @see Model/Zeal_Model_AssociationInterface#getOptions()
     */
    public function getOptions()
    {
    	return $this->options;
    }

    /**
     * Returns the association type, which is one of the constants
     * defined in Zeal_Model_AssociationInterface
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Stores the class that created this association
     *
     * @param $model
     * @return Zeal_Model_AssociationInterface
     */
    public function setModel(Zeal_ModelInterface $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Gets the model
     *
     * @return Zeal_ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the association shortname
     *
     * @param string $shortname
     * @return Zeal_Model_AssociationInterface
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * Gets the association shortname
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Sets the class name
     *
     * @param string $className
     * @return Zeal_Model_AssociationInterface
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Gets the class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Sets the data mapper
     *
     * @param $mapper
     * @return Zeal_Model_AssociationInterface
     */
    public function setMapper(Zeal_MapperInterface $mapper)
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * Gets the data mapper for the class that this association returns
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        if (!$this->mapper) {
            if ($this->hasOption('mapper')) {
                $this->mapper = $this->getOption('mapper');

            } else {
	            $className = $this->getClassName();
	            if (!$className) {
    	            throw new Zeal_Model_Exception('Unable to retrieve mapper for association \''.htmlspecialchars($className).'\' as the class name has not been set');
	            }

	            $this->mapper = Zeal_Orm::getMapper($className);
            }
        }

        return $this->mapper;
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
        $this->modelMapper = $mapper;

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
        if (!$this->modelMapper) {
            if ($this->model) {
                $this->modelMapper = Zeal_Orm::getMapper(get_class($this->model));
            } else {
                throw new Zeal_Model_Exception('Unable to determine model mapper when Zeal_Model_AssociationAbstract::model is not set');
            }
        }

        return $this->modelMapper;
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

    /**
     * Returns a query object for this association
     *
     * @return Zeal_Mapper_QueryInterface
     */
    public function buildQuery()
    {
        if (!$this->query) {
            $this->query = $this->getMapper()->buildAssociationQuery($this);
        }

        return clone $this->query;
    }
}
