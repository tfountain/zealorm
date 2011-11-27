<?php

require_once 'library/Zeal/_files/UserMapper.php';
require_once 'library/Zeal/_files/User.php';
require_once 'library/Zeal/_files/AddressMapper.php';
require_once 'library/Zeal/_files/Address.php';

class AnotherUser extends Zeal_ModelAbstract
{
	public function init()
	{
		$this->belongsTo('address', array(
			'className' => 'Address',
			'allowNestedAssignment' => true
		));
	}
}

class Zeal_ModelAbstractTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$db = new Zend_Test_DbAdapter();
        Zeal_Mapper_Adapter_Zend_Db::setDb($db);

		$mapperRegistry = Zeal_Orm::getMapperRegistry();
		$mapperRegistry->clear();
		$mapperRegistry->registerMapper(new UserMapper(), 'AnotherUser');
	}

	public function testEmptyBelongsToReturnsNull()
	{
		$user = new User();
		$user->belongsTo('address', array(
			'className' => 'Address'
		));

		$this->assertNull($user->address->getObject());
	}

	public function testAssociationDataCanBeSet()
	{
		$user = new User();
		$user->belongsTo('address', array(
			'className' => 'Address'
		));
		$user->address->address1 = '2 My Road';

		$this->assertEquals('2 My Road', $user->address->address1);
	}

	public function testAssociationDataCanBeMassAssignedIfAllowed()
	{
		$user = new User();
		$user->belongsTo('address', array(
			'className' => 'Address',
			'allowNestedAssignment' => true
		));

		$data = array(
			'firstname' => 'Joe',
			'surname' => 'Bloggs',
			'address' => array(
				'address1' => '3 My Road'
			)
		);

		$user->populate($data);

		$this->assertEquals('Joe', $user->firstname);
		$this->assertEquals('3 My Road', $user->address->address1);
	}

	public function testSerializeIncludesStandardFields()
	{
		$user = new User();
		$user->firstname = 'Joe';
		$user->surname = 'Bloggs';

		$expected = array(
			'username' => null,
			'firstname' => 'Joe',
			'surname' => 'Bloggs',
			'email' => null
		);
		$userAfterSerialize = unserialize(serialize($user));

		$this->assertEquals($expected, $userAfterSerialize->toArray());
	}

	public function testSerializeIncludesPublicProperties()
	{
	    $user = new User();

	    $user->xxx = 'Foo';
	    $userAfterSerialize = unserialize(serialize($user));

	    $this->assertEquals('Foo', $userAfterSerialize->xxx);
	}

	public function testSerializeIncludesUnsavedAssociationData()
	{
	    // Note: the AnotherUser class is used here as it defines its associations
	    // in the init() method, which is automatically run during unserialization
		$user = new AnotherUser();

		$user->address->address1 = '1 My Road';
		$userAfterSerialize = unserialize(serialize($user));

		$this->assertEquals('1 My Road', $userAfterSerialize->address->address1);
	}

	public function testAssociationStoresReferenceToModel()
	{
	    $user = new User();
		$user->belongsTo('address', array(
			'className' => 'Address'
		));

		$user->firstname = 'Joe';

		$this->assertEquals('Joe', $user->getAssociation('address')->getModel()->firstname);
	}

	public function testModelStartsDirty()
	{
	    $user = new User();
	    $user->firstname = 'John';

	    $this->assertTrue($user->isDirty());
	}

	public function testChangingDataMakesModelDirty()
	{
		$user = new User();
		$user->firstname = 'Joe';
		$user->surname = 'Bloggs';

		$user->setDirty(false);

		$this->assertFalse($user->isDirty());

		$user->firstName = 'Bob';

		$this->assertTrue($user->isDirty());
	}

	public function testPopulatedAssociationDataStartsDirty()
	{
	    $user = new User();
	    $user->belongsTo('address', array(
			'className' => 'Address',
			'allowNestedAssignment' => true
	    ));

	    $data = array(
	        'address' => array(
	            'address1' => '1 My Road'
	        )
	    );

	    $user->populate($data);

	    $this->assertTrue($user->isDirty());
	    $this->assertTrue($user->address->getObject()->isDirty());
	}

}