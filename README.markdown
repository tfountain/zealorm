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


## Quickstart

Extract the contents of the `library` directory into the library directory of your application. Alternatively, if you are familiar with git subtree, there is a branch called 'library' which contains the contents of the library/Zeal folder, allowing you to perform a subtree merge into your application using: `git subtree pull --prefix=library/Zeal --squash git@github.com:tfountain/zealorm.git library`.

Register the Zeal namespace with your autoloader, either in application.ini or in a bootstrap method:

    protected function _initAutoloader()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Zeal_');

        return $autoloader;
    }

If you want to use the built in Zend_Db mapper adapter, you'll want to register a Zend_Db instance:

    protected function _initORM()
    {
        $this->bootstrap('db');
        Zeal_Mapper_Adapter_Mysql::setDb($this->getResource('db'));
    }

alternatively, Zeal will look for an entry called 'db' in the Zend_Registry the first time the adapter is required.

## Usage

### Models

Your model classes should live in `application/models` (or `application/modules/modulename/models`) extend Zeal_ModelAbstract. If you want to use the getter/setters (or may want to in the future), define protected variables for each attribute:

    class Yourapp_Model_User extends Zeal_ModelAbstract
    {
        protected $userID;

        protected $firstName;

        protected $surname;

        protected $age;
    }

these attributes can then be accessed as normal, or you can define getter and setter methods for custom functionality:

    class Yourapp_Model_User extends Zeal_ModelAbstract
    {
        [..]

        public function getAge()
        {
            return $this->age.' years old';
        }

        public function setAge($age)
        {
            if (!is_numeric($age)) {
                throw new Zeal_Model_Exception('Age must be numeric');
            }

            $this->age = $age;
        }
    }

### Mappers

Your mapper classes should live in `application/mappers` (or `application/modules/modulename/mappers') and be named the same as the equivalent model class, except with 'Mapper' in the class name instead of 'Model'. Zend Framework's resource autoloader should autoload these classes automatically. 

Mapper classes typically define two arrays, the first contains options specific to the adapter you are using. The included Zend_Db adapter will expect to find two values here, one telling it the name of the database table the data can be found in, and the other containing the name of the primary key field for this table. Secondly, the class should define an array of key/value pairs for the fields, where the key is the field name (matching the protected variable in the model class), and the value is the field type (which tells the adapter how to translate that data to and from storage). Example:

    class Yourapp_Mapper_User extends Zeal_MapperAbstract
    {
        protected $_options = array(
            'tableName' => 'users',
            'primaryKey' => 'userID'
        );

        protected $_fields = array(
            'userID' => 'integer',
            'firstName' => 'string',
            'surname' => 'string',
            'age' => 'integer'
        );
    }

currently supported types are: integer, string, boolean, serialized, date, datetime. The `serialized` type will automatically serialize/unserialize contents as they are stored and retreived. The `date` and `datetime` types will be represented by instances of `Zeal_Date` and `Zeal_DateTime` respectively, which in turn extend the [PHP Datetime class][http://php.net/manual/en/book.datetime.php]. Custom field types can be created by registering them using `Zeal_Orm::registerFieldType()` (docs to come).

Mapper classes can be loaded using `Zeal_Orm::getMapper()`. getMapper() requires one parameter, which is either a string containing the name of the class you want the mapper for, or an instance of that class:

    $mapper = Zeal_Orm::getMapper('Yourapp_Model_User');

    $user = new Yourapp_Model_User();
    $mapper = Zeal_Orm::getMapper($user);

### Retreiving/storing data

Storing data is best shown with some examples:

    // create a new user
    $user = new Yourapp_Model_User();
    $user->firstName = 'Joe';
    $user->surname = 'Bloggs';

    $mapper->create($user);

    echo $user->userID; // outputs 1

    // update the user
    $user->age = 30;

    $mapper->update($user);


    $user->age = 31;
    $mapper->save($user); // save calls create() for a new object, update() for an existing one

Retreiving objects can either be done using the primary key:

    $mapper = Zeal_Orm::getMapper('Yourapp_Model_User');
    $user = $mapper->find(1);

or using a query object, which you pass to either `$mapper->fetchObject()` (for one result) or `$mapper->fetchAll()` (for multiple):

    $query = $mapper->query();
    $query->where('age = ?', 30);

    $users = $user->fetchAll($query);

the query object is specific to the adapter type, so if you are using the Zend_Db adapter the query object supports the same functionality and syntax as Zend_Db_Select:

    $query->where('age > 18')
          ->order('surname ASC');

    $users = $mapper->fetchAll($query);