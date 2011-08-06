<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Adapter_MongoDb_Query implements Zeal_Mapper_QueryInterface
{
    protected $_mapper;
    protected $_cursor;
    protected $_conditions = array();

    public function setMapper(Zeal_MapperInterface $mapper)
    {
        $this->_mapper = $mapper;
    }

    public function getMapper()
    {
        return $this->_mapper;
    }

    public function getCursor()
    {
        if (!$this->_cursor) {
            $this->_cursor = $this->getMapper()->getCollection()->find($this->_conditions);
        }

        return $this->_cursor;
    }

    public function where(array $condition)
    {
        if ($this->_cursor) {
            throw new Zeal_Mapper_Exception('Conditions cannot be added after the cursor has been created');
        }

        $this->_conditions = array_merge($this->_conditions, $condition);

        return $this;
    }

    public function limit($num)
    {
        $this->_cursor = $this->getCursor()->limit($num);

        return $this;
    }

    public function sort($fields)
    {
        $this->_cursor = $this->getCursor()->sort($fields);

        return $this;
    }

    public function toArray()
    {
        return $this->_conditions;
    }
}
