<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_Data_Collection implements Zeal_Model_Association_Data_CollectionInterface, ArrayAccess, Countable
{
    /**
     * Boolean to indicate whether or not the data has been loaded
     *
     * @var boolean
     */
    protected $_loaded = false;

    /**
     * Loaded objects
     *
     * @var array
     */
    protected $_objects = array();

    /**
     * IDs of loaded objects
     *
     * @var array
     */
    protected $_objectIDs = array();

    /**
     * A query object for this collection
     *
     * @var Zeal_Mapper_QueryInterface
     */
    protected $_query;

    /**
     *
     *
     * @var mixed
     */
    protected $_data;

    /**
     * Returns the objects for use by IteratorAggregate
     *
     * @return array
     */
    public function getIterator()
    {
        if (!$this->_loaded) {
            $this->load();
        }

        return new ArrayIterator($this->_objects);
    }

    /**
     * Sets an association
     *
     * @param Zeal_Model_AssociationInterface $association
     * @return Zeal_Model_Association_Data_CollectionInterface
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
     * Populates the model that invoked this association
     *
     * @param object $model
     * @return Zeal_Model_Association_Data_Collection
     */
    public function setModel($model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Returns the model that created the association
     *
     * @return object
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Returns the mapper for the model this association loads
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        return Zeal_Orm::getMapper($this->getAssociation()->getClassName());
    }

    /**
     * Load the data
     *
     * @return void
     */
    public function load()
    {
        if ($this->_loaded) {
            throw new Zeal_Model_Exception('Attempted to load collection data multiple times');
        }

        // set the flag so loading isn't attempted more than once
        $this->_loaded = true;

        // lazy load the objects and store
        $this->_objects = $this->getMapper()->lazyLoadObjects($this);

        // ensure we got the right sort of data
        if (!is_array($this->_objects)) {
            throw new Zeal_Model_Exception('Lazy loading of collection objects must return an array, '.get_class($this->getMapper()).' returned '.gettype($this->_objects));
        }
    }

    /**
     * (non-PHPdoc)
     * @see Model/Association/Data/Zeal_Model_Association_Data_CollectionInterface#clearCached()
     */
    public function clearCached()
    {
        $this->_loaded = false;
        $this->_objects = array();
    }

    /**
     * Populates the collection objects
     *
     * @param array $objects
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function setObjects($objects)
    {
        $this->_objects = $objects;

        // prevent lazy loading, since we've populated the data manually
        $this->_loaded = true;

        return $this;
    }

    /**
     * Returns an array of objects loaded by this collection
     *
     * @return array
     */
    public function getObjects()
    {
        if (!$this->_loaded) {
            $this->load();
        }

        return $this->_objects;
    }

    public function getObjectIDs()
    {
        if (!$this->_objectIDs) {
	        if (!$this->_loaded) {
	            $this->load();
	        }

	        foreach ($this->_objects as $object) {

	        }
        }

        return $this->_objectIDs;
    }

    /**
     * Set data
     *
     * @param mixed $data
     * @return Zeal_Model_Association_Data_Collection
     */
    public function setData($data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Returns the data this collection was populated with
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
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
     * Sets the query object for this collection
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function setQuery(Zeal_Mapper_QueryInterface $query)
    {
        $this->_query = $query;

        return $this;
    }

    /**
     * Creates a new collection with the supplied limit
     *
     * @param integer $limit
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function limit($limit)
    {
        $collection = new self();
        $collection->setModel($this->getModel())
                   ->setAssociation($this->getAssociation())
                   ->setQuery($this->query()->limit($limit));

        return $collection;
    }

    /**
     * Creates a new collection with objects ordered as supplied
     *
     * @param string $orderBy
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function order($orderBy)
    {
        $collection = new self();
        $collection->setModel($this->getModel())
                   ->setAssociation($this->getAssociation())
                   ->setQuery($this->query()->order($orderBy));

        return $collection;
    }

    /**
     * Creates a new collection with the where restriction limit
     *
     * @param string $where
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function where($where)
    {
        $collection = new self();
        $collection->setModel($this->getModel())
                   ->setAssociation($this->getAssociation())
                   ->setQuery($this->query()->where($where));

        return $collection;
    }

    /**
     * For ArrayAccess
     *
	 * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->_loaded) {
            $this->load();
        }

        if ($offset === null) {
            $this->_objects[] = $value;
        } else {
            $this->_objects[$offset] = $value;
        }
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetExists($offset)
    {
        if (!$this->_loaded) {
            $this->load();
        }

        return isset($this->_objects[$offset]);
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetUnset($offset)
    {
        if (!$this->_loaded) {
            $this->load();
        }

        unset($this->_objects[$offset]);
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetGet($offset)
    {
        if (!$this->_loaded) {
            $this->load();
        }

        return isset($this->_objects[$offset]) ? $this->_objects[$offset] : null;
    }

    /**
     * For Countable
     */
    public function count()
    {
        if (!$this->_loaded) {
            $this->load();
        }

        return count($this->_objects);
    }
}
