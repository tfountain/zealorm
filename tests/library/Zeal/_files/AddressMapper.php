<?php

class AddressMapper extends Zeal_MapperAbstract
{
    protected $_className = 'Address';

    protected $_fields = array(
        'shortname' => 'string',
        'address1' => 'string'
    );
}
