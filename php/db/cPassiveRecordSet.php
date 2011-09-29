<?php

require_once("db/cDb.php");
require_once("db/cPassiveRecord.php");
require_once("db/cPassiveRecordField.php");


/**
 * cPassiveRecordSet - part of a persistence layer for php
 *
 * perform queries in an abstract way, act as cRecord factory
 *
 * @author Ian Katz
 */
class cPassiveRecordSet
{
    /**
     * database handle
     *
     * @access protected
     */
    protected $mDb;

    /**
     * name of the fully-qualified table in which this record exists
     *
     * @access protected
     * @var string
     */
    protected $mTableFq;
    
    /**
     * table name
     *
     * ikatz: this was marked as FIXME... not sure why (4/20/07)
     *
     * @access protected
     * @var string
     */
    protected $mTable;

    /**
     * constructor
     *
     * @access public
     * @param $db database object
     * @param $tableName string the name of the table containing the record
     * @return void
     */
    public function __construct($db, $tableName) 
    {
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
     * accessor
     *
     * @access public
     * @param $field string the field name
     * @return cPassiveRecordField
     */
    public function __get($field)
    {
        return $this->getField($field);
    }
    

    /**
     * accessor
     *
     * @access public
     * @param $field string the field name
     * @return cPassiveRecordField
     */
    public function getField($field)
    {
        return new cPassiveRecordField($this->mDb, $this->mTableFq, $field);
    }


    /**
     * return all IDs
     *
     * @access public
     * @param $whereArray array WHERE structure (defined in cDb)
     * @param $orderArray array ORDER structure (defined in cDb)
     * @param $limit int a limit on the number of rows
     * @param $start_from int a row to start from
     * @return array all the IDs in this recordset
     */
    public function IDs($whereArray = array(), 
                        $orderArray = array(),
                        $limit = NULL,
                        $start_from = 0)
    {
        $result = array();

        $query = cDbObjects::makeSQL(array($this->mTable . "_id"),
                                    $this->mTableFq,
                                        $whereArray, 
                                        $orderArray, 
                                        $limit, 
                                        $start_from);

        //echo $query;
        $rs = $this->mDb->query($query);
        while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
        {

            //###########
            /*
            $xxx = $row[0];
            echo "<br>";
            echo $xxx;
            */
            //###########
            
            $result[] = $row[0];
        }
        //if (false != $rs)
        //{
        //    $rs->free();
        //}
        return $result;
    }

    /**
     * return a single record based on id
     *
     * @access public
     * @param $id int the id of the record to return
     * @return active record object
     */
    public function ID($id, $order = array())
    {
        if (is_array($id))
        {
            if (0 == count($id))
            {
                return array();
            }
            return $this->ByKeys(
                array("{$this->mTable}_id" => $id),
                array(),
                $order);
        }
        else
        {
            return $this->recordFactory($id);
        }
    }


    /**
     * return activeRecord objects for given criteria
     *
     * @access public
     * @param $whereArray array WHERE structure (defined in cDb)
     * @param $orderArray array ORDER structure (defined in cDb)
     * @param $limit int a limit on the number of rows
     * @param $start_from int a row to start from
     * @return array
     */
    public function Records($whereArray = array(), 
                            $orderArray = array(),
                            $limit = NULL,
                            $start_from = 0)
    {
        $idList = $this->IDs($whereArray, $orderArray, $limit, $start_from);

        $recs = array();
        foreach ($idList as $id)
        {
            $recs[] = $this->recordFactory($id);
        }
        return $recs;
    }

    /**
     * return first activeRecord object for given criteria
     *
     * @access public
     * @param $whereArray array WHERE structure (defined in cDb)
     * @param $orderArray array ORDER structure (defined in cDb)
     * @return activerecord object
     */
    public function FirstRecord($whereArray = array(), 
                            $orderArray = array())
    {
        $recs = $this->Records($whereArray, $orderArray);
        return isset($recs[0]) ? $recs[0] : null;

    }
    
    /**
     * get values based on a named field and its values
     *
     * @access public
     * @param $keys_vals associative array of keyfield names and their values
     * @param $where array the array of acceptable id values
     * @param $orderArray array ORDER structure (defined in cDb)
     * @return array
     */
    public function ByKeys($keys_vals, $where = array(), $order = array())
    {
        $cond = $where;
        foreach ($keys_vals as $key => $values)
        {
            //echo "-";
            //echo $key;
            $vals = is_array($values) ? implode(",", $values) : $values;
            $cond = array_merge($cond, array($key => "in ( $vals )"));
        }
        return $this->Records($cond, $order);
    }

    
    /**
     * record count
     *
     * @access public
     * @param $whereArray array WHERE structure (defined in cDb)
     * @param $limit int a limit on the number of rows
     * @param $start_from int a row to start from
     * @return void
     */
    public function RecordCount($whereArray = array(),
                                $limit = NULL,
                                $start_from = 0)
    {
        $q = "select count(*) from ("
            . cDbObjects::makeSQL(array($this->mTable . "_id"), 
                                    $this->mTableFq,
                                    $whereArray, 
                                    array(), 
                                    $limit, 
                                    $start_from) 
            . ") myQuery";
        //echo $q;
        return $this->mDb->getOne($q);
    }

    /**
     * create a new record from this type of recordset
     *
     * @access protected
     * @param $id the id of the record
     * @return void
     */
    protected function recordFactory($id)
    {
        return new cPassiveRecord($this->mDb, $this->mTableFq, $id);
    }


}

?>
