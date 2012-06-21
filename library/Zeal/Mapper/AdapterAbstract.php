<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_Mapper_AdapterAbstract implements Zeal_Mapper_AdapterInterface
{
	protected $_mapper;

    /**
     * Does the mapper support nested data sets?
     *
     * @var boolean
     *
     */
    protected $_supportsNestedData = false;

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_AdapterInterface#setMapper($mapper)
     */
	public function setMapper(Zeal_MapperInterface $mapper)
	{
		$this->_mapper = $mapper;

		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see Mapper/Zeal_Mapper_AdapterInterface#getMapper()
	 */
	public function getMapper()
	{
		return $this->_mapper;
	}
}