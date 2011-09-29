<?php

/**
 * class for cleaning a variable
 *
 * borrowed from http://www.phpbuilder.com/columns/sanitize_inc_php.txt
 * 
 * @author Ian Katz
 * @uses 
 */
class cClean
{
    /**
     * the data
     *
     * @access private
     * @var string
     */
    private $mVal;

    /**
     * constructor
     *
     * @access public
     * @param $data string
     * @return void
     */
    public function __construct($data)
    {
        $this->mVal = $data;
    }

    /**
     * provide access to functions
     *
     * @access public
     * @param $type string
     * @return mixed a sanitized string or array of sanitized strings
     */
    public function __get($type)
    {
        $x = $this->mVal;
        $a = is_array($x);
        
        switch (strtolower($type))
        {
            case "string":
                return $a ? implode("\n", $x) : $x;
            case "int":
                return $a ? array_map("intval", $x) : intval($x);
            case "float":
                return $a ? array_map("floatval", $x) : floatval($x);
            case "url":
                return $a ? array_map("htmlentities", $x) : htmlentities($x);
            case "url_arg":
                return $a ? array_map("urlencode", $x) : urlencode($x);

            case "vcard":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_vcard"), $x)
                    : self::sanitize_vcard($x);

            case "post_arg":
                return $a 
                    ? array_map(array(__CLASS__, "myurlencode"), $x) 
                    : self::myurlencode($x);

            case "html":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_html"), $x)
                    : self::sanitize_html($x);
            case "ldap":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_ldap"), $x)
                    : self::sanitize_ldap($x);
            case "paranoid":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_paranoid"), $x)
                    : self::sanitize_paranoid($x);
            case "sql":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_sql"), $x)
                    : self::sanitize_sql($x);
            case "system":
                return $a
                    ? array_map(array(__CLASS__, "sanitize_system"), $x)
                    : self::sanitize_system($x);
            case "utf8":
                return $a 
                    ? array_map(array(__CLASS__, "my_utf8_decode"), $x)
                    : self::my_utf8_decode($x);

            case "balanced_html":
                return $a
                    ? array_map(array(__CLASS__, "balanceTags"), $x)
                    : self::balanceTags($x);

            case "path":
                return $a
                    ? array_map(array(__CLASS__, "fix_path"), $x)
                    : self::fix_path($x);

            default:
                trigger_error("Don't know how to sanitize '$type'", E_USER_ERROR);
        }
    }
  
    /**
     * 
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function fix_path($string)
    {
        return str_replace('../', '', $string);
    }

  
    /**
     * my url encoder
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function myurlencode($string)
    {
        $temp = urlencode($string);
        $temp = str_replace(' ', '_', $temp);
        //$temp = str_replace('.', '_', $temp);
        //$temp = str_replace('-', '_', $temp);
        return $temp;
    }

   
    /**
     * utf decoder
     *
     * because apparently PHP's decoder is weird
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function my_utf8_decode($string)
    {
        return strtr($string, 
          "???????¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝß"
            . "àáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ", 
          "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYs"
            . "aaaaaaaceeeeiiiionoooooouuuuyy");
    }
    
    /**
     * paranoid sanitizer
     *
     * only alphanumerics
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function sanitize_paranoid($string)
    {
        return preg_replace("/[^a-zA-Z0-9]/", "", $string);
    }
   
    /**
     * sanitize a string in prep for passing a single argument to system() 
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function sanitize_system($string)
    {
        // no piping, passing possible environment variables ($),
        // seperate commands, nested execution, file redirection, 
        // background processing, special commands (backspace, etc.), quotes
        // newlines, or some other special characters
        $pattern = '/(;|\||`|>|<|&|^|"|' . "\n|\r|'" . '|{|}|[|]|\)|\()/i'; 
        $string = preg_replace($pattern, '', $string);
        //make sure this is only interpretted as ONE argument
        $string = '"' . preg_replace('/\$/', '\\\$', $string) . '"'; 
        return $string;
    }
   
    /**
     * sanitize a string for vcard fields 
     *
     * @access public
     * @param $text string
     * @return string
     */
    public static function sanitize_vcard($text)
    {
        // escape colons not led by a backslash
        $regex = '(?<!\\\\)(\:)';
        $text = preg_replace("/$regex/i", "\\:", $text); 

        // escape semicolons not led by a backslash
        $regex = '(?<!\\\\)(\;)';
        $text = preg_replace("/$regex/i", "\\;", $text); 

        // escape commas not led by a backslash
        $regex = '(?<!\\\\)(\,)';
        $text = preg_replace("/$regex/i", "\\,", $text); 

        // escape newlines
        $regex = '\\n';
        $text = preg_replace("/$regex/i", "\\n", $text); 


        return $text;
    }
   
   
    /**
     * sanitize a string for SQL input (simple slash out quotes and slashes)
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function sanitize_sql($string)
    {
        $pattern[0] = '/(\\\\)/';
        $pattern[1] = "/\"/";
        $pattern[2] = "/'/";
        $replacement[0] = '\\\\\\';
        $replacement[1] = '\"';
        $replacement[2] = "\\'";
        return preg_replace($pattern, $replacement, $string);
    }
   
    /**
     * sanitize for LDAP (whatever that involves...)
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function sanitize_ldap($string)
    {
        $pattern = '/(\)|\(|\||&)/';
        return preg_replace($pattern, '', $string);
    }
   
    /**
     * sanitize a string for HTML (make sure nothing gets interpretted!)
     *
     * @access public
     * @param $string string
     * @return string
     */
    public static function sanitize_html($string)
    {
        $pattern[0] = '/\&/';
        $pattern[1] = '/</';
        $pattern[2] = "/>/";
        $pattern[3] = '/\n/';
        $pattern[4] = '/"/';
        $pattern[5] = "/'/";
        $pattern[6] = "/%/";
        $pattern[7] = '/\(/';
        $pattern[8] = '/\)/';
        $pattern[9] = '/\+/';
        $pattern[10] = '/-/';
        $replacement[0] = '&amp;';
        $replacement[1] = '&lt;';
        $replacement[2] = '&gt;';
        $replacement[3] = '<br>';
        $replacement[4] = '&quot;';
        $replacement[5] = '&#39;';
        $replacement[6] = '&#37;';
        $replacement[7] = '&#40;';
        $replacement[8] = '&#41;';
        $replacement[9] = '&#43;';
        $replacement[10] = '&#45;';
        return preg_replace($pattern, $replacement, $string);
    }
    
