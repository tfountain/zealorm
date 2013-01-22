<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Paginator_Adapter implements Zend_Paginator_Adapter_Interface
{
    /**
     * @var Zeal_Mapper_QueryInterface
     */
    protected $_query;

    /**
     * @var Zeal_MapperInterface
     */
    protected $_mapper;

    /**
     * For Countable
     *
     * @var integer
     */
    protected $_rowCount;


    /**
     * Sets the query object
     *
     * @param Zeal_Mapper_QueryInterface $query
     * @return Zeal_Mapper_Paginator_Adapter
     */
    public function setQuery(Zeal_Mapper_QueryInterface $query)
    {
        $this->_query = $query;

        return $this;
    }

    /**
     * Returns the query object
     *
     * @return Zeal_Mapper_QueryInterface
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Sets the mapper
     *
     * @param Zeal_MapperInterface $mapper
     * @return Zeal_Mapper_Paginator_Adapter
     */
    public function setMapper(Zeal_MapperInterface $mapper)
    {
        $this->_mapper = $mapper;

        return $this;
    }

    /**
     * Returns the mapper
     *
     * @return Zeal_MapperInterface
     */
    public function getMapper()
    {
        return $this->_mapper;
    }

    /**
     *
     * @param $offset
     * @param $itemCountPerPage
     * @return unknown_type
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_query->limit($itemCountPerPage, $offset);

        return $this->getMapper()->fetchAll($this->getQuery());
    }

    public function count()
    {
        if ($this->_rowCount === null) {
            $this->_rowCount = $this->_query->count();
        }

        return $this->_rowCount;
    }
}
