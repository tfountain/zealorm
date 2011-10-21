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
    protected $model;
    protected $loaded = false;

    /**
     * Any options supplied to the tree by the model
     *
     * @var array|null
     */
    protected $options;

    /**
     * Constructor
     *
     * @param null|array $options
     * @return void
     */
    public function __construct(array $options = null)
    {
        if ($options) {
            $this->options = $options;
        }
    }

    /**
     *
     * @return void
     */
    public function init()
    {

    }

    /**
     * Returns whether or not the behaviour has been loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    public function load()
    {
        $this->loaded = true;
    }

    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns the option with the specified key, if set; otherwise returns null.
     *
     * A default value can be provided as the second parameter to be returned instead
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (is_array($this->options) && array_key_exists($key, $this->options)) {
            return $this->options[$key];
        } else {
            return $default;
        }
    }

    /**
     * Returns true if the tree was setup with the specified option
     *
     * @param string $key
     * @return boolean
     */
    public function hasOption($key)
    {
        if (is_array($this->options)) {
            return array_key_exists($key, $this->options);
        }

        return false;
    }
}
