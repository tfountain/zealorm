<?php

require_once 'library/Zeal/_files/User.php';
require_once 'library/Zeal/_files/UserMapper.php';
require_once 'library/Zeal/_files/Address.php';
require_once 'library/Zeal/_files/AddressMapper.php';

class Zeal_Mapper_Adapter_MongoDbTest extends PHPUnit_Framework_TestCase
{
    protected $_mongoDB;

    public function setUp()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('The MongoDB extension is not available');
        }

        try {
            $mongo = new Mongo();
        } catch (MongoConnectionException $e) {
            $this->markTestSkipped('Unable to connect to MongoDB');
        }

        $this->_mongoDB = $mongo->selectDB('zealtest');

        $this->_seedData();

        Zeal_Mapper_Adapter_MongoDb::setDb($this->_mongoDB);
    }

    public function tearDown()
    {
        $this->_mongoDB->drop();
    }

    protected function _seedData()
    {
        $usersCollection = $this->_mongoDB->users;
        $addressesCollection = $this->_mongoDB->addresses;

        // add addresses
        $firstAddress = array(
            '_id' => '5mystreet',
            'address1' => '5 My Street'
        );
        $secondAddress = array(
            '_id' => '10myavenue',
            'address1' => '10 My Avenue'
        );
        $thirdAddress = array(
            '_id' => '15someotherroad',
            'address1' => '15 Some Other Road'
        );
        $addressesCollection->insert($firstAddress);
        $addressesCollection->insert($secondAddress);
        $addressesCollection->insert($thirdAddress);

        // add users
        $userData = array(
            '_id' => 'joebloggs',
            'firstname' => 'Joe',
            'surname' => 'Bloggs',
            'addresses' => array(
                MongoDbRef::create('addresses', $firstAddress['_id']),
                MongoDbRef::create('addresses', $secondAddress['_id'])
            )
        );
        $usersCollection->insert($userData);

        $userData = array(
            '_id' => 'joanbloggs',
            'firstname' => 'Joan',
            'surname' => 'Bloggs'
        );
        $usersCollection->insert($userData);
    }

    public function testCreate()
    {
        $userData = array(
            'username' => 'johnsmith',
            'firstname' => 'John',
            'surname' => 'Smith'
        );
        $user = new User($userData);
        $mapper = new UserMapper();

        $result = $mapper->create($user);

        $this->assertTrue($result);

        $expected = $mapper->getCollection()->findOne(array('_id' => 'johnsmith'));
        $expected['username'] = $expected['_id'];

        $this->assertEquals($expected, $user->toArray());
    }

    public function testCreatePopulatesId()
    {
        $user = new User();
        $mapper = new UserMapper();

        $user->firstname = 'Joe';

        $result = $mapper->create($user);

        // TODO improve these assertions once using PHPUnit 3.5+
        $this->assertTrue(isset($user->_id));
        $this->assertTrue(($user->_id instanceof MongoId));
    }

    public function testFind()
    {
        $userMapper = new UserMapper();
        $user = $userMapper->find('joanbloggs');

        $expectedData = array(
            'username' => 'joanbloggs',
            'firstname' => 'Joan',
            'surname' => 'Bloggs'
        );

        $actual = $userMapper->find('joanbloggs');
        $this->assertTrue(($actual instanceof User));

        $actualData = $actual->toArray();
        $this->assertEquals($expectedData, $actualData);
    }

    public function testHasMany()
    {
        $userMapper = new UserMapper();
        $user = $userMapper->find('joebloggs');

        $addressMapper = new AddressMapper();
        $desiredResult = array(
            $addressMapper->find('5mystreet'),
            $addressMapper->find('10myavenue')
        );

        $this->assertTrue(($user instanceof User));
        $this->assertEquals(get_class($addressMapper), get_class($user->addresses->getMapper()));
        $this->assertEquals('Address', $user->addresses->getMapper()->getClassName());
        $this->assertEquals($desiredResult, $user->addresses->getObjects());
    }
}
