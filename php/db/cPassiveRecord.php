<?php

require_once("utils/cInputValidator.php");
require_once("db/cDb.php");
require_once("db/cPassiveRecordSet.php");


/**
 * cPassiveRecord
 *
 * persistence layer for php
 *
 * THIS CLASS ASSUMES GOOD NAMING CONVENTIONS IN THE DB
 *  - the primary key must be labeled ID
 *
 * @author Ian Katz
 * @uses 
 */
class cPassiveRecord 
{
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
     * ikatz: this had a FIXME, (4/20/07)
     * 
     * @access protected
     * @var string
     */
    protected $mTable;

    /**
     * row id of this record (the primary key column value)
     *
     * @access protected
     * @var integer
     */
    protected $mRowId;

    /**
     * database handle
     *
     * @access protected
     * @var cDb
     */
    protected $mDb;

    /**
     * constructor
     *
     * @access public
     * @param $db cDb object
     * @param $tableName string the name of the table containing the record
     * @param $rowID the id of the row in question or null to allow new one
     * @return void
     */
    public function __construct($db, $tableName, $rowID = NULL) 
    {
        $this->mTableFq = $tableName;
        $this->mTable = $tableName;
        $pos = strpos($tableName, '.');
        if (false !== $pos)
        {
            $this->mTable = substr($tableName, $pos + 1);
        }

        $this->mDb = $db;
        $this->mRowId = $rowID;
        
        //FIXME: need to throw error if things are missing here
        //also need to check that tablename and rowid 
        //are valid words and numbers respectively
    }

    /**
     * record retrieval
     *
     * @access private
     * @param $fieldname string the field name to get
     * @return string the value in the field
     */
    public function __get($fieldname)
    {
        return $this->getField($fieldname);
    }

    /**
     * record retrieval
     *
     * @access private
     * @param $fieldname string the field name to get
     * @return string the value in the field
     */
    public function getField($fieldname)
    {
        $this->proceedWithCaution($fieldname);

        if (strtolower($fieldname) == $this->mTable . "_id")
        {
            //no sense running a query for it
            return $this->mRowId;
        }

        $sql = "
            select $fieldname
            from {$this->mTableFq}
            where {$this->mTable}_id = {$this->mRowId}
            ";
        
        return $this->mDb->getOne($sql);
    }

    /**
     * return the parent recordset of this object
     *
     * @access public
     * @return cPassiveRecordSet
     */
    public function getParentSet()
    {
        return new cPassiveRecordSet($this->mDb, $this->mTableFq);
    }

    /**
     * return a set of records from the same table
     *
     * @access public
     * @param $whereArray array of where clause stuff
     * @param $orderArray array of order clause stuff
     * @return void
     */
    public function duplicate($whereArray = array(), $orderArray = array())
    {
        $parent = $this->getParentSet();
        return $parent->Records($whereArray, $orderArray);
    }

    /**
     * whether field is set
     *
     * @access public
     * @param $fieldname string
     * @return bool
     */
    public function fieldIsSet($fieldname)
    {
        $this->proceedWithCaution($fieldname);
    
        if (strtolower($fieldname) == $this->mTable . "_id")
        {
            //no sense running a query for it
            return true;
        }
        
        $myTableID = $this->mTable . "_ID"; 
        
        return 0 < $this->mDb->getOne("
            select count(*) 
            from {$this->mTableFq}
            where $myTableID = {$this->mRowId}
            and $fieldname is not null
            ");
        
    }


    /**
     * whether a column is null
     *
     * @access private
     * @param $fieldname string the name of the field
     * @return bool whether the column is not null
     */
    public function __isset($fieldname)
    {
        return $this->fieldIsSet($fieldname);
    }

    /**
     * validator... make sure things are good
     *
     * @access protected
     * @param $fieldname string the field name to check
     * @return void
     */
    protected function proceedWithCaution($fieldname)
    {
        if (is_null($this->mRowId))
        {
            //FIXME: error!
        }

        if (!cInputValidator::isValidWord($fieldname))
        {
            //FIXME: error!
        }
    }


}

?>
