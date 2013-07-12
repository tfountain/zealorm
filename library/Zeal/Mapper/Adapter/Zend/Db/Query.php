<?php
/**
 * Zeal ORM
 *
 * @category   Zeal
 * @package    Zeal ORM
 * @copyright  Copyright (c) 2010-2013 Tim Fountain (http://tfountain.co.uk/)
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

    public function getCacheKey($mapper)
    {
        // don't cache queries with a GROUP BY, join, or ORDER BY
        if (count($this->_parts['group']) > 0 || count($this->_parts['from']) > 1 || count($this->_parts['order']) > 0) {
            return null;
        }

        // or queries where the columns have been specified
        if (count($this->_parts['columns']) > 1 || $this->_parts['columns'] != '*') {
            return null;
        }

        // or anything with multiple where clauses
        if (count($this->_parts['where']) <> 1) {
            return null;
        }

        $tableName = $mapper->getAdapter()->getTableName();
        $primaryKey = $mapper->getAdapter()->getPrimaryKey();
        if (!$tableName || !$primaryKey || is_array($primaryKey)) {
            return null;
        }

        if (preg_match('/^\('.$tableName.'\.'.$primaryKey.' = ([0-9]+)\)$/', $this->_parts['where'][0], $matches)) {
            return $matches[1];
        }

        return null;
    }
}
