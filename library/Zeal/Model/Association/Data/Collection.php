<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_Data_Collection extends Zeal_Model_Association_DataAbstract implements Zeal_Model_Association_Data_CollectionInterface, ArrayAccess, Countable
{
    /**
     * The association
     *
     * @var Zeal_Model_AssociationInterface
     */
    protected $association;

    /**
     * Loaded objects
     *
     * @var array
     */
    protected $objects = array();

    /**
     * IDs of loaded objects
     *
     * @var array
     */
    protected $objectIDs = array();

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
        if (!$this->loaded) {
            $this->load();
        }

        return new ArrayIterator($this->objects);
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
        if ($this->loaded) {
            throw new Zeal_Model_Exception('Attempted to load collection data multiple times');
        }

        // set the flag so loading isn't attempted more than once
        $this->loaded = true;

        // lazy load the objects and store
        $this->objects = $this->getMapper()->lazyLoadObjects($this);

        // ensure we got the right sort of data
        if (!is_array($this->objects)) {
            throw new Zeal_Model_Exception('Lazy loading of collection objects must return an array, '.get_class($this->getMapper()).' returned '.gettype($this->objects));
        }
    }

    /**
     * (non-PHPdoc)
     * @see Model/Association/Data/Zeal_Model_Association_Data_CollectionInterface#clearCached()
     */
    public function clearCached()
    {
        $this->loaded = false;
        $this->objects = array();
    }

    /**
     * Populates the collection objects
     *
     * @param array $objects
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;

        // prevent lazy loading, since we've populated the data manually
        $this->loaded = true;

        return $this;
    }

    /**
     * Returns an array of objects loaded by this collection
     *
     * @param boolean $lazyLoad
     * @return array
     */
    public function getObjects($lazyLoad = true)
    {
    	if ($lazyLoad && !$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return $this->objects;
    }

    /**
     * (non-PHPdoc)
     * @see Model/Association/Data/Zeal_Model_Association_Data_CollectionInterface#getObjectIDs()
     */
    public function getObjectIDs()
    {
        if (!$this->objectIDs) {
            if (!$this->loaded) {
                $this->load();
            }

            // FIXME this is somewhat DB specific, not all adapters will have a primary key
            $primaryKey = $this->getMapper()->getAdapter()->getPrimaryKey();
            if ($primaryKey) {
                foreach ($this->objects as $object) {
                    $this->objectIDs[] = $object->$primaryKey;
                }
            } else {
                throw new Zeal_Model_Exception('Unable to all getObjectIDs() on a collection without a primary key');
            }
        }

        return $this->objectIDs;
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

        if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        $this->objects[] = $object;

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

        if ($this->getMapper()->create($object)) {
            if (!$this->loaded && $this->loadRequired) {
                $this->load();
            }

            $this->objects[] = $object;

            return true;
        }

        return false;
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

    public function __set($var, $value)
    {
    	var_dump($var);
    	var_dump($value);
    	var_Dump(debug_backtrace());
    	exit;
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
    	if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        if ($offset === null) {
            $this->objects[] = $value;
        } else {
            $this->objects[$offset] = $value;
        }
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetExists($offset)
    {
    	if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return isset($this->objects[$offset]);
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetUnset($offset)
    {
    	if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        unset($this->objects[$offset]);
    }

    /**
	 * For ArrayAccess
	 *
	 * @return boolean
     */
    public function offsetGet($offset)
    {
    	if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return isset($this->objects[$offset]) ? $this->objects[$offset] : null;
    }

    /**
     * For Countable
     */
    public function count()
    {
    	if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return count($this->objects);
    }
}
