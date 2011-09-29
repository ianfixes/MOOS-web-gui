<?php

require_once("utils/cArrayObject.php");
require_once("utils/cClean.php");

/**
 * access an array like an object and provide methods for properly escaping
 * 
 * plus each data member returns a sanitized object
 *
 * @author Ian Katz
 * @uses
 */
class cCleanArray extends cArrayObject
{
    /**
     * get the index of the array
     *
     * @access public
     * @param $index string the array index
     * @return mixed
     */
    public function __get($index)
    {
        return new cClean(parent::__get($index));
    }

    /**
     * set an index of the array
     *
     * @access public
     * @param $index string
     * @param $val mixed
     * @return void
     */
    public function __set($index, $val)
    {
        trigger_error("Clean Arrays are read-only!", E_USER_ERROR);
    }

    /**
     * raw ... all the array values as cClean objects
     *
     * @access public
     * @return array
     */
    public function __raw()
    {
        $ret = array();
        foreach (parent::raw() as $k => $v)
        {
            $ret[$k] = new cClean($v);
        }

        return $ret;
    }

}

?>
