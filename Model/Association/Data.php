<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_Data extends Zeal_Model_Association_DataAbstract implements Zeal_Model_Association_DataInterface
{
    /**
     * A query object
     *
     * @var Zeal_Mapper_QueryInterface
     */
    protected $_query;

    /**
     * Holds the loaded data
     *
     * @var object
     */
    protected $object;

    protected $_data;

    public function __get($var)
    {
        if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return isset($this->object->$var) ? $this->object->$var : null;
    }

    public function __call($name, $arguments)
    {
        if (!$this->loaded && $this->loadRequired) {
            $this->load();
        }

        if (!$this->object || !is_object($this->object)) {
        	throw new Zeal_Model_Exception("Unable to call function '$name' on the object for association '".$this->getAssociation()->getShortname()."' as no object exists");
        }

        return $this->object->$name($arguments);
    }

    public function __set($var, $value)
    {
        if (!$this->object) {
        	$className = $this->getAssociation()->getClassName();
        	$this->object = new $className();

			$this->loadRequired = false;
        }

        $this->object->$var = $value;
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
        $this->loaded = true;

        $this->object = $this->getMapper()->lazyLoadObject($this);

        if ($this->object && !is_object($this->object)) {
           throw new Zeal_Model_Exception('Data load method for association \''.$this->getAssociation()->getShortname().'\' must return either an object or false');
        }
    }

    /**
     * (non-PHPdoc)
     * @see Model/Association/Zeal_Model_Association_DataInterface#clearCached()
     */
    public function clearCached()
    {
        $this->loaded = false;
        $this->loadRequired = true;

        $this->object = null;
    }

    /**
     * Sets the object data
     *
     * @param object $object
     * @return Zeal_Model_Association_DataInterface
     */
    public function setObject($object)
    {
        $this->object = $object;

        // prevent lazy loading, since we've populated the data manually
        $this->loadRequired = false;

        return $this;
    }

    /**
     * Returns the object loaded by this data set
     *
     * @return object
     */
    public function getObject($lazyLoad = true)
    {
        if ($lazyLoad && !$this->loaded && $this->loadRequired) {
            $this->load();
        }

        return $this->object;
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

            if ($this->object && method_exists($this->object, '__toString')) {
                return $this->object->__toString();
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

        $this->object = $object;
        $this->loadRequired = false;

        return $object;
    }

    /**
     *
     * @param array $data
     * @return boolean
     */
    public function create(array $data = array())
    {
    	if ($this->getObject()) {
    		if ($data) {
    			// what should happen here? TODO

    		} else {
    			$object = $this->object;
    		}
    	} else if ($data) {
			$object = $this->build($data);

    	} else {

    	}

        return $this->getMapper()->create($object);
    }
}
