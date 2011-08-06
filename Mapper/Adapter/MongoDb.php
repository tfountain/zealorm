<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Adapter_MongoDb extends Zeal_Mapper_AdapterAbstract
{
    static protected $_db;
    protected $_collectionName;
    protected $_id;

    static public function setDb(MongoDB $db)
    {
        self::$_db = $db;
    }

    public function getDb()
    {
        return self::$_db;
    }

    public function getCollectionName()
    {
        return $this->_collectionName;
    }

    public function getCollection()
    {
        return $this->getDb()->{$this->getCollectionName()};
    }

    public function getIdField()
    {
        return $this->_id;
    }

    public function find($id)
    {
        // $id must be a MongoId object if not using a custom id field
        if (!$this->getIdField() && !($id instanceof MongoId)) {
            $id = new MongoId($id);
        }

        $data = $this->getCollection()->findOne(array('_id' => $id));
        if ($data) {
            return $this->arrayToObject($data, false);
        }

        return false;
    }

    public function objectToArray($object, $fields = null)
    {
        $data = parent::objectToArray($object, $fields);

        if ($this->getIdField()) {
            if (empty($data['_id']) && !empty($data[$this->getIdField()])) {
                $data['_id'] = $data[$this->getIdField()];
                unset($data[$this->getIdField()]);
            }
        }

        return $data;
    }

    public function arrayToObject(array $data, $guard = true)
    {
        if ($this->getIdField()) {
            if (isset($data['_id']) && empty($data[$this->getIdField()])) {
                // move the _id value to the custom ID field
                $data[$this->getIdField()] = $data['_id'];
                unset($data['_id']);
            }
        }

        return parent::arrayToObject($data, $guard);
    }

    protected function _query()
    {
        if (!$this->_collectionName) {
            throw new Zeal_Mapper_Exception('Unable to create a new MongoDB query object without a collection name set in '.get_class($this));
        }

        $query = new Zeal_Mapper_MongoDb_Query();
        $query->setMapper($this);

        return $query;
    }

    public function fetchOne($query = null)
    {
        if ($query) {
            $cursor = $query->getCursor();
        } else {
            $cursor = $this->getCollection()->find();
        }

        $data = $cursor->getNext();
        if ($data) {
            return $this->arrayToObject($data, false);
        }

        return false;
    }

    public function fetchAll($query = null)
    {
        if ($query) {
            $cursor = $query->getCursor();
        } else {
            $cursor = $this->getCollection()->find();
        }

        $result = array();
        foreach ($cursor as $data) {
            $result[] = $this->arrayToObject($data, false);
        }

        return $result;
    }

    public function create($object)
    {
        $data = $this->objectToArray($object);
        $success = $this->getCollection()->insert($data);
        if ($success && isset($data['_id'])) {
            // populate id
            $object->_id = $data['_id'];
        }

        return $success;
    }

    public function update($object)
    {

    }

    public function save($object)
    {

    }

    public function delete($object)
    {

    }

    /*public function lazyLoadAssociation($dataSet)
    {
        //Zeal_Model_AssociationInterface $association, $object, $data = null
        $query = $dataSet->query();

        if ($data) {
            //return $this->fetchAll()
            //'type' => array('$in' => array('homepage', 'editorial'))

        }

        return $this->fetchAll($query);
    }*/
}
