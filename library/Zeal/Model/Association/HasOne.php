<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_HasOne extends Zeal_Model_AssociationAbstract
{
    /**
     * The association type
     *
     * @var integer
     */
    protected $_type = Zeal_Model_AssociationInterface::HAS_ONE;

    /**
     * Initialises the association data object
     *
     * @return Zeal_Model_Association_Data
     */
    public function initAssociationData()
    {
        return new Zeal_Model_Association_Data();
    }
}
