<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Behaviour_Tree extends Zeal_Model_BehaviourAbstract
{
    public function getMapper()
    {
        if ($this->hasOption('mapper')) {
            $mapperClass = $this->getOption('mapper');
            return new $mapperClass();
        } else {
            return Zeal_Orm::getMapper($this->getModel());
        }
    }

    public function parent()
    {
        return $this->getMapper()->find($this->getModel()->parentID);
    }

    public function hasParent()
    {
        return ($this->getModel()->parentID > 0);
    }

    public function children($order = false)
    {
        $mapper = $this->getMapper();
        $query = $mapper->query()
            ->where('parentID = ?', $this->getModel()->{$mapper->getPrimaryKey()});

        if ($order) {
            $query->order($order);
        }

        return $mapper->fetchAll($query);
    }

}
