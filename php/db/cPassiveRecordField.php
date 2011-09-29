<?php

require_once("db/cDb.php");
require_once("db/cDbObjects.php");
require_once("db/cPassiveRecord.php");


/**
 * cPassiveRecordField - part of a persistence layer for php
 *
 * perform queries in an abstract way, act as cRecord factory
 *
 * @author Ian Katz
 */
class cPassiveRecordField
{
    /**
     * database handle
     *
     * @access protected
     */
    protected $mDb;

    /**
     * fully-qualified name of the table in which this record exists
     *
     * @access protected
     * @var string
     */
    protected $mTableFq;
    
    /**
     * name of the table in which this record exists
     *
     * ikatz: this had a FIXME (4/20/07)
     * 
     * @access protected
     * @var string
     */
    protected $mTable;

    /**
     * field name
     *
     * @access protected
     * @var string
     */
    protected $mField;

    /**
     * constructor
     *
     * @access public
     * @param $db cDb object
     * @param $tableName string the name of the table containing the record
     * @param $fieldName string the name of the field w want
     * @return void
     */
    public function __construct($db, $tableName, $fieldName)
    {
        $this->mField = $fieldName;
        $this->mTableFq = $tableName;
        $this->mTable = $tableName;
        $pos = strpos($tableName, '.');
        if (false !== $pos)
        {
            $this->mTable = substr($tableName, $pos + 1);
        }
        $this->mDb = $db;
        
        //FIXME: need to throw error if things are missing here
        //also need to check that tablename is a word
        //.... from then on, we assume its all valid
    }

    
    /**
     * return an array of all values in this field
     *
     * @access public
     * @return array of strings
     */
    public function All()
    {
        return $this->Some();
    }

    /**
     * Fetches the specified column of a query result into an array.
     *
     * @access public
     * @param $where array the where clause parameters
     * @param $order array the order clause parameters
     * @return array associative array: row_id => field_val
     */
    public function Some($where = array(), $order = array())
    {
        $q = cDbObjects::makeSQL(array($this->mField),
                                    $this->mTableFq,
                                    $where,
                                    $order);

        $rs = $this->mDb->query($q);

        while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
        {
            $result[] = $row[0];
        }
       // FIXME
       // $rs->free();

        return isset($result) ? $result : array();
    }

    /**
     * get one value corresponding to an id
     *
     * @access public
     * @param $id int the record id to fetch
     * @return void
     */
    public function Of($id)
    {
        $result = $this->Some(array($this->mTable . "_id" => " = $id"));
        return isset($result[0]) ? $result[0] : "";
    }

    /**
     * get values based on a named field and its values
     *
     * @access public
     * @param $keys_vals associative array of keyfield names and their values
     * @param $where array the array of other criteria
     * @return array
     */
    public function Keyed($keys_vals, $where = array())
    {
        $cond = $where;
        foreach ($keys_vals as $key => $values)
        {
        
            $vals = is_array($values) ? implode(",", $values) : $values;
            $cond = array_merge($cond, array($key => "in ( $vals )"));
        }
        return $this->Some($cond);
    }

    
    /**
     * Fetches the first row in the specified column of a query result
     *
     * @access public
     * @param $where array the where clause parameters
     * @param $order array the order clause parameters
     * @return string
     */
    public function First($where = array(), $order = array())
    {
        $result = $this->Some($where, $order);
        return isset($result[0]) ? $result[0] : "";
    }

    /**
     * Fetches the distinct elements from the field
     *
     * @access public
     * @param $where array the where clause parameters
     * @param $order array the order clause parameters
     * @return string
     */
    public function Distinct($where = array(), $order = array())
    {
        
       //return array_unique($this->Some($where));

    
        $myWhere = "";
        $myOrder = "";

        if (0 < count($where))
        {
            $myWhere = " where\n" . cDbObjects::parseWhere($where) . "\n";
        }

        if (0 < count($order))
        {
            $myOrder = " order by\n" . cDbObjects::parseOrder($order) . "\n";
        }

        $q = "select distinct {$this->mField} from " . $this->mTableFq . 
            $myWhere . $myOrder;

        //echo $q;

        $rs = $this->mDb->query($q);

        while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
        {
            $result[] = $row[0];
        }
//      FIXME
//        $rs->free();
        
        return isset($result) ? $result : array();

    }


    /**
     * get the number of records that match something
     *
     * @access public
     * @param $where array the where clause parameters
     * @return string
     */
    public function Count($where = array())
    {

        $myWhere = "";

        if (0 < count($where))
        {
            $myWhere = " where\n" . cDbObjects::parseWhere($where) . "\n";
        }

        $q = "select {$this->mField} from " . $this->mTableFq . 
            $myWhere;
        
        return $this->mDb->getOne("select count(*) from ($q) as tmp");
    }   
}

?>
