<?php

require_once("db/cDb.php");
require_once("utils/cInputValidator.php");
require_once("db/cPassiveRecord.php");
require_once("db/cActiveRecordSet.php");

/**
 * cActiveRecord
 *
 * persistence layer for php, adding the ability to modify recoreds
 *
 * THIS CLASS ASSUMES GOOD NAMING CONVENTIONS IN THE DB
 *  - the primary key must be labeled ID
 *  - there must be a sequence for it named s_tablenamenounderscores_id
 *
 * @author Ian Katz
 * @uses 
 */
class cActiveRecord extends cPassiveRecord
{
    /**
     * constructor
     *
     * @access public
     * @param $db cDb a database object
     * @param $tableName string a table or recordset subquery
     * @param $rowID int the row of this table
     * @return void
     */
    public function __construct($db, $tableName, $rowID = NULL) 
    {
        $this->mTablename = $tableName;
        $this->mDb = $db;
        $this->mRowId = $rowID;
        
        //FIXME: need to throw error if things are missing here
        //also need to check that tablename and rowid 
        //are valid words and numbers respectively
    }

    /**
     * record updating
     *
     * @access private
     * @param $fieldname string the field name
     * @param $val string what to se the field to
     * @return void
     */
    public function __set($fieldname, $val)
    {
        $this->proceedWithCaution($fieldname);
    
        $this->mDb->execute("
           update {$this->mTablename}
           set $fieldname = ?
           where ID = {$this->mRowId}
           ", array($val));
    }

    
    /**
     * unset a column
     *
     * @access private
     * @param $fieldname the fieldname to blank out
     * @return void
     */
    public function __unset($fieldname)
    {
        $this->proceedWithCaution($fieldname);

        $this->mDb->execute("
            update {$this->mTablename}
            set $fieldname = NULL
            where ID = {$this->mRowId}
            ");
    }

    /**
     * get the parent activerecordset object (or a clone therof)
     *
     * @access public
     * @param 
     * @return cActiveRecordSet
     */
    public function getParentSet()
    {
        return new cActiveRecordSet($this->mDb, $this->mTablename);
    }

    /**
     * create a new record
     *
     * you can set a bunch of fields at once which is useful for "not null"s
     * for values like SYSDATE or date functions which should not be quoted
     * list the fieldname in the $literals array
     *
     * @access public
     * @param $row array data for this record as fieldname => value
     * @param $literals array fieldnames that should be interpreted as literals
     * @return void
     */
    public function create($row, $literals)
    {
        if (!is_null($this->mRowId))
        {
            //FIXME: error, another record already active!
        }

        $this->verifyAllInput($row, $literals);

        //get the primary key from the (properly named) sequence
        $sequencename = "s_" . str_replace("_", "", $this->mTablename) . "_id";
        
        $rowID = $this->mDb->getOne("
            select {$sequencename}.nextval from dual
            ");

        //the start of the query with all the field names
        $insert = " 
            insert into {$this->mTablename}
                (id, " . implode(",", array_keys($row)) . ")
             values 
                ($rowID ";

        //declare the values as "?" or "!" appropriately 
        foreach ($row as $fieldname => $dontcare)
        {
            $insert .= ", ";
            
            //"!" is a placeholder for a literal... otherwise "?" to quote it
            $insert .= isset($literals[$fieldname]) ? "!" : "?";
        }

        $insert .= ")";

        //execute and plug in all the values
        $this->mDb->execute($insert, array_values($row));

        //if that worked, mark that we have a new record
        $this->mRowId = $rowID;
    }

    /**
     * update a record
     *
     * you can set a bunch of fields at once which is useful for "not null"s
     *
     * @access public
     * @param $row array data for this record as fieldname => value
     * @param $literals array fieldnames that should be interpreted as literals
     * @return void
     */
    public function modify($row, $literals)
    {
        if (is_null($this->mRowId))
        {
            //FIXME: no record!
        }

        $this->verifyAllInput($row, $literals);

        //build an array of actions
        $whatToDo = array();
        foreach ($row as $field => $newval)
        {
            $action = "$field = ";
            $action .= isset($literals[$field]) ? "!" : "?";
            $action .= "\n             ";
            $whatToDo[] = $action;
        }

        //the start of the query with all the field names
        $insert = " 
            update {$this->mTablename}
            set " . implode(",", $whatToDo) . "
            where ID = {$this->mRowId}";

        //execute and plug in all the values
        $this->mDb->execute($insert, array_values($row));
    }


    /**
     * destroy this record
     *
     * @access public
     * @return void
     */
    public function destroy()
    {
        if (is_null($this->mRowId))
        {
            //FIXME: error, no record!
        }

        $this->mDb->execute("
            delete from {$this->mTablename}
            where ID = {$this->mRowId}
            ");

        $this->mRowId = NULL;
    }

    /**
     * check arrays of input
     *
     * @access protected
     * @param $rowdata array associated keys => values
     * @param $somekeys array whose values are subset of the keys of rowdata
     * @return void
     */
    protected function verifyAllInput($rowdata, $somekeys)
    {
        //check that the field names are valid!
        foreach ($row as $fieldname => $value)
        {
            if (!cInputValidator::isValidWord($fieldname))
            {
                //FIXME: error!
            }
        }

        foreach ($literals as $fieldname)
        {
            if (!isset($row[$fieldname]))
            {
                //FIXME: should i raise an error if they say a field is 
                //literal if they aren't specifying it at all?
            }
            
            //regular error check
            //$this->proceedWithCaution($fieldname);
            //unnecessary... we already checked all the fieldnames, 
            //and just verified that no others exist
        }

    }


}
?>
