<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
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
    protected $query;

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
        if ($this->objectIDs) {
            // we already know the IDs of the objects so just load those
            // FIXME - this may avoid some of the overriding at the mapper level so needs refactoring
            $query = $this->getMapper()->query();
            foreach ($this->objectIDs as $id) {
                $query->orWhere($this->getMapper()->getAdapter()->getPrimaryKey().' = ?', $id);
            }
            $this->objects = $this->getMapper()->fetchAll($query);

        } else {
            // delegate back to the mapper
            $this->objects = $this->getMapper()->lazyLoadObjects($this);
        }

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

        return $this;
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
     * @param mixed $data
     * @throws Zeal_Model_Exception
     * @return object
     */
    public function populate($data)
    {
        $this->dirty = true;
        $className = $this->getAssociation()->getClassName();

        if (is_array($data)) {
            if (count($data) > 0) {
                if (is_object($data[0])) {
                    if ($data[0] instanceof $className) {
                        $this->setObjects($data);
                    } else {
                        throw new Zeal_Model_Exception('Objects passed to association \''.$this->getAssociation()->getShortname().'\' must be instances of '.$className);
                    }

                } else if (is_array($data[0])) {
                    foreach ($data as $row) {
                        $this->build($row);
                    }

                } else if (is_numeric($data[0]) && ($this->getAssociation() instanceof Zeal_Model_Association_HasAndBelongsToMany)) {
                    $this->objectIDs = $data;
                    $this->clearCached();

                } else {
                    throw new Zeal_Model_Exception('Invalid data in assignment to data collection');
                }
            }

        } else if (!is_null($data)) {
            throw new Zeal_Model_Exception('Invalid data ('.gettype($data).') passed as value for association \''.$this->getAssociation()->getShortname().'\'');
        }
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
        $object->setDirty(true);

        if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        $this->objects[] = $object;

        // mark the collection as dirty so the object will be saved
        $this->dirty = true;

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
        if (!$this->query) {
            $this->query = $this->getMapper()->buildAssociationQuery($this->getAssociation());
        }

        return $this->query;
    }

    /**
     * Sets the query object for this collection
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @return Zeal_Model_Association_Data_CollectionInterface
     */
    public function setQuery(Zeal_Mapper_QueryInterface $query)
    {
        $this->query = $query;

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
    public function where($where, $params = null)
    {
        $collection = new self();
        $collection->clearCached()
                   ->setModel($this->getModel())
                   ->setAssociation($this->getAssociation())
                   ->setQuery($this->query()->where($where, $params));

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

        $this->dirty = true;

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

        $this->dirty = true;

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

    /**
     * Returns true if the association data is 'dirty' and requires saving
     *
     * @return boolean
     */
    public function isDirty()
    {
        if ($this->dirty) {
            return true;
        }

        // otherwise we need to check each of the objects in this collection
        if ($this->objects) {
            foreach ($this->objects as $object) {
                if ($object->isDirty()) {
                    return true;
                }
            }
        }

        return false;
    }
}
