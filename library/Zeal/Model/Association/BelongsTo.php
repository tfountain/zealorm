<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_BelongsTo extends Zeal_Model_AssociationAbstract
{
    /**
     * The association type
     *
     * @var integer
     */
    protected $type = Zeal_Model_AssociationInterface::BELONGS_TO;

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
