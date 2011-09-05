<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_ModelAbstract implements Zeal_ModelInterface
{
    /**
     * An array of association objects
     *
     * @var array
     */
    protected $associations = array();

    /**
     * An array of association data objects
     *
     * @var array
     */
    protected $associationData = array();

    static protected $availableBehaviours = array();
    protected $activeBehaviours = array();
    protected $mappedBehaviourProperties = array();
    protected $mappedBehaviourMethods = array();

    protected $unsavedAssociationData = array();

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

            $this->$key = $value;
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
            // use the set method
            return $this->$setMethodName($value);

        } else if ($this->isAssociation($var)) {
            if (!isset($this->associationData[$var])) {
                // create data set
                $associationData = $this->associations[$var]->initAssociationData();

                // populate stuff it might need
                $associationData->setAssociation($this->associations[$var])
                                ->setModel($this);

                $this->associationData[$var] = $associationData;
            }

            if (is_array($value)) {
            	if ($this->associationData[$var] instanceof Zeal_Model_Association_DataInterface) {
					foreach ($value as $key => $data) {
	            		$this->associationData[$var]->$key = $data;
	            	}
            	} else if ($this->associationData[$var] instanceof Zeal_Model_Association_Data_CollectionInterface) {
            		if (is_array($value) && count($value) > 0) {
            			if (is_object($value[0])) {
            				$this->associationData[$var]->setObjects($value);
            			} else if (is_array($value[0])) {
            				$objects = array();
            				foreach ($value as $data) {
            					$objects[] = $this->associationData[$var]->build($data);
            				}
            				$this->associationData[$var]->setObjects($objects);

            			} else {
            				throw new Zeal_Model_Exception('Invalid data in assignment to data collection');
            			}
            		}

            	} else {
            		throw new Zeal_Model_Exception('Invalid model association data type: '.get_class($this->associationData[$var]));
            	}

            } else if ($value instanceof Valuation_Model_Address) {
            	$this->associationData[$var]->setObject($value);

            } else if (!is_null($value)) {
            	throw new Zeal_Model_Exception('Invalid data ('.gettype($value).') passed as value for association \''.$var.'\'');
            }

        } else {
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
     * @return unknown_type
     */
	public function __sleep()
	{
		$sleepFields = array();

		// add the standard fields
		$mapper = Zeal_Orm::getMapper($this);
		$fields = $mapper->getFields();
		foreach ($fields as $field => $fieldType) {
			if (property_exists($this, $field)) {
				$sleepFields[] = $field;
			}
		}

		// add any public properties
		$sleepFields = array_merge($sleepFields, Zeal_Orm::getPublicProperties($this));

		// add any unsaved association data objects
		foreach ($this->associationData as $associationData) {
			if (true) { // TODO find a way to check if the data is saved or not
				if ($associationData instanceof Zeal_Model_Association_DataInterface) {
					$object = $associationData->getObject();
					if ($object) {
						$this->unsavedAssociationData[$associationData->getAssociation()->getShortname()] = $object->toArray(true);
						$sleepFields[] = 'unsavedAssociationData';
					}
				} else if ($associationData instanceof Zeal_Model_Association_Data_CollectionInterface) {
					$objects = $associationData->getObjects();
					if ($objects) {
						$associationShortname = $associationData->getAssociation()->getShortname();
						$this->unsavedAssociationData[$shortname] = array();
						foreach ($objects as $object) {
							$this->unsavedAssociationData[$shortname][] = $object->toArray(true);
						}
						$sleepFields[] = 'unsavedAssociationData';
					}
				} else {
					throw new Zeal_Model_Exception('Invalid association data type');
				}
			}
		}

		$sleepFields = array_unique($sleepFields);

		return $sleepFields;
	}

	/**
	 * PHP magic method run when an object is unserialized
	 *
	 * Restores any unsaved association data that was set aside by __sleep()
	 *
	 * @return void
	 */
	public function __wakeup()
	{
		// restore any associations
		$this->init();

		// move any unsaved association data back into the appropriate objects
		if (count($this->unsavedAssociationData) > 0) {
			foreach ($this->unsavedAssociationData as $associationShortname => $data) {
				if ($this->isAssociation($associationShortname)) {
					$this->$associationShortname->build($data);
				} else {
					// error here? we have data for an association which doesn't exist
				}
			}

			$this->unsavedAssociationData = array();
		}
	}

    /**
     * Returns an array of data held by this model
     *
     * @return array
     */
    public function toArray($includeUnsavedNestedData = false)
    {
    	$mapper = Zeal_Orm::getMapper($this);
		$fields = $mapper->getFields();

		$data = array();
		foreach ($fields as $field => $fieldType) {
			$data[$field] = isset($this->$field) ? $this->$field : null;
		}

		if ($includeUnsavedNestedData) {
			foreach ($this->associationData as $associationData) {
				if (true) { // TODO find a way to check if the data is saved or not
					if ($associationData instanceof Zeal_Model_Association_DataInterface) {
						$object = $associationData->getObject(false);
						if ($object) {
							$data[$associationData->getAssociation()->getShortname()] = $object->toArray(true);
						}
					} else if ($associationData instanceof Zeal_Model_Association_Data_CollectionInterface) {
						$objects = $associationData->getObjects();
						if ($objects) {
							$associationShortname = $associationData->getAssociation()->getShortname();
							$data[$associationShortname] = array();
							foreach ($objects as $object) {
								$data[$associationShortname][] = $object->toArray(true);
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
        foreach ($this->getAssociations() as $association) {
            if ($association->hasOption('allowNestedAssignment') && $association->getOption('allowNestedAssignment') === true) {
                $nestable[] = $association;
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

            $association = $this->associations[$associationShortname];

            // initialise association data class
            $associationData = $association->initAssociationData();

            // populate stuff it might need
            $associationData->setAssociation($association)
                            ->setModel($this);

            // store in the model
            $this->associationData[$associationShortname] = $associationData;
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
    static public function registerBehaviour($behaviourShortname, $class)
    {
        if (isset(self::$availableBehaviours[$behaviourShortname])) {
            throw new Zeal_Model_Exception('A behaviour with the shortname \''.htmlspecialchars($behaviourShortname).'\' already exists');
        }

        self::$availableBehaviours[$behaviourShortname] = $class;
    }

    /**
     * Unregister all behaviours
     *
     * @return void
     */
    static public function unregisterAllBehaviours()
    {
        self::$availableBehaviours = array();
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
        if (!isset(self::$availableBehaviours[$behaviourShortname])) {
            throw new Zeal_Model_Exception('Invalid behaviour: '.htmlspecialchars($behaviourShortname).' defined in class \''.get_class($this).'\'');
        }

        $behaviourClass = self::$availableBehaviours[$behaviourShortname];
        if (!class_exists($behaviourClass)) {
            throw new Zeal_Model_Exception('Invalid behaviour class: '.htmlspecialchars($behaviourClass));
        }

        $behaviour = new $behaviourClass($options);
        $behaviour->setModel($this)
            ->init();

        $this->activeBehaviours[$behaviourShortname] = $behaviour;
    }

    /**
     * Returns true if the model has the specified behaviour
     *
     * @param string $behaviourShortname
     * @return boolean
     */
    public function hasBehaviour($behaviourShortname)
    {
        return isset($this->activeBehaviours[$behaviourShortname]);
    }

    /**
     * Returns true if $var is a property of any of the active behaviours
     *
     * @param string $var
     * @return boolean
     */
    protected function _isBehaviourProperty($var)
    {
        if (isset($this->mappedBehaviourProperties[$var])) {
            return true;
        }

        foreach ($this->activeBehaviours as $behaviourShortname => $behaviourClass) {
            $properties = get_object_vars($behaviourClass);

            if (array_key_exists($var, $properties)) {
                $this->mappedBehaviourProperties[$var] = $behaviourShortname;
                return true;
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

        foreach ($this->activeBehaviours as $behaviour => $behaviourClass) {
            // check behaviour class methods
            if (in_array($name, get_class_methods($behaviourClass))) {
                $this->mappedBehaviourMethods[$name] = $behaviour;
                return true;
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
}
