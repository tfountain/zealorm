<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_Mapper_AdapterInterface
{
    /**
     * Sets the mapper
     *
     * @param Zeal_MapperInterface $mapper
     * @return Zeal_Mapper_AdapterInterface
     */
	public function setMapper(Zeal_MapperInterface $mapper);

	/**
	 * Returns the mapper that instantiated this adapter
	 *
	 * @return Zeal_MapperInterface
	 */
	public function getMapper();

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
     * @param Zeal_Mapper_QueryInterface $query
     * @return object|false
     */
    public function fetchObject(Zeal_Mapper_QueryInterface $query = null);

    /**
     * Returns all objects matching the supplied query
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @return array|false
     */
    public function fetchAll(Zeal_Mapper_QueryInterface $query = null);

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
     * @return integer
     */
    public function count(Zeal_Mapper_QueryInterface $query);

    /**
     * Sets the appropriate keys in an object for the supplied association
     *
     * @param object $object
     * @param Zeal_Model_AssociationInterface $association
     * @return object
     */
    public function populateObjectForAssociation($object, Zeal_Model_AssociationInterface $association);

    /**
     * Saves associated objects for a specific association
     * 
     * @param object $object the object which the association belongs to
     * @param Zeal_Model_AssociationInterface $association the association itself
     * @return boolean
     */
    public function saveAssociatedForAssociation($object, Zeal_Model_AssociationInterface $association);

    /**
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @param Zeal_Model_AssociationInterface $association
     * @return Zeal_Mapper_QueryInterface
     */
    public function populateQueryForAssociation(Zeal_Mapper_QueryInterface $query, Zeal_Model_AssociationInterface $association);
}