<?php
require_once dirname(__FILE__) . '/Logins.php';
require_once dirname(__FILE__) . '/Profiles.php';

class Memex_Db_Table_LoginsProfiles extends Zend_Db_Table_Abstract
{
    protected $_name = 'logins_profiles';
    protected $_referenceMap = array(
        'Login' => array(
            'columns'       => 'login_id',
            'refTableClass' => 'Memex_Db_Table_Logins',
            'refColumns'    => 'id'
        ),
        'Account' => array(
            'columns'       => 'profile_id',
            'refTableClass' => 'Memex_Db_Table_Profiles',
            'refColumns'    => 'id'
        )
    );
}
