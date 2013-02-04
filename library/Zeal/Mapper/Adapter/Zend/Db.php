<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Adapter_Zend_Db extends Zeal_Mapper_AdapterAbstract
{
    /**
     * The Zend_Db adapter instance
     *
     * @var Zend_Db_Adapter_Abstract
     */
    static protected $db;

    /**
     * The database table name
     *
     * @var string
     */
    protected $tableName;

    /**
     * The primary key field
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * Sets the Zend_Db adapter
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @return void
     */
    static public function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * Returns the Zend_Db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        if (!self::$db) {
            // see if there's one in the registry
            if (Zend_Registry::isRegistered('db')) {
                $db = Zend_Registry::get('db');
                if ($db instanceof Zend_Db_Adapter_Abstract) {
                    self::$db = $db;
                }
            }

            // we tried, time to error
            if (!self::$db) {
                throw new Zeal_Mapper_Exception('No database adapter. Please either set one using '.__CLASS__.'::setDb() or put one in the Zend_Registry using the key \'db\'');
            }
        }

        return self::$db;
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
        if (!$this->tableName) {
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

            $this->tableName = $tableName;
        }

        return $this->tableName;
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
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Returns the primary key
     *
     * @return mixed
     */
    public function getPrimaryKey()
    {
        if (!$this->primaryKey) {
            if ($this->getMapper()->hasOption('primaryKey')) {
                $this->primaryKey = $this->getMapper()->getOption('primaryKey');
            } else {
                $this->primaryKey = $this->_classNameToObjectName($this->getMapper()->getClassName()).'ID';
            }
        }

        return $this->primaryKey;
    }

    /**
     * Sets the primary key
     *
     * @param $primaryKey
     * @return Zeal_Mapper_Adapter_Zend_Db
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;

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
    public function find($id, $query = null)
    {
        if (!$query) {
            $query = $this->getMapper()->query();
        }
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

        if ($this->getDb()->insert($this->getTableName(), $data)) {
            // if there's an auto-incrementing key, populate it
            $primaryKey = $this->getMapper()->getOption('primaryKey');
            if ($primaryKey && (!$this->getMapper()->hasOption('autoIncrement') || $this->getMapper()->getOption('autoIncrement'))) {
                $id = $this->getDb()->lastInsertId();
                $object->$primaryKey = $id;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Builds a where clause apporiate for the supplied object
     *
     * @param $object
     * @return string
     */
    public function buildWhereClause($object)
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
        if ($data) {
            $this->getDb()->update($this->getTableName(), $data, $this->buildWhereClause($object));
        }

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
        if ($this->getDb()->delete($this->getTableName(), $this->buildWhereClause($object))) {
            return true;
        } else {
            return false;
        }
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
                $primaryKey = $association->getMapper()->getAdapter()->getPrimaryKey();
                $baseQuery = $association->buildQuery();
                $baseQuery->reset(Zend_Db_Select::COLUMNS)
                      ->reset(Zend_Db_Select::ORDER);

                if (!$primaryKey) {
                    // delete all the objects initiallys
                    $association->getMapper()->getAdapter()->getDb()->query("DELETE ".$baseQuery);
                }

                $idsProcessed = array();
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

                    if ($primaryKey) {
                        $idsProcessed[] = $associatedObject->$primaryKey;
                    }
                }

                if ($primaryKey) {
                    // delete any objects that weren't submitted
                    // TODO could use some refactoring
                    $associationKey = $association->getMapper()->getAdapter()->getPrimaryKey();
                    $baseQuery->where("$associationKey NOT IN (?)", $idsProcessed);

                    $association->getMapper()->getAdapter()->getDb()->query("DELETE ".$baseQuery);
                }
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                $lookupTable = $this->getLookupTableForHabtm($association);
                $foreignKey = $association->getOption('foreignKey', $this->getMapper()->getAdapter()->getPrimaryKey());
                $associationForeignKey = $association->getOption('associationForeignKey', $association->getMapper()->getAdapter()->getPrimaryKey());

                $objectKeyValue = $objectKeyValue = $object->{$foreignKey};

                $idsProcessed = array();
                $associatedObjects = $object->{$association->getShortname()}->getObjects();
                foreach ($associatedObjects as $associatedObject) {
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
                    $idsProcessed[] = $associatedObjectKeyValue;
                }

                if (count($idsProcessed) > 0) {
                    // remove lookups for any objects that haven't been updated
                    $this->getDb()->query(
                        "DELETE FROM $lookupTable WHERE $foreignKey = ? AND $associationForeignKey NOT IN (".$this->getDb()->quote($idsProcessed).")",
                        array($objectKeyValue)
                    );
                } else {
                    // remove all the lookups
                    $this->getDb()->query("DELETE FROM $lookupTable WHERE $foreignKey = ?", $objectKeyValue);
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

    public function getModelColumnValue($model, $column)
    {
        if ($column == 'class') {
            return get_class($model);
        } else if ($column == 'classID') {
            $mapper = Zeal_Orm::getMapper($model);
            $primaryKey = $mapper->getAdapter()->getPrimaryKey();
            return $model->$primaryKey;
        } else {
            return $model->$column;
        }
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
                $key = $association->getOption('primaryKey', $this->getPrimaryKey());
                $foreignKey = $association->getOption('foreignKey', $key);
                if (is_array($foreignKey)) {
                    foreach ($foreignKey as $foreignKeyColumn) {
                        $value = $association->getModel()->$foreignKeyColumn;
                        $query->where("$table.$key = ?", $value);
                    }

                    // TODO check for values here?

                } else {
                    $value = $association->getModel()->$foreignKey;
                    if (!isset($value)) {
                        //throw new Zeal_Model_Exception("Unable to populate belongsTo query for association '".$association->getShortname()."' in ".$association->getModelMapper()->getClassName()." as the model has no value for the foreign key '$foreignKey'");
                        return false;
                    }

                    $query->where("$table.$key = ?", $value);
                }
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
                if (is_array($foreignKey)) {
                    $query = $association->getMapper()->query();
                    foreach ($foreignKey as $foreignKeyColumn) {
                        $value = $this->getModelColumnValue($association->getModel(), $foreignKeyColumn);
                        if (!$value) {
                            return false;
                        }

                        $query->where("$table.$foreignKeyColumn = ?", $value);
                    }

                } else {
                    $value = $this->getModelColumnValue($association->getModel(), $foreignKey);
                    $query->where("$table.$foreignKey = ?", $value);
                }
                break;

            case Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY:
                $lookupTable = $this->getLookupTableForHabtm($association);

                $tableName = $this->getTableName();
                $foreignKey = $association->getOption('foreignKey', $association->getModelMapper()->getAdapter()->getPrimaryKey());
                $foreignKeyValueColumn = $association->getOption('foreignKeyValueColumn', $foreignKey);
                $associationForeignKey = $association->getOption('associationForeignKey', $this->getMapper()->getAdapter()->getPrimaryKey());
                $associationKey = $association->getMapper()->getAdapter()->getPrimaryKey();

                $joinClause = $association->getOption('joinClause', "$lookupTable.$associationForeignKey = $tableName.$associationKey");

                if (is_array($foreignKey)) {
                    $query = $association->getMapper()->query();
                    foreach ($foreignKey as $foreignKeyColumn) {
                        $value = $this->getModelColumnValue($association->getModel(), $foreignKeyColumn);
                        if (!$value) {
                            return false;
                        }

                        $query->where("$lookupTable.$foreignKeyColumn = ?", $value);
                    }

                } else {
                    $value = $this->getModelColumnValue($association->getModel(), $foreignKeyValueColumn);

                    if (empty($value)) {
                        return false;
                    }

                    $query = $association->getMapper()->query();
                    $query->where("$lookupTable.$foreignKey = ?", $value);
                }

                $query->joinInner($lookupTable, $joinClause, '');
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
