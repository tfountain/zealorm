<?php

require_once 'library/Zeal/_files/User.php';
require_once 'library/Zeal/_files/Address.php';
require_once 'library/Zeal/_files/AddressMapper.php';


class Zeal_Model_Association_DataTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new Zend_Test_DbAdapter();
        Zeal_Mapper_Adapter_Zend_Db::setDb($db);
    }

    public function testBuildOverridesExistingObject()
    {
        $user = new User();
        $user->hasOne('address', array(
            'className' => 'Address',
            'mapper' => new AddressMapper()
        ));

        $oldAddress = new Address(array(
            'shortname' => 'home',
            'address1' => '1 My Street'
        ));
        $user->address->setObject($oldAddress);

        $newData = array(
            'shortname' => 'home',
            'address1' => '2 My Road'
        );
        $user->address->build($newData);

        $this->assertEquals($user->address->getObject()->toArray(), $newData);
    }
}