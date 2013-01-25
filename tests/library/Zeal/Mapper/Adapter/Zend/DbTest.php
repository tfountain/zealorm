<?php
require_once 'library/Zeal/_files/User.php';
require_once 'library/Zeal/_files/UserMapper.php';

require_once 'library/Zeal/_files/Address.php';
require_once 'library/Zeal/_files/AddressMapper.php';

require_once 'library/Zeal/_files/GenericMapper.php';


class Zeal_Mapper_Adapter_Zend_DbTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_adapter = new Zeal_Mapper_Adapter_Zend_Db();

        // use the Zend Db test adapter
        $db = new Zend_Test_DbAdapter();
        $this->_adapter->setDb($db);
    }

    public function tearDown()
    {
        $this->_adapter->setTableName(null);
    }

    public function testTableNameCalculatedCorrectly()
    {
        $mapper = new UserMapper();
        $this->_adapter->setMapper($mapper);

        $this->assertEquals('users', $this->_adapter->getTableName());
    }

    public function testTableNameUsesMapperOption()
    {
        $mapper = new UserMapper();
        $mapper->setOption('tableName', 'usersx');
        $this->_adapter->setMapper($mapper);

        $this->assertEquals('usersx', $this->_adapter->getTableName());
    }

    public function testTableNameYPlurals()
    {
        $mapper = new GenericMapper();
        $mapper->setClassName('Category');
        $this->_adapter->setMapper($mapper);

        $this->assertEquals('categories', $this->_adapter->getTableName());
    }

    public function testTableNameSPlurals()
    {
        $mapper = new GenericMapper();
        $mapper->setClassName('News');
        $this->_adapter->setMapper($mapper);

        $this->assertEquals('news', $this->_adapter->getTableName());
    }

    public function testTableNameXPlurals()
    {
        $mapper = new GenericMapper();
        $mapper->setClassName('Box');
        $this->_adapter->setMapper($mapper);

        $this->assertEquals('boxes', $this->_adapter->getTableName());
    }

    public function testBelongsToQuery()
    {
        $address = new Address();
        $address->belongsTo('user', array(
            'className' => 'User'
        ));
        $address->userID = 1;

        $this->assertEquals(
            'SELECT users.* FROM users WHERE (users.userID = 1)',
            $address->user->query()->__toString()
        );
    }

    public function testHasOneQuery()
    {
        $user = new User();
        $user->hasOne('address', array(
            'className' => 'Address'
        ));
        $user->userID = 1;

        $this->assertEquals(
            'SELECT addresses.* FROM addresses WHERE (addresses.userID = 1)',
            $user->address->query()->__toString()
        );
    }

    public function testHasManyQuery()
    {
        $user = new User();
        $user->hasMany('addresses', array(
            'className' => 'Address'
        ));
        $user->userID = 1;

        $this->assertEquals(
        	'SELECT addresses.* FROM addresses WHERE (addresses.userID = 1)',
            $user->addresses->query()->__toString()
        );
    }

    public function testHasManyObjectsAreCorrectlyPopulated()
    {
        $user = new User();
        $user->hasMany('addresses', array(
            'className' => 'Address'
        ));
        $user->userID = 1;

        $newAddress = $user->addresses->build(array());

        //$this->assertInstanceOf('Address', $newAddress);
        $this->assertTrue(($newAddress instanceof Address));
        $this->assertEquals(1, $newAddress->userID);
    }

    public function testHabtmQuery()
    {
        $user = new User();
        $user->hasAndBelongsToMany('addresses', array(
            'className' => 'Address'
        ));
        $user->userID = 1;

        $this->assertEquals(
        	"SELECT addresses.* FROM addresses\n INNER JOIN addressesUsers ON addressesUsers.addressID = addresses.addressID WHERE (addressesUsers.userID = 1)",
            $user->addresses->query()->__toString()
        );
    }

    public function testHasManyQueryWithCompoundForeignKey()
    {
        $user = new User();
        $user->hasMany('addresses', array(
            'className' => 'Address',
            'foreignKey' => array('class', 'classID')
        ));
        $user->userID = 1;

        $this->assertEquals(
            'SELECT addresses.* FROM addresses WHERE (addresses.class = foo AND addresses.classID = 1)',
            $user->addresses->query()->__toString()
        );
    }
}