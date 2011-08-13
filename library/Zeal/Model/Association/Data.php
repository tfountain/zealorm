<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_Data implements Zeal_Model_Association_DataInterface
{
    /**
     * Boolean to indicate whether or not the data has been loaded
     *
     * @var boolean
     */
    protected $_loaded = false;

    /**
     * Boolean to indicate whether or not loading should be attempted, if has
     * not been done already
     *
     * @var boolean
     */
    protected $_loadRequired = true;

    /**
     * The object created the association
     *
     * @var object
     */
    protected $_model;

    /**
     * The mapper
     *
     * @var Zeal_MapperInterface
     */
    protected $_mapper;

    /**
     * A query object
     *
     * @var Zeal_Mapper_QueryInterface
     */
    protected $_query;

    /**
     * The association
     *
     * @var Zeal_Model_AssociationInterface
     */
    protected $_association;

    /**
     * Holds the loaded data
     *
     * @var object
     */
    protected $_object;

    protected $_data;

    public function __get($var)
    {
        if (!$this->_loaded && $this->_loadRequired) {
            $this->load();
        }

        return isset($this->_object->$var) ? $this->_object->$var : null;
    }

    public function __call($name, $arguments)
    {
        if (!$this->_loaded && $this->_loadRequired) {
            $this->load();
        }

        return $this->_object->$name($arguments);
    }

    public function __set($var, $value)
    {
        if (!$this->_object) {
        	$className = $this->getAssociation()->getClassName();
        	$this->_object = new $className();

			$this->_loadRequired = false;
        }

        $this->_object->$var = $value;
    }

    /**
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        if (!$this->_mapper) {
            $this->_mapper = $this->getAssociation()->getMapper();
        }

        return $this->_mapper;
    }

    /**
     * Returns a query object for this collection
     *
     * @return Zeal_Mapper_QueryInterface
     */
    public function query()
    {
        if (!$this->_query) {
            $this->_query = $this->getMapper()->buildAssociationQuery($this->getAssociation());
        }

        return $this->_query;
    }

    /**
     * Loads the association data
     *
     * @return void
     */
    public function load()
    {
        $this->_loaded = true;

        $this->_object = $this->getMapper()->lazyLoadObject($this);

        if ($this->_object && !is_object($this->_object)) {
           throw new Zeal_Model_Exception('Data load method for association \''.$this->getAssociation()->getShortname().'\' must return either an object or false');
        }
    }

    /**
     * (non-PHPdoc)
     * @see Model/Association/Zeal_Model_Association_DataInterface#clearCached()
     */
    public function clearCached()
    {
        $this->_loaded = false;
        $this->_object = null;
    }

    /**
     * Sets the object data
     *
     * @param object $object
     * @return Zeal_Model_Association_DataInterface
     */
    public function setObject($object)
    {
        $this->_object = $object;

        // prevent lazy loading, since we've populated the data manually
        $this->_loaded = true;

        return $this;
    }

    /**
     * Returns the object loaded by this data set
     *
     * @return object
     */
    public function getObject()
    {
        if (!$this->_loaded && $this->_loadRequired) {
            $this->load();
        }

        return $this->_object;
    }

    /**
     * Set data
     *
     * @param $data
     * @return Zeal_Model_Association_DataInterface
     */
    public function setData($data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Sets the association
     *
     * @param Zeal_Model_AssociationInterface $association
     * @return Zeal_Model_Association_DataInterface
     */
    public function setAssociation(Zeal_Model_AssociationInterface $association)
    {
        $this->_association = $association;

        return $this;
    }

    /**
     * Returns the association
     *
     * @return Zeal_Model_AssociationInterface
     */
    public function getAssociation()
    {
        return $this->_association;
    }

    /**
     * Sets the model
     *
     * @param object $model
     * @return Zeal_Model_Association_DataInterface
     */
    public function setModel($model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Returns the model
     *
     * @return object
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * __toString magic method. Proxies to the object's __toString()
     * method if it has one. Otherwise returns null.
     *
     * Since __toString methods cannot throw exceptions, the whole
     * action is wrapped in a try/catch and any errors are logged to
     * the PHP error log.
     *
     * @return string|null
     */
    public function __toString()
    {
        try {
            if (!$this->_loaded && $this->_loadRequired) {
                $this->load();
            }

            if ($this->_object && method_exists($this->_object, '__toString')) {
                return $this->_object->__toString();
            }

        } catch (Exception $e) {
            error_log('Error in __toString() method of '.get_class($this).' for association '.$this->getAssociation()->getShortname());
            error_log($e->getMessage());

            return '(Error)';
        }

        return '';
    }

    /**
     *
     * @param array $data
     * @return object
     */
    public function build(array $data = array())
    {
        $className = $this->getAssociation()->getClassName();
        $object = $this->getMapper()->arrayToObject($data);
        $this->getAssociation()->populateObject($object);

        $this->_object = $object;
        $this->_loadRequired = false;

        return $object;
    }

    /**
     *
     * @param array $data
     * @return boolean
     */
    public function create(array $data = array())
    {
        $object = $this->build($data);

        return $this->getMapper()->create($object);
    }
}
