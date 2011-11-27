<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_MapperAbstract implements Zeal_MapperInterface
{
    /**
     * The adapter for use by this mapper
     *
     * @var Zeal_Mapper_AdapterInterface
     */
    protected $_adapter;

    /**
     * Global plugins, applied to all data mappers
     *
     * @var array
     */
    static protected $_globalPlugins = array();

    /**
     * Plugins for this instance only
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * The name of the class
     *
     * @var string
     */
    protected $_className;

    /**
     * The fields
     *
     * @var array
     */
    protected $_fields = array();

    /**
     *
     * @var array
     */
    static protected $_globalFieldTypes = array();

    /**
     *
     * @var array
     */
    protected $_fieldTypes = array();

    /**
     * Options for the mapper adapter
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Constructor. Initialises the data mapper and any plugins
     *
     * @return void
     */
    public function __construct()
    {
        $this->init();
        $this->initPlugins();
    }

    /**
     * Called by the constructor, override for custom initialisation
     * in child classes
     *
     * @return void
     */
    public function init()
    {

    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#getAdapter()
     */
    public function getAdapter()
    {
        if (!$this->_adapter) {
            $this->_adapter = new Zeal_Mapper_Adapter_Zend_Db();
            $this->_adapter->setMapper($this);
        }

        return $this->_adapter;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#setAdapter($adapter)
     */
    public function setAdapter(Zeal_Mapper_AdapterInterface $adapter)
    {
        $adapter->setMapper($this);

        $this->_adapter = $adapter;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#hasOption($key)
     */
    public function hasOption($key)
    {
    	if (array_key_exists($key, $this->_options)) {
    		return true;
    	}

    	return false;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#getOption($key)
     */
    public function getOption($key, $default = false)
    {
    	if ($this->hasOption($key)) {
    		return $this->_options[$key];
    	}

    	return $default;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#setOption($key, $value)
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;

        return $this;
    }

    /**
     * Sets the class
     *
     * @param string $className
     * @return void
     */
    public function setClassName($className)
    {
        $this->_className = $className;
    }

    /**
     * Returns the class name
     *
     * @return string
     */
    public function getClassName($data = null)
    {
        if (!$this->_className) {
            // assume that the class name is the same as the mapper class,
            // but with _Model_ instead of _Mapper
            if (strpos(get_class($this), '_Mapper_') !== false) {
                $this->_className = str_replace('_Mapper_', '_Model_', get_class($this));
            } else {
                throw new Zeal_Mapper_Exception('Class name not set in mapper \''.get_class($this).'\' and unable to determine class name from mapper class');
            }
        }

        return $this->_className;
    }

    /**
     * Sets the fields
     *
     * @param array $fields
     * @return Zeal_MapperInterface
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;

        return $this;
    }

    /**
     * Returns the fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Is the object cached?
     *
     * @param string $className
     * @param mixed $keyValue
     * @return boolean
     */
    public function isCached($className, $keyValue)
    {
        return Zeal_Identity_Map::isCached($className, $keyValue);
    }

    /**
     * Retrieve a cached object
     *
     * @param string $className
     * @param mixed $keyValue
     * @return object
     */
    public function getCached($className, $keyValue)
    {
        return Zeal_Identity_Map::get($className, $keyValue);
    }

    /**
     * Cache the object
     *
     * @param object $object
     * @param mixed $keyValue
     * @return void
     */
    public function cache($object, $keyValue)
    {
        Zeal_Identity_Map::store($object, $keyValue);
    }

    /**
     * Initialise the plugins
     *
     * @return void
     */
    public function initPlugins()
    {
        foreach ($this->getPlugins() as $plugin) {
            $plugin->init($this);
        }
    }

    /**
     * Returns the plugins for this data mapper
     *
     * @return array
     */
    public function getPlugins()
    {
        return self::$_globalPlugins + $this->_plugins;
    }

    /**
     * Registers a plugin (for this mapper instance only)
     *
     * @param Zeal_Mapper_PluginInterface $plugin
     * @return void
     */
    public function registerPlugin(Zeal_Mapper_PluginInterface $plugin)
    {
        $this->_plugins[] = $plugin;
    }

    /**
     * Registers a global plugin
     *
     * @param Zeal_Mapper_PluginInterface $plugin
     * @return void
     */
    static public function registerGlobalPlugin(Zeal_Mapper_PluginInterface $plugin)
    {
        self::$_globalPlugins[] = $plugin;
    }

    /**
     * Plugin callback
     *
     * @param string|array $actions callback name (e.g. 'preSave')
     * @param object $object
     * @return void
     */
    protected function _pluginCallback($actions, $object)
    {
        if (is_array($actions)) {
            foreach ($actions as $action) {
                $this->_pluginCallback($action, $object);
            }

        } else {
            foreach ($this->getPlugins() as $plugin) {
                $callbackResult = $plugin->$actions($object, $this);
                if ($callbackResult === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Converts a data array into a object
     *
     * @param array $data
     * @param boolean $guard
     * @return object
     */
    public function arrayToObject(array $data, $guard = true)
    {
        $className = $this->getClassName($data);
        if (!$className) {
            // check for class fields
            if (isset($this->_fields['class']) && $this->_fields['class'] == 'class' && !empty($data['class'])) {
                $className = $data['class'];
            }
        }

        $object = new $className();
        $fields = $this->getFields();
        $fieldTypes = Zeal_Orm::getFieldTypes();

        foreach ($data as $field => $value) {
            if (isset($fields[$field])) {
                $fieldType = $fields[$field];

                if (isset($fieldTypes[$fieldType])) {
                    $closure = $fieldTypes[$fieldType];
                    $data[$field] = $closure($value);

                } else {
                    switch ($fieldType) {
                        case 'boolean':
                            $data[$field] = (bool)$value;
                            break;

                        case 'integer':
                            $data[$field] = (int)$value;
                            break;

                        case 'serialized':
                            if (!empty($value) && is_string($value)) {
                                $data[$field] = empty($value) ? $value : unserialize($value);
                            } else {
                                $data[$field] = $value;
                            }
                            break;

                        case 'datetime':
                        	if ($value instanceof Zeal_Mapper_FieldType_DateTime) {
                        		$data[$field] = $value;
                        	} else {
                            	$data[$field] = new Zeal_Mapper_FieldType_DateTime($value);
                        	}
                            break;

                        case 'date':
                        	if ($value instanceof Zeal_Mapper_FieldType_Date) {
                        		$data[$field] = $value;
                        	} else {
                        		$data[$field] = new Zeal_Mapper_FieldType_Date($value.' 12:00:00');
                        	}
                            break;
                    }
                }
            }
        }

        $object->populate($data, $guard);

        $object->setDirty(false);

        return $object;
    }

    /**
     * Converts the result of an adapter query into an object
     *
     * This function is called on any data returned by the mapper's adapter. In most
     * cases this data will be in an array-type format, and so by default this calls
     * arrayToObject, but the function exists to allow custom functionality at the mapper
     * level for any adapters that return other data structures.
     *
     * @param mixed $result
     * @return object
     */
    public function resultToObject($result, $guard = true)
    {
        return $this->arrayToObject($result, $guard);
    }

    /**
     * Converts an object into an array of data suitable for storage
     *
     * @param object $object
     * @param array $fields which fields to include in the array, defaults
     * to all fields
     * @return array
     */
    public function objectToArray($object, $fields = null)
    {
        // default to all fields
        if (!$fields) {
            $fields = $this->getFields();
        }

        // start with the raw data from the object
        $data = array_intersect_key($object->toArray(), $fields);

        $fieldTypes = Zeal_Orm::getFieldTypes();

        foreach ($fields as $field => $fieldType) {
            if (array_key_exists($field, $data)) {

                $value = $data[$field];

                // handle custom field types
                if (isset($fieldTypes[$fieldType]) && !($value instanceof Zeal_Mapper_FieldTypeInterface)) {
                    $closure = $fieldTypes[$fieldType];
                    $value = $closure($value);
                }

                if ($value !== null) {
                    if ($fieldType == 'serialized') {
                        $value = serialize($value);

                    } else if (is_object($value) && $value instanceof Zeal_Mapper_FieldTypeInterface) {
                        // convert custom field types into a format for storage
                        $value = $value->getValueForStorage($this->getAdapter());
                        if (!is_scalar($value)) {
                            throw new Zeal_Mapper_Exception(get_class($value).'::getValueForStorage() must return a scalar value');
                        }
                    }
                }

                $data[$field] = $value;

            }
        }

        return $data;
    }

    /**
     * Save any associated objects
     *
     * @param $object
     * @return boolean
     */
    protected function _saveAssociated($object)
    {
        $associations = $object->getAssociations();
        if ($associations) {
            $nestableAssociations = $object->getNestableAssociations();
            foreach ($associations as $association) {
                switch ($association->getType()) {
                    case Zeal_Model_AssociationInterface::HAS_ONE:
                    case Zeal_Model_AssociationInterface::BELONGS_TO:
                        $associationData = $object->{$association->getShortname()};
                        if ($associationData instanceof Zeal_Model_Association_DataInterface) {
                            $associatedObject = $associationData->getObject();
                            if ($associatedObject && $associatedObject->isDirty()) {
                                if (in_array($association, $nestableAssociations)) {
                                    $association->populateObject($associatedObject);
                                    $association->getMapper()->save($associatedObject);
                                }  else {
                                    // data for an association that can't be saved!
                                    throw new Zeal_Mapper_Exception('Association \''.$association->getShortname().'\' contains data that requires saving but allow nested assignment is set to false');
                                }
                            }
                        } else {
                            // something has been put in the variable that is not an association data object
                            echo 'oh noes:';
                            var_Dump($associationData);
                            throw new Zeal_Mapper_Exception('Found something other than an association data object in '.get_class($this).'->'.$association->getShortname());
                        }
                        break;

                    case Zeal_Model_AssociationInterface::HAS_MANY:
                    case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                        $associatedObjects = $object->{$association->getShortname()}->getObjects();
                        foreach ($associatedObjects as $associatedObject) {
                            if ($associatedObject->isDirty()) {
                                if (in_array($association, $nestableAssociations)) {
                                    $association->populateObject($associatedObject);
                                    $association->getMapper()->save($associatedObject);
                                }  else {
                                    // data for an association that can't be saved!
                                    throw new Zeal_Mapper_Exception('Association \''.$association->getShortname().'\' contains data that requires saving but allow nested assignment is set to false');
                                }
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#find($id)
     */
    public function find($id)
    {
        $data = $this->getAdapter()->find($id);
        if ($data) {
            return $this->resultToObject($data, false);
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#fetchOne($query)
     */
    public function fetchOne($query)
    {
		return $this->fetchObject($query);
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#fetchObject($query)
     */
    public function fetchObject($query)
    {
        $data = $this->getAdapter()->fetchObject($query);
        if ($data) {
            return $this->resultToObject($data, false);
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#fetchAll($query)
     */
    public function fetchAll($query = null)
    {
        if (!$query) {
            $query = $this->query();
        }

        $data = $this->getAdapter()->fetchAll($query);
        if ($data) {
            $results = array();
            foreach ($data as $result) {
            	$results[] = $this->resultToObject($result, false);
            }

            return $results;
        }

        return array();
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#paginate($query, $currentPage, $itemsPerPage)
     */
    public function paginate($query = null, $currentPage = 1, $itemsPerPage = 30)
    {
        if (!$query) {
            $query = $this->query();
        }

        $paginatorAdapter = new Zeal_Mapper_Paginator_Adapter();
        $paginatorAdapter->setQuery($query)
            ->setMapper($this);

        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($currentPage)
            ->setItemCountPerPage($itemsPerPage);

        return $paginator;
    }

    /**
     * Create an object
     *
     * @param object $object
     * @return boolean
     */
    protected function _create($object)
    {
        return $this->getAdapter()->create($object);
    }

    /**
     * Create an object, along with associated objects and callbacks
     *
     * @param object $object
     * @return boolean
     */
    public function create($object)
    {
        // preSave, preCreate callback
        if ($this->_pluginCallback(array('preSave', 'preCreate'), $object)) {

            // create
            if ($this->_create($object)) {
                // create/update any associated objects
                $this->_saveAssociated($object);

                // postCreate, postSave callback
                $this->_pluginCallback(array('postCreate', 'postSave'), $object);
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Save changes to the supplied object
     *
     * @param $object
     * @param null|array $fields
     * @return boolean
     */
    protected function _update($object, $fields = null)
    {
        return $this->getAdapter()->update($object, $fields);
    }

    /**
     * Saves changes to the supplied object, along with callbacks
     *
     * @param $object
     * @param null|array $fields
     * @return boolean
     */
    public function update($object, $fields = null)
    {
        // preSave callback
        $this->_pluginCallback(array('preSave', 'preUpdate'), $object);

        if ($this->_update($object)) {
            // create/update any associated objects
            $this->_saveAssociated($object);

            // postSave callback
            $this->_pluginCallback(array('postSave', 'postUpdate'), $object);

            return true;
        }

        return false;
    }

    /**
     * Save changes to the object
     *
     * @param $object
     * @return boolean
     */
    protected function _save($object)
    {
        return $this->getAdapter()->save($object);
    }

    /**
     * Save the object, with callbacks
     *
     * This will create the object if it has not previously been saved,
     * or update it if it has
     *
     * @param $object
     * @return boolean
     */
    public function save($object)
    {
        // preSave callback
        $this->_pluginCallback('preSave', $object);

        if ($this->_save($object)) {
            // create/update any associated objects
            $this->_saveAssociated($object);

            // postSave callback
            $this->_pluginCallback('postSave', $object);

            return true;
        }

        return false;
    }

    /**
     * Deletes the supplied object
     *
     * @param $object
     * @return boolean
     */
    protected function _delete($object)
    {
        return $this->getAdapter()->delete($object);
    }

    /**
     * Deletes the object, with callbacks
     *
     * @param $object
     * @return boolean
     */
    public function delete($object)
    {
        // preDelete callback
        $this->_pluginCallback('preDelete', $object);

        $success = $this->_delete($object);

        // postDelete callback
        $this->_pluginCallback('postDelete', $object);

        return $success;
    }

    /**
     * Build a query object
     *
     * @return Zeal_Mapper_QueryInterface
     */
    public function query()
    {
        $query = $this->getAdapter()->query();

        // query callback
        $this->_pluginCallback('query', $query);

        return $query;
    }

    /**
     * (non-PHPdoc)
     * @see Zeal_MapperInterface#count($query)
     */
    public function count(Zeal_Mapper_QueryInterface $query)
    {
		return $this->getAdapter()->count($query);
    }

    /**
     * Builds an association class for use in a Model
     *
     * @param $invokingClass
     * @param integer $type
     * @param string $associationShortname
     * @param array $options
     * @return Zeal_Model_AssociationInterface
     */
    public function buildAssociation($type, $options = array())
    {
        switch ($type) {
            case Zeal_Model_AssociationInterface::BELONGS_TO:
                $association = new Zeal_Model_Association_BelongsTo($options);
                break;

            case Zeal_Model_AssociationInterface::HAS_ONE:
                $association = new Zeal_Model_Association_HasOne($options);
                break;

            case Zeal_Model_AssociationInterface::HAS_MANY:
                $association = new Zeal_Model_Association_HasMany($options);
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                $association = new Zeal_Model_Association_HasAndBelongsToMany($options);
                break;

            default:
                throw new Zeal_Mapper_Exception('Invalid association type');
                break;
        }

        return $association;
    }

    public function buildAssociationQuery(Zeal_Model_AssociationInterface $association)
    {
        $query = $this->getAdapter()->populateQueryForAssociation($this->query(), $association);

        return $query;
    }

    public function lazyLoadObject(Zeal_Model_Association_DataInterface $data)
    {
        $query = $this->buildAssociationQuery($data->getAssociation());
        if ($query) {
        	return $this->fetchObject($query);
        }

        return null;
    }

    public function lazyLoadObjects(Zeal_Model_Association_Data_CollectionInterface $collection)
    {
        $query = $this->buildAssociationQuery($collection->getAssociation());
        if ($query) {
        	return $this->fetchAll($query);
        }

        return array();
    }
}
