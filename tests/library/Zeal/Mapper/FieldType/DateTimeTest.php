<?php

require_once 'library/Zeal/Mapper/_files/DummyMapper.php';
require_once 'library/Zeal/Mapper/_files/Dummy.php';

class Zeal_Mapper_FieldType_DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_mapper = new DummyMapper();
        $this->_mapper->setFields(array(
            'date' => 'datetime'
        ));
    }

    public function testDateTimeParsesZendDbNow()
    {
        $model = $this->_mapper->arrayToObject(array(
            'date' => new Zend_Db_Expr('NOW()')
        ));

        $this->assertTrue(($model->date instanceof Zeal_Mapper_FieldType_DateTime));
        $this->assertEquals(date('j/n/Y'), $model->date->format('j/n/Y'));
    }

    public function testDateTimeParsesUnixTimestamp()
    {
        $model = $this->_mapper->arrayToObject(array(
            'date' => time()
        ));

        $this->assertTrue(($model->date instanceof Zeal_Mapper_FieldType_DateTime));
        $this->assertEquals(date('j/n/Y'), $model->date->format('j/n/Y'));
    }
}
