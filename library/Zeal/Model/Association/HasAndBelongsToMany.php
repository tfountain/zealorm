<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Association_HasAndBelongsToMany extends Zeal_Model_AssociationAbstract
{
    /**
     * The association type
     *
     * @var integer
     */
    protected $type = Zeal_Model_AssociationInterface::HAS_AND_BELONGS_TO_MANY;

    /**
     * Initialises the association data collection object
     *
     * @return Zeal_Model_Association_Data_Collection
     */
    public function initAssociationData()
    {
        return new Zeal_Model_Association_Data_Collection();
    }
}
