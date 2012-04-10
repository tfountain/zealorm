<?php

require_once 'library/Zeal/_files/UserMapper.php';
require_once 'library/Zeal/_files/User.php';

class Zeal_Mapper_FieldType_DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_mapper = new UserMapper();
        $this->_mapper->setFields(array(
            'date' => 'datetime'
        ));
    }

    public function testDateTimeParsesZendDbNow()
    {
        $model = $this->_mapper->arrayToObject(array(
            'date' => new Zend_Db_Expr('NOW()')
        ));

        $this->assertTrue(($model->date instanceof Zeal_DateTime));
        $this->assertEquals(date('j/n/Y'), $model->date->format('j/n/Y'));
    }

    public function testDateTimeParsesUnixTimestamp()
    {
        $model = $this->_mapper->arrayToObject(array(
            'date' => time()
        ));

        $this->assertTrue(($model->date instanceof Zeal_DateTime));
        $this->assertEquals(date('j/n/Y'), $model->date->format('j/n/Y'));
    }
}
