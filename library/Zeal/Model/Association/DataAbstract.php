<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_Model_Association_DataAbstract
{
    /**
     * The object created the association
     *
     * @var object
     */
    protected $model;

    /**
     * The mapper
     *
     * @var Zeal_MapperInterface
     */
    protected $mapper;

    /**
     * A query object
     *
     * @var Zeal_Mapper_QueryInterface
     */
    protected $query;

    /**
     * The association
     *
     * @var Zeal_Model_AssociationInterface
     */
    protected $association;

    /**
     * Boolean to indicate whether or not lazy loading should be attempted
     *
     * @var boolean
     */
    protected $loadRequired = true;

    /**
     * Boolean to indicate whether or not lazy loading has been attempted
     *
     * @var boolean
     */
    protected $loaded = false;

    /**
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        if (!$this->mapper) {
            $this->mapper = $this->getAssociation()->getMapper();
        }

        return $this->mapper;
    }

    /**
     * Sets the association
     *
     * @param Zeal_Model_AssociationInterface $association
     * @return Zeal_Model_Association_DataInterface
     */
    public function setAssociation(Zeal_Model_AssociationInterface $association)
    {
        $this->association = $association;

        return $this;
    }

    /**
     * Returns the association
     *
     * @return Zeal_Model_AssociationInterface
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * Sets the model
     *
     * @param object $model
     * @return Zeal_Model_Association_DataInterface
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Returns the model
     *
     * @return object
     */
    public function getModel()
    {
        return $this->model;
    }
}
