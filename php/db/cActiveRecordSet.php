<?php

require_once("db/cDb.php");
require_once("db/cActiveRecord.php");
require_once("db/cPassiveRecordSet.php");

/**
 * cActiveRecordSet - part of a persistence layer for php
 *
 * perform queries in an abstract way, act as cRecord factory
 *
 * @author Ian Katz
 * @uses cDb
 */
class cActiveRecordSet extends cPassiveRecordSet
{
    /**
     * create a new record from this type of recordset
     *
     * @access protected
     * @param $id the id of the record
     * @return void
     */
    protected function recordFactory($id)
    {
        return new cActiveRecord($this->mDb, $this->mTablename, $id);
    }

}

?>
