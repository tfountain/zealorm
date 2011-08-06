<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_Model_Association_Data_CollectionInterface extends IteratorAggregate
{
    public function load();

    public function clearCached();

    public function getObjects();

    public function getObjectIDs();

    public function setObjects($objects);

    public function setData($data);

    public function build(array $data = array());

    public function create(array $data = array());
}
