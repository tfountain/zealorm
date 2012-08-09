<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2012 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Date extends DateTime implements Zeal_Mapper_FieldTypeInterface
{
    static $_defaultFormat = 'd/m/Y';

    public function __construct($time = null)
    {
        if ($time) {
            if ($time instanceof Zend_Db_Expr) {
                // might be NOW()
                if ($time->__toString() == 'NOW()') {
                    $time = date('Y-m-d H:i:s');
                } else {
                    throw new Zeal_Exception('Invalid date parameter supplied to DateTime');
                }
            } else if (is_numeric($time) && $time > 0) {
                // assume unix timestamp
                $time = '@'.$time;
            }
        } else {
            $time = '@'.time();
        }

        parent::__construct($time);
    }

    /**
     * Converts the datetime into a string using the default format
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format(self::$_defaultFormat);
    }

    /**
     * (non-PHPdoc)
     * @see Mapper/Zeal_Mapper_FieldTypeInterface#getValueForStorage($adapter)
     */
    public function getValueForStorage(Zeal_Mapper_AdapterInterface $adapter)
    {
        return $this->format('Y-m-d');
    }
}
