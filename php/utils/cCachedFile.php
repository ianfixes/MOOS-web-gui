<?php

/**
 * super-caching: client-side and server-side caching of a file
 * 
 * client side caching saves bandwidth and CPU
 * server side caching saves CPU
 * this class handles the server-side caching, HTTP headers, and decision chain
 *
 * @author Ian Katz
 */
class cCachedFile
{

    //create new object with default values for everything we care about
    public function __construct()
    {
        $this->checked = false;

        $this->etag        = NULL;
        $this->lastupdate  = NULL;
        $this->expiry      = NULL;
        $this->filename    = NULL;
        $this->hashseed    = NULL;
        $this->cachedir    = NULL;
    }

    // make sure the class is stocked with vars before proceeding
    protected function CheckVars()
    {
        if ($this->checked) return;

        $necessary = array("etag"        => $this->etag,
                           "lastupdate"  => $this->lastupdate,
                           "expiry"      => $this->expiry,
                           "filename"    => $this->filename,
                           "hashseed"    => $this->hashseed,
                           "cachedir"    => $this->cachedir);
        
        foreach ($necessary as $k => $v)
        {
            if (NULL == $v)
            {
                die("cCache error: $k not set");                
            }

        }

        $this->checked = true;
    }
    
    //client tells us if it has this file already when it makes the request
    // --- so act on it!
    //
    //usage: if TryClientCache() then exit 
    public function TryClientCache()
    {
        $this->CheckVars();

        $inheaders = getallheaders();
        if (strpos(@$inheaders['If-None-Match'], $this->etag)
            && (@$inheaders["If-Modified-Since"] == $this->lu_str))
        {
            // avoid bandwidth by telling the browser to use what it has
            header("HTTP/1.1 304 Not Modified");
            header('Cache-Control: private');
            header('Pragma: ');
            header('Expires: ');
            header('Content-Type: ');
            header("ETag: \"{$this->etag}\"");
            return true;
        }
       
        return false;
    }


    //if this file exists in the server cache, load it and spit it with the 
    //   supplied callback.
    //
    //usage: if TryServerCache() then exit.  
    // contentCallback is a function that prints (or otherwise handles) the cached data
    //  CALLBACK MUST RETURN TRUE ON SUCCESS
    public function TryServerCache($contentCallback)
    {
        $this->CheckVars();

        //cache the image itself  
        $cacheFile = $this->CacheFile();
        if (file_exists($cacheFile))
        {
            // avoid CPU by grabbing our local copy
            $contents = file_get_contents($cacheFile);
       
            $this->MakeCacheHeaders(); 
       
            return $contentCallback($contents);
        }

        return false; 
    }

    // if the file isn't cached, here's what we need for the client to cache it
    // --- this should go out with the fully-loaded page
    public function MakeCacheHeaders()
    {
        $this->CheckVars();

        //instruct browser how to cache this
        header('Cache-Control: private');
        header('Pragma: ');
        header('Expires: ' . gmdate('D, d M Y H:i:s', $this->expiry) . ' GMT');
        header("Last-Modified: {$this->lu_str}");
        header("ETag: \"{$this->etag}\"");
    }

    // the path & file on disk representing the cache.  this is where your 
    //  script should save its cached data to be read with TryServerCache...
    public function CacheFile()
    {
        return "{$this->cachedir}/{$this->filename}." . md5($this->hashseed);
    }

    public function setEtag($x)
    {
        $this->etag = $x;
    }

    public function setLastupdate($lastupdate)
    {
        $this->lastupdate = $lastupdate;
        $this->lu_str = gmstrftime("%a, %d %b %Y %T %Z", $lastupdate);
    }

    public function setExpiry($x)
    {
        $this->expiry = $x;
    }

    public function setFilename($x)
    {
        $this->filename = $x;
    }

    public function setHashseed($x)
    {
        $this->hashseed = $x;
    }

    public function setCachedir($x)
    {
        $this->cachedir = $x;
        @mkdir($x, 0777);
    }

}

?>
