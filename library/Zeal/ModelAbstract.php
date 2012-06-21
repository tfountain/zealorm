<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_ModelAbstract implements Zeal_ModelInterface, Serializable
{
    /**
     * An array of association objects
     *
     * @var null|array
     */
    protected $associations;

    /**
     * An array of association data objects
     *
     * @var null|array
     */
    protected $associationData;

    /**
     * An array of class level behaviours which are initialised when an instance
     * of the class is created
     *
     * @var null|array
     */
    static protected $classBehaviours;

    /**
    * @var null|array
    */
    protected $activeBehaviours;

    /**
    * @var null|array
    */
    protected $mappedBehaviourProperties;

    /**
    * @var null|array
    */
    protected $mappedBehaviourMethods;

    /**
    * @var null|array
    */
    protected $unsavedAssociationData;

    /**
     * @var boolean
     */
    protected $dirty;

    /**
     * Model constructor
     *
     * @param array $data data to store in the model
     * @return void
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->populate($data);
        }

        $this->init();

        $this->initBehaviours();
    }

    /**
     * Initialisation - called in the constructor, override in child
     * classes to initialise associations etc.
     *
     * @return void
     */
    public function init()
    {

    }

    /**
     * Store an array of data in the model
     *
     * @param array $data
     * @param boolean $guard
     * @return Zeal_ModelAbstract
     */
    public function populate(array $data, $guard = true)
    {
        foreach ($data as $key => $value) {
            if ($guard && $this->_isGuarded($key)) {
                throw new Zeal_Model_Exception('Unable to mass-assign guarded field \''.htmlspecialchars($key).'\'');
            }

            $this->__set($key, $value);
        }

        return $this;
    }

    /**
     *
     *
     * @param string $field
     * @return boolean
     */
    protected function _isGuarded($field)
    {
        if ($this->isAssociation($field)) {
            $association = $this->getAssociation($field);
            $allowNestedAssignment = $association->getOption('allowNestedAssignment');
            return !$allowNestedAssignment;
        }

        return false;
    }

    /**
     * Magic method for returning model data
     *
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
        $getMethodName = 'get'.ucfirst($var);
        if (method_exists($this, $getMethodName)) {
            // use the get method
            return $this->$getMethodName();

        } else if (isset($this->$var) || property_exists($this, $var)) {
            // init the association if that's what is
            if ($this->isAssociation($var)) {
                $this->_initAssociationData($var);

                if (isset($this->$var)) {
                    echo 'huh?';exit;
                }

            }

            // return the value
            return $this->$var;

        } else if ($this->isAssociation($var)) {
            // return association data
            return $this->_getAssociationData($var);

        } else if ($this->_isBehaviourProperty($var)) {
            // return behaviour property
            return $this->_getBehaviourProperty($var);
        }
    }

    /**
     * Magic method for setting model data
     *
     * @param string $var
     * @param mixed $value
     * @return void
     */
    public function __set($var, $value)
    {
        $setMethodName = 'set'.ucfirst($var);
        if (method_exists($this, $setMethodName)) {
            if (!$this->dirty) {
                $this->dirty = true;
            }

            // use the set method
            return $this->$setMethodName($value);

        } else if ($this->isAssociation($var)) {
            $this->dirty = true;

            $this->_initAssociationData($var);

            $this->associationData[$var]->populate($value);

        } else {
            if (!$this->dirty && $this->$var !== $value) {
                $this->dirty = true;
            }

            $this->$var = $value;
        }
    }

    /**
     * Magic method for model method calls
     *
     * This exists purely to proxy calls to behaviour classes
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($this->_isBehaviourMethod($name)) {
            return $this->_callBehaviourMethod($name, $arguments);
        }

        throw new Zeal_Model_Exception('Invalid method '.get_class($this).'::'.htmlspecialchars($name).'()');
    }

    /**
     * Magic method for checking if a value is set
     *
     * @param string $var
     * @return boolean
     */
    public function __isset($var)
    {
        $getMethodName = 'get'.ucfirst($var);
        if (method_exists($this, $getMethodName)) {
            $value = $this->$getMethodName();
            return empty($value);

        } else {
        	return isset($this->$var);
        }
    }

    /**
     *
     *
     */
    public function getUnsavedAssociationData()
    {
        $unsavedAssociationData = array();

        if ($this->associationData) {
            foreach ($this->associationData as $associationData) {
                $associationShortname = $associationData->getAssociation()->getShortname();

                if ($associationData instanceof Zeal_Model_Association_DataInterface) {
                    $object = $associationData->getObject();
                    if ($object && $object->isDirty()) {
                        $data = $object->toArray();
                        $data = array_merge($data, $object->getUnsavedAssociationData());

                        $unsavedAssociationData[$associationShortname] = $data;
                    }

                } else if ($associationData->getAssociation() instanceof Zeal_Model_Association_HasAndBelongsToMany) {
                    $unsavedAssociationData[$associationShortname] = $associationData->getObjectIDs();

                } else if ($associationData instanceof Zeal_Model_Association_Data_CollectionInterface) {
                    $objects = $associationData->getObjects();
                    if ($objects) {
                        $unsavedAssociationData[$associationShortname] = array();
                        foreach ($objects as $object) {
                            $data = $object->toArray();
                            $data = array_merge($data, $object->getUnsavedAssociationData());

                            $unsavedAssociationData[$associationShortname][] = $data;
                        }
                    }
                }
            }
        }

        return $unsavedAssociationData;
    }

    /**
     * Part of Serializable. Returns a serialized form of the model.
     *
     * @return string
     */
	public function serialize()
	{
	    // start with the object data
		$data = $this->toArray();

		// add any public properties
		$publicProperties = Zeal_Orm::getPublicProperties($this);
		if ($publicProperties) {
		    foreach ($publicProperties as $key) {
		        $data[$key] = $this->$key;
		    }
		}

		// and any unsaved association data
		$data = array_merge($data, $this->getUnsavedAssociationData());


		return serialize($data);
	}

	/**
	 * Part of Serializeable, restores object state when unserialize($model) is called.
	 *
	 * @param array $data
	 */
	public function unserialize($data)
	{
	    // restore any associations
	    $this->init();

	    // and populate model
	    $this->populate(unserialize($data));
	}

    /**
     * Returns an array of data held by this model
     *
	 * @param null|array $includeAssociations
	 * @throws Zeal_Model_Exception
	 */
    public function toArray($includeAssociations = null)
    {
    	$mapper = Zeal_Orm::getMapper($this);
		$fields = $mapper->getFields();

		$data = array();
		foreach ($fields as $field => $fieldType) {
			$data[$field] = isset($this->$field) ? $this->$field : null;
		}

		if ($includeAssociations) {
		    if (!is_array($includeAssociations)) {
		        throw new Zeal_Model_Exception('Invalid parameter supplied to Zeal_ModelAbstract::toArray()');
		    }

			foreach ($includeAssociations as $associationShortname => $nestedAssociations) {
			    $associationData = $this->$associationShortname;
                if ($associationData) {
					if ($associationData instanceof Zeal_Model_Association_DataInterface) {
						$object = $associationData->getObject(false);
						if ($object) {
							$data[$associationData->getAssociation()->getShortname()] = $object->toArray($nestedAssociations);
						}
					} else if ($associationData instanceof Zeal_Model_Association_Data_CollectionInterface) {
						$objects = $associationData->getObjects();
						if ($objects) {
							$associationShortname = $associationData->getAssociation()->getShortname();
							$data[$associationShortname] = array();
							foreach ($objects as $object) {
							    // FIXME
								$data[$associationShortname][] = $object->toArray();
							}
						}
					} else {
						throw new Zeal_Model_Exception('Invalid association data type');
					}
				}
			}
		}

		return $data;
    }

    protected function unsavedAssociations()
    {
        $unsavedAssociations = array();

        foreach ($this->getAssociations() as $associationShortname => $association) {
            if (isset($this->associationData[$associationShortname])) {

            }
        }

        return $unsavedAssociations();
    }

    public function toArrayForSerialization()
    {
        $sleepFields = $this->__sleep();


    }

    /**
     * Initialises an association
     *
     * Creates an instance of the appropriate association class based on the
     * supplied type and stores this model.
     *
     * @param $type
     * @param $associationShortname
     * @param $options
     * @return void
     */
    protected function _initAssociation($type, $associationShortname, $options = array())
    {
        if (!$this->associations) {
            $this->associations = array();
        }

        // make sure it doesn't already exist
        if (array_key_exists($associationShortname, $this->associations)) {
            throw new Zeal_Model_Exception('Association \''.htmlspecialchars($associationShortname).'\' already exists');
        }

        // get the target mapper for the association
        if (isset($options['mapper'])) {
            if (!($options['mapper'] instanceof Zeal_MapperInterface)) {
                throw new Zeal_Model_Exception('Mapper specified for association \''.htmlspecialchars($associationShortname).'\' must implement Zeal_MapperInterface');
            }

            $mapper = $options['mapper'];

        } else {
            if (empty($options['className'])) {
                // TODO: any inflection based on the name of the association would go here!
                throw new Zeal_Model_Exception('No class name specified for association \''.htmlspecialchars($associationShortname).'\' in model \''.get_class($this).'\'');
            } else if (class_exists($options['className'])) {
                if (Zeal_Orm::getMapperRegistry()->hasMapper($options['className'])) {
                    $mapper = Zeal_Orm::getMapper($options['className']);

                } else {
                    throw new Zeal_Model_Exception('Could not find mapper for class \''.htmlspecialchars($options['className']).'\', specified for association \''.htmlspecialchars($associationShortname).'\' in model \''.get_class($this).'\'');
                }
            } else {
                throw new Zeal_Model_Exception('Invalid class name of \''.htmlspecialchars($options['className']).'\' specified for association \''.htmlspecialchars($associationShortname).'\' in model \''.get_class($this).'\'');
            }
        }

        // create assocation
        $association = $mapper->buildAssociation($type, $options);

        // populate the stuff it might need
        $association->setModel($this)
            ->setShortname($associationShortname)
            ->setClassName($mapper->getClassName());

        // store the association in the model
        $this->associations[$associationShortname] = $association;
    }

    /**
     * Create the association data object for the specified association
     *
     * @param Zeal_Model_Association|string $association
     * @throws Zeal_Model_Exception
     */
    protected function _initAssociationData($association)
    {
        if (!$this->associationData) {
            $this->associationData = array();
        }

        if (is_string($association)) {
            if (!array_key_exists($association, $this->associations)) {
                throw new Zeal_Model_Exception('Invalid association name passed to _setAssociationData');
            }

            $association = $this->associations[$association];
        }
        $associationShortname = $association->getShortname();

        if (!array_key_exists($associationShortname, $this->associationData)) {
            $associationData = $this->associations[$associationShortname]->initAssociationData();

            // populate stuff it might need
            $associationData->setAssociation($association)
                ->setModel($this);

            $this->associationData[$associationShortname] = $associationData;
        }
    }

    /**
     * Create a 'belongs to' association
     *
     * @param $associationShortname
     * @return void
     */
    public function belongsTo($associationShortname, $options = array())
    {
        $this->_initAssociation(Zeal_Model_AssociationInterface::BELONGS_TO, $associationShortname, $options);
    }

    /**
     * Create a 'has one' association
     *
     * @param $associationShortname
     * @return void
     */
    public function hasOne($associationShortname, $options = array())
    {
        $this->_initAssociation(Zeal_Model_AssociationInterface::HAS_ONE, $associationShortname, $options);
    }

    /**
     * Create a 'has many' association
     *
     * @param $associationShortname
     * @return void
     */
    public function hasMany($associationShortname, $options = array())
    {
        $this->_initAssociation(Zeal_Model_AssociationInterface::HAS_MANY, $associationShortname, $options);
    }

    /**
     * Create a 'has and belongs to many' association
     *
     * @param $associationShortname
     * @return void
     */
    public function hasAndBelongsToMany($associationShortname, $options = array())
    {
        $this->_initAssociation(Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY, $associationShortname, $options);
    }

    /**
     * Checks whether the shortname supplied is an association
     *
     * @param string $associationShortname
     * @return boolean
     */
    public function isAssociation($associationShortname)
    {
        return isset($this->associations[$associationShortname]);
    }

    /**
     * Returns an association
     *
     * @param string $associationShortname
     * @return Zeal_Model_AssociationInterface
     */
    public function getAssociation($associationShortname)
    {
        return $this->associations[$associationShortname];
    }

    /**
     * Returns an array of associations setup on this model
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * Returns an array of associations that can be saved when saving
     * parent classes
     *
     * @return array
     */
    public function getNestableAssociations()
    {
        $nestable = array();
        if ($this->getAssociations()) {
            foreach ($this->getAssociations() as $association) {
                if ($association->hasOption('allowNestedAssignment') && $association->getOption('allowNestedAssignment') === true) {
                    $nestable[] = $association;
                }
            }
        }

        return $nestable;
    }

    /**
     * Returns the association data class for the supplied association
     *
     * @param string $associationShortname
     * @return Zeal_Model_Association_DataInterface|Zeal_Model_Association_Data_CollectionInterface
     */
    protected function _getAssociationData($associationShortname)
    {
        if (!isset($this->associationData[$associationShortname])) {
            $this->_initAssociationData($associationShortname);
        }

        return $this->associationData[$associationShortname];
    }

    /**
     * Register a behaviour
     *
     * @param string $behaviourShortname
     * @param string $class
     * @return void
     */
    static public function registerBehaviour($behaviourShortname, $initOptions = null)
    {
        if (isset(static::$classBehaviours[$behaviourShortname])) {
            throw new Zeal_Model_Exception('A behaviour with the shortname \''.htmlspecialchars($behaviourShortname).'\' already exists');
        }

        static::$classBehaviours[$behaviourShortname] = $initOptions;
    }

    /**
     * Unregister all behaviours
     *
     * @return void
     */
    static public function unregisterAllBehaviours()
    {
        static::$classBehaviours = array();
    }

    /**
     * Activate a behaviour on a model
     *
     * @param string $behaviourShortname
     * @param array|null $options
     * @return void
     */
    public function actsAs($behaviourShortname, $options = null)
    {
        if (!$this->activeBehaviours) {
            $this->activeBehaviours = array();
        }

        $registeredBehaviours = Zeal_Orm::getRegisteredBehaviours();
        if (!isset($registeredBehaviours[$behaviourShortname])) {
            throw new Zeal_Model_Exception('Invalid behaviour: '.htmlspecialchars($behaviourShortname).'\' specified');
        }

        $behaviourClass = $registeredBehaviours[$behaviourShortname];
        if (!class_exists($behaviourClass)) {
            throw new Zeal_Model_Exception('Invalid behaviour class: '.htmlspecialchars($behaviourClass));
        }

        $behaviour = new $behaviourClass($options);
        $behaviour->setModel($this)
                  ->init();

        $this->activeBehaviours[$behaviourShortname] = $behaviour;
    }


    public function initBehaviours()
    {
        if (static::$classBehaviours) {
            $registeredBehaviours = Zeal_Orm::getRegisteredBehaviours();

            foreach (static::$classBehaviours as $behaviourShortname => $initOptions) {
                if (!isset($registeredBehaviours[$behaviourShortname])) {
                    throw new Zeal_Model_Exception('Attempted to initialise unregistered behaviour \''.htmlspecialchars($behaviourShortname).'\' in class '.get_class($this));
                }

                $behaviourClassName = $registeredBehaviours[$behaviourShortname];
                $behaviour = new $behaviourClassName($initOptions);
                $behaviour->setModel($this)
                          ->init();

                $this->activeBehaviours[$behaviourShortname] = $behaviour;
            }
        }
    }

    /**
     * Returns true if the model has the specified behaviour
     *
     * @param string $behaviourShortname
     * @return boolean
     */
    public function hasBehaviour($behaviourShortname)
    {
        return $this->activeBehaviours && isset($this->activeBehaviours[$behaviourShortname]);
    }

    /**
     * Returns true if $var is a property of any of the active behaviours
     *
     * @param string $var
     * @return boolean
     */
    protected function _isBehaviourProperty($var)
    {
        if (!$this->mappedBehaviourProperties) {
            $this->mappedBehaviourProperties = array();
        }

        if (isset($this->mappedBehaviourProperties[$var])) {
            return true;
        }

        if ($this->activeBehaviours) {
            foreach ($this->activeBehaviours as $behaviourShortname => $behaviourClass) {
                $properties = get_object_vars($behaviourClass);

                if (array_key_exists($var, $properties)) {
                    $this->mappedBehaviourProperties[$var] = $behaviourShortname;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets a behaviour property
     *
     * @param string $var
     * @return mixed
     */
    protected function _getBehaviourProperty($var)
    {
        if (isset($this->mappedBehaviourProperties[$var])) {
            // make sure the behaviour has been initialised - triggers any behaviour specific loading etc.
            if (!$this->activeBehaviours[$this->mappedBehaviourProperties[$var]]->isLoaded()) {
                $this->activeBehaviours[$this->mappedBehaviourProperties[$var]]->load();
            }

            return $this->activeBehaviours[$this->mappedBehaviourProperties[$var]]->$var;
        }

        throw new Zeal_Model_Exception('Invalid behaviour property \''.htmlspecialchars($var).'\'');
    }

    /**
     * Checks to see if $name it is a public method of any of the active
     * behaviours.
     *
     * This method is used by the magic method __call to facilitate behaviours.
     *
     * @param string $name
     * @return boolean
     */
    protected function _isBehaviourMethod($name)
    {
        if (isset($this->mappedBehaviourMethods[$name])) {
            return true;
        }

        if ($this->activeBehaviours) {
            foreach ($this->activeBehaviours as $behaviour => $behaviourClass) {
                // check behaviour class methods
                if (in_array($name, get_class_methods($behaviourClass))) {
                    $this->mappedBehaviourMethods[$name] = $behaviour;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calls a behaviour method
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    protected function _callBehaviourMethod($name, $arguments)
    {
        // make sure the behaviour has been initialised - triggers any behaviour specific loading etc.
        if (!$this->activeBehaviours[$this->mappedBehaviourMethods[$name]]->isLoaded()) {
            $this->activeBehaviours[$this->mappedBehaviourMethods[$name]]->load();
        }

        return $this->activeBehaviours[$this->mappedBehaviourMethods[$name]]->$name($arguments);
    }

    /**
     *
     * @param boolean $dirty
     */
    public function setDirty($dirty)
    {
        $this->dirty = $dirty;
    }

    /**
     * Returns whether or not the object is 'dirty' (has been changed since loaded)
     *
     * @return boolean
     */
    public function isDirty()
    {
        return (bool)$this->dirty;
    }
}
