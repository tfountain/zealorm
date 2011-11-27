<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_Model_Association_DataInterface
{
    public function load();

    public function clearCached();

    public function getObject($lazyLoad = true);

    public function setObject($object);

    public function populate($data);

    public function build(array $data = array());

    public function create(array $data = array());
}
