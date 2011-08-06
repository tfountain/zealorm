<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

interface Zeal_Mapper_PluginInterface
{
    public function init($mapper);

    public function query($query, $mapper);

    public function preCreate($object, $mapper);

    public function postCreate($object, $mapper);

    public function preUpdate($object, $mapper);

    public function postUpdate($object, $mapper);

    public function preSave($object, $mapper);

    public function postSave($object, $mapper);
}
