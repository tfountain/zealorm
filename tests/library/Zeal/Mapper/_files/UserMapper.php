<?php

class UserMapper extends Zeal_MapperAbstract
{
    protected $_className = 'User';

    protected $_fields = array(
        'username' => 'string',
        'firstname' => 'string',
        'surname' => 'string',
        'email' => 'string'
    );
}
