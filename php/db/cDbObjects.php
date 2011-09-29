<?php

require_once "db/cPassiveRecordSet.php";
require_once "db/cActiveRecordSet.php";
require_once "db/cPassiveRecord.php";
require_once "db/cActiveRecord.php";
require_once "db/cDb.php";

/**
 * Database connection object
 * 
 * This class allows access to databases, as objects
 *
 * @author Ian Katz
 * @uses MDB2
 */
class cDbObjects
{

    /**
     * database handle
     *
     * @var cDb
     */
    protected $mDb;

    /**
     * mUser
     *
     */
     protected $mUser;

    /**
     * constructor
     *
     * @param $dsn 
     * @access public
     * @return void
     */
    public function __construct($dsn = null)
    {
        $this->mDb = cDb::singleton($dsn);
    }


    /**
     * get an active or passive recordset for a table
     *
     * get an active or passive recordset depending on its type 
     *
     * @access public
     * @param $tablename string
     * @return mixed
     */
    public function __get($tablename)
    {
        return $this->getRecordSet($tablename, true);
    }

    /**
     * getRecordSet
     *
     * get an active or passive recordset depending on the type...
     *
     * @access public
     * @param $table string a table name or parenthesized sql select
     * @param $withUser bool whether to prepend the username
     * @return mixed an active or passive recordset
     */
    public function getRecordSet($table, $withUser = false)
    {
        if ($withUser && $this->mUser)
        {
            //prepend username and dot
            $table = "{$this->mUser}.$table";
        }
    
        if ($this->isWriteableTable($table))
        {
            return new cActiveRecordSet($this, $table);
        }
        else
        {
            return new cPassiveRecordSet($this, $table);
        }
    }

    /**
     * getRecord
     *
     * get a record (by id)
     *
     * @access public
     * @param $table string the table name
     * @param $id int the row id
     * @return mixed an active or passive record
     */
    /*
    public function getRecord($table, $id)
    {
        if ($this->isWriteableTable($table))
        {
            return new cActiveRecord($this, $table, $id);
        }
        else
        {
            return new cPassiveRecord($this, $table, $id);
        }
    }
    */

    /**
     * Set the account used to prefix the table names
     *
     * @access public
     * @param $username string a user account 
     * @return void
     */
    public function setUserAccount($username)
    {
        $this->mUser = $username;
    }


    /**
     * build the SQL query
     *
     * make an sql query out of various pieces
     *
     * @access public
     * @static
     * @param $selectArray array fields to select
     * @param $fromString string a table name or recordset subquery
     * @param $whereArray array a WHERE structure
     * @param $orderArray array an ORDER structure
     * @param $limit int limit on the number of rows
     * @param $start_from row number to start from
     * @return string
     */
    public static function makeSQL($selectArray = array(),
                            $fromString, 
                            $whereArray = array(), 
                            $orderArray = array(),
                            $limit = NULL,
                            $start_from = 0)
    {
        $query  = "select " . implode(",\n    ", $selectArray) . "\n";
        $query .= "from $fromString\n";

        if (0 < count($whereArray))
        {
            $query .= "where\n" . self::parseWhere($whereArray) . "\n";
        }

        if (0 < count($orderArray))
        {
            $query .= "order by " . self::parseOrder($orderArray) . "\n";
        }

        if ($limit)
        {
            $query .= "limit $start_from, $limit\n";
        }
           
        return $query; 
    }

