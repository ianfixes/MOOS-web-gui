<?php

require_once 'cMitMdb2.php';

/**
 * Database connection object
 * 
 * This class allows access to databases, as a singleton
 * class.
 *
 * @author Ian Katz
 * @uses MDB2
 */
class cDb 
{

    /**
     * Singleton method
     *
     * @param $dsn 
     * @access public
     * @return cMitMdb2 connection object
     */
    public static function singleton($dsn)
    {
        static $instances = array();

        if (array_key_exists($dsn, $instances)) 
        {
            $instance = &$instances[$dsn];
        } 
        else 
        {
            $instances[$dsn] = cMitMdb2::connect($dsn);
            $instance = &$instances[$dsn];
        }

        return $instance;
    }

}
?>
