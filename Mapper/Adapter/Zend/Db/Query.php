<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2011 Tim Fountain (http://tfountain.co.uk/)
 * @license    New BSD License - http://tfountain.co.uk/license/new-bsd
 */

class Zeal_Mapper_Adapter_Zend_Db_Query extends Zend_Db_Select implements Zeal_Mapper_QueryInterface
{
    public function sort($spec)
    {
        return $this->order($spec);
    }

    public function count()
    {
        $countQuery = clone $this;
        $countQuery->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(new Zend_Db_Expr('COUNT(*) AS Zeal_Count'));

        $data = $countQuery->query(Zend_Db::FETCH_ASSOC)->fetch();
        return $data['Zeal_Count'];
    }
}