    /**
     * parse "order" structure
     *
     * parse an array in the form fieldname => asc/desc into a string
     *
     * @access public
     * @static
     * @param $orderStatements array an associative array, fieldname => asc
     * @return string
     */
    public static function parseOrder($orderStatements)
    {
        $allstrings = array();
        foreach ($orderStatements as $fieldname => $ord)
        {
            //FIXME: check fieldname for valid word
            
            switch (strtolower($ord))
            {
                case "asc":
                case "desc":
                    break;
                default:
                    $e = "Error parsing ORDER clause: can't 'ORDER BY ";
                    $e .= "$fieldname $ord' because there is no such order as ";
                    $e .= "'$ord'";
                    trigger_error($e, E_USER_ERROR);
            }

            $allstrings[] = "$fieldname $ord";
        }

        return implode(",\n    ", $allstrings);
    }

    
    /**
     * parse "where" structure
     *
     * takes in an associative array (of arrays (of arrays (...)))
     *
     * possible uses:
     * 
     * array('fieldname1' => ' = 3', 'fieldname2' => ' = 1' ...) 
     *   --> fieldnames and their predicates ANDed together
     * 
     * array('fieldname1' => array(' = 3', '= 5')) 
     *   --> arrays of predicates are OR'd together
     *   
     * array('fieldname1' => array(array('> 1', '< 5'), array('> 11', '< 15')))
     *   --> arrays of arrays of predicates are ANDed, then OR'd
     *       in this example, between 1 and 5 OR between 11 and 15
     *
     * array(array('fieldname1' => ...), array('fieldname1' => ...))
     *   --> this is the same behavior as the above example except that
     *       it works on fieldnames instead of predicates
     *       (fieldname1 predicates and fieldname2 predicates) OR
     *        (fieldname1 otherpredicates and fieldname3 predicates)
     * 
     * @access public
     * @param $struct array the where structure
     * @return string
     */
    public function parseWhere($struct)
    {
        return self::parseWhere_h(0, $struct);
    }



    /**
     * parse "where" structure helper
     *
     * recursive, for your entertainment
     *
     * @access protected
     * @static
     * @param $depth int how many times we've recursed
     * @param $node mixed the next element in the where structure
     * @param $fieldname 
     * @return void
     */
    protected static function parseWhere_h($depth, $node, $fieldname = NULL)
    {
        if (is_null($fieldname) && !is_array($node))
        {
            $e = "Error parsing WHERE clause: reached the end of the ";
            $e .= "structure, and didn't find a fieldname";
            trigger_error($e, E_USER_ERROR);
        }
        else if (!is_null($fieldname) && !is_array($node))
        {
            //terminal case, and we DO have a fieldname
            $predicate = $node;
            return str_repeat("    ", $depth) . "($fieldname $predicate)\n";
        }
        else if (!is_null($fieldname) && is_array($node))
        {
            //this is a list of conditions... but it may be a list
            //of ANDs or ORs... we won't know until we process it all
            $flat = true;
            $conditions = array();

            foreach ($node as $subnode)
            {
                if (is_array($subnode))
                {
                    $flat = false;
                }

               $conditions[] = self::parseWhere_h($depth + 1, $subnode, $fieldname);
            }

            $sep = $flat ? "  and" : "   or";
            $sp = str_repeat("    ", $depth);

            return "$sp(\n" . implode("$sp$sep\n", $conditions) . "\n$sp)\n";
        }
        else if (is_null($fieldname) && is_array($node))
        {
            //no conditions yet... we either have a single depth AND array
            //or a double depth OR/AND array
            $flat = true;
            $conditions = array();

            foreach ($node as $key => $subnode)
            {
                if (is_numeric($key))
                {
                    $flat = false;
                    $conditions[] = self::parseWhere_h($depth + 1, $subnode);
                }
                else
                {
                    $conditions[] = self::parseWhere_h($depth + 1, $subnode, $key);
                }
            }

            $sep = $flat ? "  and" : "   or";
            $sp = str_repeat("    ", $depth);

            return "$sp(\n" . implode("$sp$sep\n", $conditions) . "\n$sp)\n";

        }
        else
        {
            return "\n" . str_repeat(" ", $depth) . " -- i got confused\n";
        }
    }

    /**
     * is this table writable?
     *
     * @access public
     * @param $tablename string the name of the table
     * @return bool
     */
    public function isWriteableTable($tablename)
    {
        return false;



        $stuff = MDB2::parseDSN($this->mDSN);
        switch ($stuff["phptype"])
        {
            case "mysql": 
                return $this->isWriteableTable_mysql($tablename);
            case "oci8":
                return $this->isWriteableTable_oracle($tablename);
            default:
                return false;
        }
    }


