<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Adapter_Zend_Db extends Zeal_Mapper_AdapterAbstract
{
    /**
     * The Zend_Db adapter instance
     *
     * @var Zend_Db_Adapter_Abstract
     */
    static protected $_db;

    /**
     * The database table name
     *
     * @var string
     */
    protected $_tableName;

    /**
     * The primary key field
     *
     * @var string
     */
    protected $_primaryKey;

    /**
     * Sets the Zend_Db adapter
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @return void
     */
    static public function setDb($db)
    {
        self::$_db = $db;
    }

    /**
     * Returns the Zend_Db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        if (!self::$_db) {
            // see if there's one in the registry
            if (Zend_Registry::isRegistered('db')) {
                $db = Zend_Registry::get('db');
                if ($db instanceof Zend_Db_Adapter_Abstract) {
                    self::$_db = $db;
                }
            }

            // we tried!
            if (!self::$_db) {
                throw new Zeal_Mapper_Exception('No database adapter. Please either set one using '.__CLASS__.'::setDb() or put one in the Zend_Registry using the key \'db\'');
            }
        }

        return self::$_db;
    }

    /**
     * Return the human readable object part of the class name
     *
     * @param string $string
     * @return string
     */
    protected function _classNameToObjectName($string)
    {
        if (strpos($string, '_Model_') !== false) {
            // assuming NAMESPACE_Model_Object format
        	$string = substr($string, strpos($string, '_Model_') + 7);
        }

        // switch this to lcfirst() once supporting PHP 5.2.x is not required
        $string{0} = strtolower($string{0});

        return $string;
    }

    /**
     * Returns the table name
     *
     * @return string
     */
    public function getTableName()
    {
        if (!$this->_tableName) {
            if ($this->getMapper()->hasOption('tableName')) {
	        	$tableName = $this->getMapper()->getOption('tableName');

	        } else {
	        	// guess table name based on the name of the class
	        	$tableName = $this->_classNameToObjectName($this->getMapper()->getClassName());

	        	$lastLetter = substr($tableName, -1);
	        	$secondLastLetter = substr($tableName, -2, 1);
	        	$secondLastLetterIsConsonant = !in_array($secondLastLetter, array('a', 'e', 'i', 'o', 'u'));

	        	switch ($lastLetter) {
	        	    case 'y':
	        	        if ($secondLastLetterIsConsonant) {
	        	            $tableName = substr($tableName, 0, -1);
	        	            $tableName .= 'ies';
	        	        } else {
	        	            $tableName .= 's';
	        	        }
	        	        break;

	        	    case 'x':
	        	        $tableName .= 'es';
	        	        break;

	        	    case 'o':
	        	        if ($secondLastLetterIsConsonant) {
	        	            $tableName .= 'es';
	        	        } else {
	        	            $tableName .= 's';
	        	        }
	        	        break;

	        	    case 's':
	        	        if (in_array($secondLastLetter, array('s', 'z', 'h'))) {
	        	            $tableName .= 'es';
	        	        }
	        	        break;

	        	    default:
	        	        $tableName .= 's';
	        	        break;
	        	}
	        }

	        $this->_tableName = $tableName;
        }

        return $this->_tableName;
    }

    /**
     * Populate the table name
     *
     * Normally the table name is populated automatically either based on the mapper
     * option or based on the class name, but this method can be used for custom
     * functionality such as using different tables for different actions (create/update)
     *
     * @param string $tableName
     * @return Zend_Mapper_Adapter_Zend_Db
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;

        return $this;
    }

    /**
     * Returns the primary key
     *
     * @return mixed
     */
    public function getPrimaryKey()
    {
        if (!$this->_primaryKey) {
            if ($this->getMapper()->hasOption('primaryKey')) {
                $this->_primaryKey = $this->getMapper()->getOption('primaryKey');
            } else {
                $this->_primaryKey = $this->_classNameToObjectName($this->getMapper()->getClassName()).'ID';
            }
        }

        return $this->_primaryKey;
    }

    /**
     * Sets the primary key
     *
     * @param $primaryKey
     * @return Zeal_Mapper_Adapter_Zend_Db
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->_primaryKey = $primaryKey;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#query()
     */
    public function query()
    {
        $query = new Zeal_Mapper_Adapter_Zend_Db_Query($this->getDb());

        // add table name
        $query->from($this->getTableName());

        return $query;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#count($query)
     */
    public function count(Zeal_Mapper_QueryInterface $query)
    {
    	$query->columns('COUNT(*) AS count');
		$data = $this->getDb()->fetchRow($query);

		// FIXME - this query is returning lots of unnecessary data

		if (isset($data['count'])) {
		    return (int)$data['count'];
		}

		throw new Zeal_Exception('Unable to determine count');
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#find($id)
     */
    public function find($id)
    {
        $query = $this->getMapper()->query();
        $query->where($this->getTableName().'.'.$this->getPrimaryKey().' = ?', $id);

        return $this->fetchObject($query);
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#fetchOne($query)
     */
    public function fetchObject(Zeal_Mapper_QueryInterface $query = null)
    {
        $query->limit(1);

        try {
        	$object = $this->getDb()->fetchRow($query);
        } catch (Zend_Exception $e) {
        	throw new Zeal_Mapper_Exception('Exception whilst loading object of type \''.$this->getMapper()->getClassName().'\' in adapter: '.$e->getMessage());
        }

        return $object;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#fetchAll($query)
     */
    public function fetchAll(Zeal_Mapper_QueryInterface $query = null)
    {
    	try {
        	$objects = $this->getDb()->fetchAll($query);
    	} catch (Zend_Exception $e) {
    		throw new Zeal_Mapper_Exception('Exception whilst loading objects of type \''.$this->getMapper()->getClassName().'\' in adapter: '.$e->getMessage());
    	}

    	return $objects;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#create($object)
     */
    public function create($object)
    {
        $data = $this->getMapper()->objectToArray($object);

        $this->getDb()->insert($this->getTableName(), $data);

        // populate auto-increment value if appropriate
        if ($this->getMapper()->getOption('autoIncrement', true)) {
            $primaryKey = $this->getPrimaryKey();
            $id = $this->getDb()->lastInsertId();
            if ($primaryKey && $id) {
                $object->$primaryKey = $id;
            }
        }

        return true;
    }

    /**
     * Builds a where clause apporiate for the supplied object
     *
     * @param $object
     * @return string
     */
    protected function _buildWhereClause($object)
    {
        $key = $this->getPrimaryKey();
        if (is_array($key)) {
            $whereBits = array();
            foreach ($key as $field) {
                $whereBits[] = $this->getDb()->quoteInto("$field = ?", $object->$field);
            }
            $where = implode(' AND ', $whereBits);

        } else {
            $where = $this->getDb()->quoteInto("$key = ?", $object->$key);
        }

        return $where;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#update($object)
     */
    public function update($object, $fields = null)
    {
        $data = $this->getMapper()->objectToArray($object, $fields);

        $this->getDb()->update($this->getTableName(), $data, $this->_buildWhereClause($object));

        return true;
    }

    /**
     * Returns true if this record has not yet been committed to the database
     *
     * @param object $object
     * @return boolean
     */
    public function isNewRecord($object)
    {
        return empty($object->{$this->getPrimaryKey()});
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#save($object)
     */
    public function save($object)
    {
        if ($this->isNewRecord($object)) {
            return $this->create($object);
        } else {
            return $this->update($object);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#delete($object)
     */
    public function delete($object)
    {
        $this->getDb()->delete($this->getTableName(), $this->_buildWhereClause($object));

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#populateObjectForAssociation($object, $association)
     */
    public function populateObjectForAssociation($object, Zeal_Model_AssociationInterface $association)
    {
        switch ($association->getType()) {
            case Zeal_Model_AssociationInterface::BELONGS_TO:
                // TODO
                break;

            case Zeal_Model_AssociationInterface::HAS_ONE:
                // TODO
                break;

            case Zeal_Model_AssociationInterface::HAS_MANY:
                // populate the foreign key
                $key = $association->getModelMapper()->getAdapter()->getPrimaryKey();
                $object->$key = $association->getModel()->$key;
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                // TODO
                break;
        }

        return $object;
    }

    public function saveAssociatedForAssociation($object, Zeal_Model_AssociationInterface $association)
    {
        $nestableAssociations = $object->getNestableAssociations();
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
                        } else {
                            // data for an association that can't be saved!
                            throw new Zeal_Mapper_Exception('Association \''.$association->getShortname().'\' contains data that requires saving but allow nested assignment is set to false');
                        }
                    }
                } else {
                    // something has been put in the variable that is not an association data object
                    throw new Zeal_Mapper_Exception('Found something other than an association data object in '.get_class($this).'->'.$association->getShortname());
                }
                break;

            case Zeal_Model_AssociationInterface::HAS_MANY:
                $objectsProcessed = array();
                $associatedObjects = $object->{$association->getShortname()}->getObjects();
                foreach ($associatedObjects as $associatedObject) {
                    if ($associatedObject->isDirty()) {
                        if (in_array($association, $nestableAssociations)) {
                            $association->populateObject($associatedObject);
                            $association->getMapper()->save($associatedObject);
                        } else {
                            // data for an association that can't be saved!
                            throw new Zeal_Mapper_Exception('Association \''.$association->getShortname().'\' contains data that requires saving but allow nested assignment is set to false');
                        }
                    }

                    if ($association->getMapper()->getAdapter()->getPrimaryKey()) {
                        $objectsProcessed[] = $associatedObject->{$association->getMapper()->getAdapter()->getPrimaryKey()};
                    }
                }

                if (count($objectsProcessed) > 0 && $association->getMapper()->getAdapter()->getPrimaryKey()) {
                    // delete any objects that weren't submitted
                    // TODO could use some refactoring
                    $associationKey = $association->getMapper()->getAdapter()->getPrimaryKey();

                    $query = $association->getMapper()->buildAssociationQuery($association);
                    $query->reset(Zend_Db_Select::COLUMNS)
                          ->reset(Zend_Db_Select::ORDER)
                          ->where("$associationKey NOT IN (?)", $objectsProcessed);

                    $association->getMapper()->getAdapter()->getDb()->query("DELETE ".$query);
                }
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                $lookupTable = $this->getLookupTableForHabtm($association);
                $foreignKey = $association->getOption('foreignKey', $this->getMapper()->getAdapter()->getPrimaryKey());
                $associationForeignKey = $association->getOption('associationForeignKey', $association->getMapper()->getAdapter()->getPrimaryKey());

                $associatedObjects = $object->{$association->getShortname()}->getObjects();
                foreach ($associatedObjects as $associatedObject) {
                    $objectKeyValue = $object->{$this->getPrimaryKey()};
                    $associatedObjectKeyValue = $associatedObject->{$association->getMapper()->getAdapter()->getPrimaryKey()};

                    $count = $this->getDb()->fetchOne("
                        SELECT COUNT(*) FROM $lookupTable WHERE $foreignKey = ? AND $associationForeignKey = ?",
                        array($objectKeyValue, $associatedObjectKeyValue)
                    );
                    if ($count == 0) {
                        // create the lookup
                        $this->getDb()->insert($lookupTable, array(
                            $foreignKey => $objectKeyValue,
                            $associationForeignKey => $associatedObjectKeyValue
                        ));
                    }
                }
                break;
        }

        return true;
    }

    public function getLookupTableForHabtm($association)
    {
        if ($association->hasOption('lookupTable')) {
            $lookupTable = $association->getOption('lookupTable');
        } else {
            $tables = array(
                $association->getMapper()->getAdapter()->getTableName(),
                $association->getModelMapper()->getAdapter()->getTableName()
            );
            sort($tables);
            $lookupTable = $tables[0].ucfirst($tables[1]);
        }

        return $lookupTable;
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#populateQueryForAssociation($query, $association)
     */
    public function populateQueryForAssociation(Zeal_Mapper_QueryInterface $query, Zeal_Model_AssociationInterface $association)
    {
        switch ($association->getType()) {
            case Zeal_Model_AssociationInterface::BELONGS_TO:
                $table = $this->getTableName();
                $key = $this->getOption('primaryKey', $this->getPrimaryKey());
                $foreignKey = $association->getOption('foreignKey', $key);
                $value = $association->getModel()->$foreignKey;

		        if (!isset($value)) {
		            //throw new Zeal_Model_Exception("Unable to populate belongsTo query for association '".$association->getShortname()."' in ".$association->getModelMapper()->getClassName()." as the model has no value for the foreign key '$foreignKey'");
		            return false;
		        }

                $query->where("$table.$key = ?", $association->getModel()->$foreignKey);
                break;

            case Zeal_Model_AssociationInterface::HAS_ONE:
                $table = $this->getTableName();
                $key = $association->getModelMapper()->getAdapter()->getPrimaryKey();
                $value = $association->getModel()->$key;

        		if (!isset($value)) {
		            //throw new Zeal_Model_Exception("Unable to populate belongsTo query for association '".$association->getShortname()."' in ".$association->getModelMapper()->getClassName()." as the field '$key' has no value in model");
		            return false;
		        }

                $query = $association->getMapper()->query();
                $query->where("$table.$key = ?", $value);
                break;

            case Zeal_Model_AssociationInterface::HAS_MANY:
                $table = $this->getTableName();
                $key = $association->getOption('primaryKey', $association->getModelMapper()->getAdapter()->getPrimaryKey());
                $foreignKey = $association->getOption('foreignKey', $key);
                $value = $association->getModel()->$key;

                if (!isset($value)) {
		            //throw new Zeal_Model_Exception("Unable to populate belongsTo query for association '".$association->getShortname()."' in ".$association->getModelMapper()->getClassName()." as the field '$key' has no value in model");
		            return false;
		        }

                $query = $association->getMapper()->query();
                $query->where("$table.$foreignKey = ?", $value);
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                $lookupTable = $this->getLookupTableForHabtm($association);

                $tableName = $this->getTableName();
                $foreignKey = $association->getOption('foreignKey', $association->getModelMapper()->getAdapter()->getPrimaryKey());
                $associationForeignKey = $association->getOption('associationForeignKey', $this->getMapper()->getAdapter()->getPrimaryKey());
                $associationKey = $association->getMapper()->getAdapter()->getPrimaryKey();

                $value = $association->getModel()->{$foreignKey};

                if (empty($value)) {
                	return false;
                }

                $query = $association->getMapper()->query();

                $query->joinInner($lookupTable, "$lookupTable.$associationForeignKey = $tableName.$associationKey", '')
                    ->where("$lookupTable.$foreignKey = ?", $value);
                break;
        }

        if ($association->hasOption('where')) {
            $whereBits = $association->getOption('where');
            $query->where($whereBits[0], $whereBits[1]);
        }
        if ($association->hasOption('order')) {
            $query->order($association->getOption('order'));
        }

        return $query;
    }

}
