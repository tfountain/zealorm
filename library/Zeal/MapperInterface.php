<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_MapperInterface
{
    /**
     * Initialise the mapper
     *
     * Called by the constructor. Can be overriden by child classes
     * in order to provide custom functionality
     *
     * @return void
     */
    public function init();

    /**
     * Returns the adapter for this mapper
     *
     * @return Zeal_Mapper_AdapterInterface
     */
    public function getAdapter();

    /**
     * Sets the adapter for this mapper
     *
     * @param $adapter
     * @return Zeal_MapperInterface
     */
    public function setAdapter(Zeal_Mapper_AdapterInterface $adapter);

    /**
     * Returns true if the supplied option has been set in this mapper
     *
     * @param $key
     * @return boolean
     */
    public function hasOption($key);

    /**
     * Sets an option in the mapper
     *
     * @param string $key
     * @param mixed $value
     * @return Zeal_MapperInterface
     */
    public function setOption($key, $value);

    /**
     *
     * @param $key
     * @return unknown_type
     */
    public function getOption($key);

    /**
     * Loads an object by its unique identifier
     *
     * @param mixed $id
     * @return mixed
     */
    public function find($id);

    /**
     * Returns one object matching the supplied query
     *
     * @param Zeal_QueryInterface $query
     * @return object|false
     */
    public function fetchObject($query);

    /**
     * Returns all objects matching the supplied query
     *
     * @param Zeal_QueryInterface $query
     * @return array|false
     */
    public function fetchAll($query = null);

    /**
     * Returns paginated objects matching the supplied query
     *
     * @param Zeal_QueryInterface $query
     * @return array|false
     */
    public function paginate($query = null, $currentPage = 1, $itemsPerPage = 30);

    /**
     * Creates an object, with callbacks
     *
     * @param mixed $object
     * @return boolean
     */
    public function create($object);

    /**
     * Commits any changes to the object, with callbacks
     *
     * @param mixed $object
     * @param null|array $fields
     * @return boolean
     */
    public function update($object, $fields = null);

    /**
     * Creates an object if it is new, updates it otherwise; with callbacks
     *
     * @param mixed $object
     * @return boolean
     */
    public function save($object);

    /**
     * Deletes an object, with callbacks
     *
     * @param mixed $object
     * @return boolean
     */
    public function delete($object);

    /**
     * Create a query object for search-related operations on supporting
     * mappers
     *
     * @return Zeal_Mapper_QueryInterface
     */
    public function query();

    /**
     * Returns the number of objects matching the supplied query
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @return integer
     */
    public function count(Zeal_Mapper_QueryInterface $query);

    public function arrayToObject(array $data, $guard = true);

    public function resultToObject($result, $guard = true);

    public function objectToArray($object, $fields = null);

    /**
     * Lazy load data for the supplied data set.
     *
     * @param Zeal_Model_Association_DataInterface $data
     * @return object|null
     */
    public function lazyLoadObject(Zeal_Model_Association_DataInterface $data);

    /**
     * Lazy load data for the supplied data collection.
     *
     * @param Zeal_Model_Association_Data_CollectionInterface $collection
     * @return array
     */
    public function lazyLoadObjects(Zeal_Model_Association_Data_CollectionInterface $collection);

    /**
     * Creates an association class based on the supplied params
     *
     * @param $invokingClass
     * @param $type
     * @param string $associationShortname
     * @param array $options
     * @return Zeal_Model_AssociationInterface
     */
    public function buildAssociation($type, $options = array());
}
