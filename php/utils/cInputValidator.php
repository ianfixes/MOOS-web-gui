<?php

/**
 *  this class validates words and numbers 
 *
 * @author Ian Katz
 */
class cInputValidator
{
    /**
     *  this function validates the word passed
     *  
     *  its purpose is so that no1 will screw with 
     *  the database by adding unwanted crap in the 
     *  parameters that will be used in the sql queries
     *
     *  @param $word STING that is the word to be validated 
     *  @return BOOLEAN
     */
    static function isValidWord($word)
    {
        if (NULL == $word)
        {
            return true;
        }
        if (preg_match("/^[a-zA-Z0-9\-\_\.]*$/", $word))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    /**
     *  this does the same thing as the isValidWord
     *  function but for numbers only like id's
     *
     *  @param $number  INT number
     *  @return boolean
     */
    static function isValidNumber($number)
    {
        if (NULL == $number)
        {
            return true;
        }
        else
        {
            return is_numeric($number);
        }
    }
}

?>
