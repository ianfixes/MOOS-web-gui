<?php

require_once 'MDB2.php';

/**
 * Database connection object
 * 
 * This class allows access to databases. See the PEAR
 * documentation for the class MDB2.
 *
 * @author Ian Katz
 * @uses MDB2
 */
class cMitMdb2 extends MDB2
{
    /**
     * Connect to the given dsn
     *
     * @param $dsn 
     * @access public
     * @return MDB2 Object
     */
    function connect($dsn)
    {
        $options = array('debug' => 3,
                 'portability' => MDB2_PORTABILITY_ALL,
                 'field_case' => CASE_LOWER,
                 'persistent' => false);

        $db = parent::connect($dsn, $options);
        if (PEAR::isError($db))
        {
            trigger_error($db->getMessage() . " DSN = $dsn", E_USER_ERROR);
        }
        $db->loadModule('Extended');
        return $db;
    }

    /**
     * Get one column from one row. This method is only provided
     * for convenience. If you want the full functionality of the getOne()
     * you can do this manually.
     *
     * @param $query the sql query to execute
     * @param $column the number of the column (indexing starts with 0)
     * @access public
     * @return the data from the specified column or MDB2_Error on failure
     */
    function getOne($query)
    {
        //$this->loadModule('Extended');
        return $this->extended->getOne($query);
    }

}
?>
