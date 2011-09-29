<?php

require_once("db/cDb.php");
require_once("utils/cArrayObject.php");

/**
 *  Main SQL access class
 * @author Ian Katz
 */
class cSql
{
    /**
     * This is a counter
     * @access public
     * @var $counter - keeps a count
     */
    static public $counter = 0;

    /**
     * get column into an array and error check it
     *
     * @access public
     * @param $q string the query 
     * @return array
     */
    public static function getCol($q)
    {
        $db = cDb::singleton();

        //$rs = $db->query($query);
        $ret = $db->getCol($q);
    
        if (MDB2::isError($ret))
        {
            self::doError($ret);
        }
        return $ret;
    }


    /**
     * Get the data from the database
     *
     * @param $query the sql query to execute
     * @param $params array of data to fill in any '?' in the query
     * @param $offset non-negative integer starting row to get (first row is 0)
     * @param $limit non-negative integer number of rows to get
     * @param $totalCount out parameter, the total count of rows in the query
     *   without limits
     * @param $outputFormat string the name of a function 
     * @access public
     * @return array of data
     */
    public static function getStuff($query, $params = array(),
        $offset = null, $limit = null, &$totalCount = null,
        $outputFormat)
    {
        self::$counter++;
        $data = array();
        $db = cDb::singleton();
        if (null !== $limit && null !== $offset)
        {
            $query = preg_replace('/^select/i',
                'SELECT SQL_CALC_FOUND_ROWS', $query);
            $query .= " LIMIT $limit OFFSET $offset";
            $q2 = 'select found_rows()';
        }

        $qp = $db->prepare($query, null, MDB2_PREPARE_RESULT);
        if (MDB2::isError($qp))
        {
            //FORLATER, this is really broken when 
            //  you pass in a statement with "?" in it... no idea why.
            self::doError($qp);
        }
        else 
        {
            //$rs = $db->query($query);
            $rs = $qp->execute($params);
    
            if (MDB2::isError($rs))
            {
                self::doError($rs);
            }
            else
            {
                while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC))
                {
                    $data[] = call_user_func($outputFormat, $row);
                }
                $rs->free();
                if (isset($q2))
                {
                    $rs2 = $db->query($q2);
                    if (!MDB2::isError($rs2))
                    {
                        $row = $rs2->fetchRow();
                        $totalCount = $row[0];
                        $rs2->free();
                    }
                    else
                    {
                        self::doError($rs2);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get the data from the database
     *
     * @param $query the sql query to execute
     * @param $params array of data to fill in any '?' in the query
     * @param $offset non-negative integer starting row to get (first row is 0)
     * @param $limit non-negative integer number of rows to get
     * @param $totalCount out parameter, the total count of rows in the query
     *   without limits
     * @access protected
     * @return array of data
     */
    protected static function getData($query, $params = array(),
        $offset = null, $limit = null, &$totalCount = null)
    {
        return self::getStuff($query, $params, $offset, $limit, $totalCount,
            array("self", "outputArrayObject"));
    }

    /**
     * Get the data from the database
     *
     * @param $query the sql query to execute
     * @param $params array of data to fill in any '?' in the query
     * @param $offset non-negative integer starting row to get (first row is 0)
     * @param $limit non-negative integer number of rows to get
     * @param $totalCount out parameter, the total count of rows in the query
     *   without limits
     * @access public
     * @return array of data
     */
    public static function getRaw($query, $params = array(),
        $offset = null, $limit = null, &$totalCount = null)
    {
        return self::getStuff($query, $params, $offset, $limit, $totalCount,
            array("self", "output2dArray"));
    }



    /**
     * output an array 
     *
     * @access protected
     * @param $row a row of a recordset
     * @return array
     */
    protected static function output2dArray($row)
    {
        return $row;
    }


    /**
     * output an array object from a recordset result row
     *
     * @access protected
     * @param $row a row of a recordset
     * @return arrayobject
     */
    protected static function outputArrayObject($row)
    {
        return new cArrayObject($row);
    }

    /**
     * Trigger the error but format the message first
     *
     * @param $err MDB2_Error object
     * @access private
     * @return null
     */
    private static function doError($err)
    {
        $x = debug_backtrace();
       
        //find the function that called cSql
        for ($i = 0; $i < count($x) && $x[$i]["file"] == __FILE__; $i++)
        {
            //do nothing
        }
        $error_message = $err->getDebugInfo() . 
            " in {$x[$i]["file"]}, Line {$x[$i]["line"]}";
            
        //collapse whitespace to single space
        $error_message = preg_replace('/\s\s+/', ' ', $error_message);
        trigger_error($error_message, E_USER_ERROR);
    }

    /**
     * Make the data array just a two dimentional array (no cArrayObject)
     *
     * @param $data array of cArrayObjects returned by getData()
     * @access public
     * @return two dimentional array of data
     */
    public static function makeRaw($data)
    {
        $res = array();
        foreach ($data as $key => $d)
        {
            if ($d instanceof cArrayObject)
            {
                $res[$key] = $d->raw();
            }
            else
            {
                $res[$key] = $d;
            }

        }
        return $res;
    }
    
    /**
     *  This function will format a date
     *
     *  @param $str - date string format
     *  @return new date format
     */
    protected static function dateFormat($str)
    {
        return "date_format($str, '%c/%e/%Y')";
    }

    /**
     * debug a query
     *
     * show the query, show (print) the result
     *
     * @access protected
     * @static 
     * @param $q a query
     * @return void
     */
    protected static function debug_query($q)
    {
        $row_limit = 5000;
        
        echo "<pre>$q</pre>\n<b>Query Result:</b><br>\n";
        flush();
        
        $data = self::getRaw($q, null, null, 5000);

        //bomb if empty
        if (0 ==  count($data))
        {
            echo "<i>The query returned no rows</i>";
            return;
        }

        echo "<table border='1'>\n";
        echo " <tr>\n";
        
        //print headers
        foreach (array_keys($data[0]) as $h)
        {
            echo "  <th>$h</th>\n";
        }

        echo " </tr>\n";

        //print data
        foreach ($data as $tr)
        {
            echo " <tr>\n";

            foreach ($tr as $td)
            {
                echo "  <td>$td</td>";
            }
            
            echo " </tr>\n";
        }

        echo "</table>";
        flush();
        
        
    }

}
?>
