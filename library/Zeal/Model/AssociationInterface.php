<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_Model_AssociationInterface
{
    const BELONGS_TO = 1;
    const HAS_ONE = 2;
    const HAS_MANY = 3;
    const HAS_AND_BELONGS_TO_MANY = 4;
    const CUSTOM = 5;

    public function initAssociationData();

    public function populateObject($object);

    public function getOption($key, $default = null);

    public function getOptions();
}
