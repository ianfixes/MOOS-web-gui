<?php

require_once("db/cDbObjects.php");

/**
 * A class for generating some nice form elements
 * 
 * @author Ian Katz
*/

class cFancyForms
{
    //filesystem_path is the directory to iterate.  it better contain JUST IMAGES!
    //web_path is the path to that directory in a webserver sense
    //inputid is a css identifier that you can use
    //inputname is the html form name
    //val is the current (selected) value, if any
    public static function RadioImageSelector($filesystem_path, $web_path, $inputid, $inputname, $val = NULL)
    {
        $images = array();
        foreach (new DirectoryIterator($filesystem_path) as $fileinfo)
        {
            if (!$fileinfo->isDot() && !$fileinfo->isDir())
            {
                $images[] = pathinfo($fileinfo->getFilename());
            }
        }

        $ret = "";

        $ret .= "
          <div class='fancyforms_radioimage' id='$inputid'>
        ";       
 
        foreach ($images as $pi)
        {
            $src = "$web_path{$pi['basename']}";
            $alt = "{$pi['filename']}";
            $sel = ($val == $src) ? "checked='checked'" : "";

            $id = "$inputid$inputname$alt";

            $ret .= "
             <div class='radiobox'>
              <label for='$id'>
               <img src='$src' alt='$alt' />
              </label>
              <div class='rbholder'>
               <input type='radio' id='$id' name='$inputname' value='$src' $sel />
              </div>
             </div>
            ";
        }

        $ret .= "
           <br style='clear:both;' />
          </div>
        ";

        return $ret;
        
    }

    //enumerate a table (Recordset) into a combobox
    //passive_rs is a passive recordset object
    //pkey is the name of the primary key field
    //field is the name of the field we want for the labels
    //inputid is a css identifier that you can use
    //inputname is the html form name
    //val is the current (selected) value, if any
    public static function DatabaseComboBox($passive_rs, $pkey, $field, $inputid, $inputname, $val = NULL)
    {
        $ret = "";

        $ret .= "
          <select id='$inputid' name='$inputname'>";

        foreach ($passive_rs->Records(array(), array($field => "asc")) as $r)
        {
            $i = $r->getField($pkey);
            $v = $r->getField($field);
            
            $sel = $i == $val ? "selected='selected'" : "";
            //$sel = $i == $val ? "selected='selected'" : "><!-- $i vs $val --";
            $ret .= "
           <option value='$i' $sel>$v</option>";
        }
        $ret .= "
          </select>";
 
        return $ret;
    }

}
?>
