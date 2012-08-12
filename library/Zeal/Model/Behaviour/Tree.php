<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Model_Behaviour_Tree extends Zeal_Model_BehaviourAbstract
{
    protected $children;

    /**
     * Returns the mapper being used by the tree
     *
     * By default this will use the mapper for the model class itself, but a different
     * mapper can be specified using the 'mapper' option when setting up the tree:
     *
     *     public function init()
     *     {
     *         $this->actsAs('tree', array(
     *             'mapper' => 'Application_Mapper_User
     *         ));
     *     }
     *
     * @return unknown_type
     */
    public function getMapper()
    {
        if ($this->hasOption('mapper')) {
            $mapperClass = $this->getOption('mapper');
            return new $mapperClass();
        } else {
            return Zeal_Orm::getMapper($this->getModel());
        }
    }

    /**
     * Returns the parent object for the model, loading it via. the model's mapper
     *
     * @return object
     */
    public function getParent()
    {
        if (!$this->hasParent()) {
            return false;
        }

        return $this->getMapper()->find($this->getModel()->parentID);
    }

    public function parent()
    {
        // DEPRECATED
        return $this->getParent();
    }

    /**
     * Returns whether or not the model has a parent
     *
     * @return boolean
     */
    public function hasParent()
    {
        return ($this->getModel()->parentID > 0);
    }

    /**
     * Returns an array of child objects for this model
     *
     * @param false|string $order
     * @return array
     */
    public function getChildren($order = false)
    {
        if (!$this->children) {
            $mapper = $this->getMapper();
            $query = $mapper->query()
                ->where('parentID = ?', $this->getModel()->{$mapper->getAdapter()->getPrimaryKey()});

            if ($order) {
                $query->order($order);
            }

            $this->children = $mapper->fetchAll($query);
        }

        return $this->children;
    }

    public function children($order = false)
    {
        return $this->getChildren($order);
    }

}
