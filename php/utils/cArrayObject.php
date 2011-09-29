<?php

/**
 * access an array like an object
 *
 * @author Ian Katz
 * @uses
 */
class cArrayObject
{
    /**
     * the array in question
     *
     * @access private
     * @var array
     */
    private $mArray;

    /**
     * provide the initial array for the class
     *
     * @access public
     * @param $myArray array
     * @return void
     */
    public function __construct($myArray = array())
    {
        $this->mArray = $myArray;
    }

    /**
     * get the index of the array
     *
     * @access public
     * @param $index string the array index
     * @return mixed
     */
    public function __get($index)
    {
        //isset doesn't work when the array value there is null!!1
        //
        //workaround
        if (!in_array($index, array_keys($this->mArray)))
        {
            $x = debug_backtrace();
            trigger_error("Data member '$index' does not exist " .
                "in {$x[0]["file"]}, Line {$x[0]["line"]}", E_USER_ERROR);
            return null;
        }
        return $this->mArray[$index];
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
        $this->mArray[$index] = $val;
    }

    /**
     * whether something is set
     *
     * @access public
     * @param $index string
     * @return bool
     */
    public function __isset($index)
    {
        return isset($this->mArray[$index]);
    }

    /**
     * unset an index
     *
     * @access public
     * @param $index string
     * @return void
     */
    public function __unset($index)
    {
        unset ($this->mArray[$index]);
    }

    /**
     * XXX not tested yet
     *
     * @access public
     * @param $method string
     * @param $arguments string
     * @return array
     */
    public function __call($method, $arguments)
    {
        if ('merge' == $method)
        {
            return array_merge($arguments[0], $arguments[1]);
        }
    }

    /**
     * get the raw data
     *
     * @access public
     * @return array
     */
    public function raw()
    {
        return $this->mArray;
    }

}

?>