    /**
     balanceTags
    
     Balances Tags of string using a modified stack.  stolen from wordpress
    
     @param $text      Text to be balanced
     @return          Returns balanced text
     @author          Leonard Lin (leonard@acm.org)
     @version         v1.1
     @date            November 4, 2001
     @license         GPL v2.0
     @notes
     @changelog
     ---  Modified by Scott Reilly (coffee2code) 02 Aug 2004
                 1.2  ***TODO*** Make better - change loop condition to $text
                 1.1  Fixed handling of append/stack pop order of end text
                      Added Cleaning Hooks
                 1.0  First Version
    */
    public static function balanceTags($text) 
    {
    
        $tagstack = array(); $stacksize = 0; $tagqueue = ''; $newtext = '';
    
        // WP bug fix for comments - in case you REALLY meant to type '< !--'
        $text = str_replace('< !--', '<    !--', $text);
        // WP bug fix for LOVE <3 (and other situations with '<' before a number)
        $text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);
    
        while (preg_match("/<(\/?\w*)\s*([^>]*)>/", $text, $regex)) 
        {
            $newtext .= $tagqueue;
    
            $i = strpos($text, $regex[0]);
            $l = strlen($regex[0]);
    
            // clear the shifter
            $tagqueue = '';
            // Pop or Push
            if ("/" == $regex[1][0]) 
            { 
                // End Tag
                $tag = strtolower(substr($regex[1], 1));
                // if too many closing tags
                if (0 >= $stacksize) 
                {
                    $tag = '';
                    //or close to be safe $tag = '/' . $tag;
                }
                // if stacktop value = tag close value then pop
                else if ($tagstack[$stacksize - 1] == $tag) 
                {
                    // found closing tag
                    $tag = '</' . $tag . '>';

                    // Pop
                    array_pop($tagstack);
                    $stacksize--;
                } 
                else 
                { 
                    // closing tag not at top, search for it
                    for ($j = $stacksize - 1; 0 <= $j; $j--) 
                    {
                        if ($tagstack[$j] == $tag) 
                        {
                            // add tag to tagqueue
                            for ($k = $stacksize - 1; $k >= $j; $k--)
                            {
                                $tagqueue .= '</' . array_pop($tagstack) . '>';
                                $stacksize--;
                            }
                            break;
                        }
                    }
                    $tag = '';
                }
            } 
            else 
            { 
                // Begin Tag
                $tag = strtolower($regex[1]);
    
                // Tag Cleaning
    
                if ((substr($regex[2], -1) == '/') || ('' == $tag)) 
                {
                    // If self-closing or '', don't do anything.
                }
                // ElseIf it's a known single-entity tag but it 
                //   doesn't close itself, do so
                elseif ('br' == $tag
                    || 'img' == $tag
                    || 'hr' == $tag
                    || 'input' == $tag) 
                {
                    $regex[2] .= '/';
                }
                else 
                {    
                    // Push the tag onto the stack
                    // If the top of the stack is the same as the tag 
                    //     we want to push, close previous tag
                    if (($stacksize > 0) 
                        && ('div' != $tag) 
                        && ($tagstack[$stacksize - 1] == $tag)) 
                    {
                        $tagqueue = '</' . array_pop($tagstack) . '>';
                        $stacksize--;
                    }
                    $stacksize = array_push($tagstack, $tag);
                }
    
                // Attributes
                $attributes = $regex[2];
                if ($attributes) 
                {
                    $attributes = ' ' . $attributes;
                }
                $tag = '<' . $tag . $attributes . '>';
                //If already queuing a close tag, then put this tag on, too
                if ($tagqueue) 
                {
                    $tagqueue .= $tag;
                    $tag = '';
                }
            }
            $newtext .= substr($text, 0, $i) . $tag;
            $text = substr($text, $i + $l);
        }
    
        // Clear Tag Queue
        $newtext .= $tagqueue;
    
        // Add Remaining text
        $newtext .= $text;
    
        // Empty Stack
        while ($x = array_pop($tagstack)) 
        {
            // Add remaining tags to close
            $newtext .= '</' . $x . '>'; 
        }
    
        // WP fix for the bug with HTML comments
        $newtext = str_replace("< !--", "<!--", $newtext);
        $newtext = str_replace("<    !--", "< !--", $newtext);
    
        return $newtext;
    }

    
} //end class

?>