    /**
     * is this table writable?
     *
     * @access public
     * @param $tablename string the name of the table 
     * @return bool
     */
    public function isWriteableTable_mysql($tablename)
    {
        $t = strtolower($tablename);

        //if it is actually a query, then no...
        if (strpos(trim($t), " "))
        {
            //since we trim the table name, strpos can't be 0
            return false;
        }

        $table_parts = explode(".", $t);

        switch (count($table_parts))
        {
            case 1:
                //single  name
                //if it is in our account, yes:
                return 0 < $this->mDb->getOne(
                        "select count(*) from information_schema.tables
                        where table_schema = ?
                          and table_name = ?",
                        array(strtolower($this->mUser), 
                            strtolower($table_parts[0])));
            case 2: 
                //now we have to look up the permissions 
                $me = strtolower($this->getUsername());
                $owner = strtolower($table_parts[0]);
                $table = strtolower($table_parts[1]);
                
                //if we own it, check our own tables for it
                if ($owner == $me)
                {
                    return 0 < $this->getOne(
                        "select count(*) from information_schema.tables
                        where table_schema = ? 
                          and table_name = ?",
                        array($owner, $table));
                }
                else
                {
                    $sql = "
                    select count(*) 
                    from mysql.tables_priv 
                    where table_name = ?
                      and db = ? 
                      and user = ?
                      and table_priv = 'Select,Insert,Update,Delete'";
                      //fixme... can't figure out how to check these individually!
    
                    return 0 < $this->getOne($sql, 
                                                    array($table, $owner, $me));
                }

            default:
                return false;
        }
        
    }

    /**
     * is this table writable?
     *
     * @access public
     * @param $tablename string the name of the table
     * @return bool
     */
    public function isWriteableTable_oracle($tablename)
    {
        $t = strtoupper($tablename);

        //if it is actually a query, then no...
        if (strpos(trim($t), " "))
        {
            //since we trim the table name, strpos can't be 0
            return false;
        }

        $table_parts = explode(".", $t);

        switch (count($table_parts))
        {
            case 1:
                //single  name
                //if it is in our account, yes:
                return 0 < $this->getOne(
                        "select count(*) from tabs where table_name = ?",
                        $table_parts);
            case 2: 
                //now we have to look up the permissions 
                $me = strtoupper($this->getUsername());
                $owner = strtoupper($table_parts[0]);
                $table = strtoupper($table_parts[1]);
                
                //if we own it, check our own tables for it
                if ($owner == $me)
                {
                    return 0 < $this->getOne(
                        "select count(*) from tabs where table_name = ?",
                        array($table_parts[1]));
                }
                else
                {
                    $sql = "
                    select count(*) 
                    from all_tab_privs 
                    where table_name = ?
                      and grantor = ? 
                      and grantee = ?
                      and privilege in ('SELECT', 'INSERT', 'UPDATE', 'DELETE')";
    
                    return 4 == $this->getOne($sql, 
                                                    array($table, $owner, $me));
                }

            default:
                return false;
        }
        
    }

    /**
     * 
     *
     * @access public
     * @param $query string
     * @return recordset
     */
    public function query($query)
    {
        $rs = $this->mDb->query($query);
        $this->checkError($query, $rs);
        return $rs;

    }

    /**
     * 
     *
     * @access public
     * @param $query
     * @return void
     */
    public function getOne($query)
    {
        $result = $this->mDb->getOne($query);
        $this->checkError($query, $result);
        return $result;
    }

    /**
     * see if there was an error and report it
     *
     * @access public
     * @param $query string
     * @param $result mixed
     * @return void
     */
    public static function checkError($query, $result)
    {
        if (MDB2::isError($result))
        {
            $x = debug_backtrace();

            //find the first non-dbobjects call
            for ($back = 0; 
                $back < count($x) 
                && (strpos($x[$back]["file"], "cPassiveRecord") 
                    || strpos($x[$back]["file"], "cDbObjects")); 
                $back++);

            $location = "in {$x[$back]["file"]}, Line {$x[$back]["line"]}";

            $q2 = str_replace("\n", " ", $query);
            $message = "cDbObjects error: "
                . "{$result->message}.  Query was $q2 -- $location --";
            //echo $message;
            trigger_error($message, E_USER_ERROR);
        }
    }





}
?>
