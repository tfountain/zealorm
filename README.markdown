Zeal ORM
========

Zeal is an ORM layer for Zend Framework applications. It aims to find some middle ground between the power and testability of the data mapper pattern, and the convenience of Ruby on Rails' ActiveRecord implementation.

It includes:

* Data Mapper implementation
    * Adapters - commit data to storage
        * Supports any database supported by Zend_Db
        * Work-in-progress MongoDB support
        * Write your own adapters for any other type of storage (NoSQL, web services etc.)
    * Field types - e.g. an IP address field might be stored as an integer, but your code will always see the string
    
* Model layer
    * Rails style associations (has many, has one, belongs to, habtm)
    * Doctrine 1 style behaviours (acts as X)
    * Getters/setters


Quickstart:

Initialise the ORM in your bootstrap:

    protected function _initORM()
    {
        $this->bootstrap('db');
        Zeal_Mapper_Adapter_Mysql::setDb($this->getResource('db'));
    }

Model:

    class Yourapp_Model_User extends Zeal_ModelAbstract
    {
        protected $userID;

        protected $firstName;

        protected $surname;
    }


Mapper:

    class Yourapp_Mapper_User extends Zeal_MapperAbstract
    {
        protected $_options = array(
            'tableName' => 'users',
            'primaryKey' => 'userID'
        );

        protected $_fields = array(
            'userID' => 'integer',
            'firstName' => 'string',
            'surname' => 'string'
        );
    }

Then in your application:

    // load an object by primary key
    $mapper = Zeal_Orm::getMapper('Yourapp_Model_User');
    $user = $mapper->find(1);

    // find objects
    $query = $mapper->query();
    $query->where('surname = ?', 'Smith');
    $users = $mapper->fetchAll($query);

    // create/update objects
    $newUser = new Yourapp_Model_User(array(
        'firstName' => 'Joe',
        'surname' => 'Bloggs'
    ));
    $mapper->create($newUser);

    $newUser->surname = 'Smith';
    $mapper->save($newUser);
