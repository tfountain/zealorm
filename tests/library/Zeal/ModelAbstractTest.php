<?php

require_once 'library/Zeal/_files/UserMapper.php';
require_once 'library/Zeal/_files/User.php';

class AnotherUser extends Zeal_ModelAbstract
{
	public function init()
	{
		$this->belongsTo('address', array('className' => 'Address'));
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

		$this->assertEquals($expected, unserialize(serialize($user))->toArray());
	}

	public function testSerializeIncludesUnsavedAssociationData()
	{
		$user = new AnotherUser();

		$user->address->address1 = '1 My Road';

		$this->assertEquals('1 My Road', unserialize(serialize($user))->address->address1);
	}

}