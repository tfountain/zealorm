<?php
// require_once 'library/Zeal/_files/User.php';
// require_once 'library/Zeal/_files/UserMapper.php';

// require_once 'library/Zeal/_files/Address.php';
// require_once 'library/Zeal/_files/AddressMapper.php';

// require_once 'library/Zeal/_files/GenericMapper.php';


class Zeal_Mapper_Adapter_Zend_Db_QueryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // use the Zend Db test adapter
        $db = new Zend_Test_DbAdapter();

        $this->query = new Zeal_Mapper_Adapter_Zend_Db_Query($db);
        $this->query->from('users');
    }

    public function testQuery()
    {
        $this->assertEquals($this->query->__toString(), 'SELECT users.* FROM users');
    }

}