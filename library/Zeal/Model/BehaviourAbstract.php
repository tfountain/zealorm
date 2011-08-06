<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

abstract class Zeal_Model_BehaviourAbstract implements Zeal_Model_BehaviourInterface
{
    protected $_model;
    protected $_loaded = false;
    protected $_options;

    public function __construct(array $options = null)
    {
        if ($options) {
            $this->_options = $options;
        }
    }

    public function init()
    {

    }

    public function isLoaded()
    {
        return $this->_loaded;
    }

    public function load()
    {
        $this->_loaded = true;
    }

    public function setModel($model)
    {
        $this->_model = $model;

        return $this;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getOption($key, $default = null)
    {
        if (array_key_exists($key, $this->_options)) {
            return $this->_options[$key];
        } else {
            return $default;
        }
    }

    public function hasOption($key)
    {
        return array_key_exists($key, $this->_options);
    }
}
