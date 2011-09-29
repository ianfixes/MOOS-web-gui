<?php

/**
 * A class for geodesy stuff, taken from MOOS
 * 
 * @author Ian Katz
*/

class cGeodesy
{
    public static function AxisSemiMajor()
    {
        return 6378137.0;
    }

    public static function AxisSemiMinor()
    {
        return 6356752.3142;
    }

    public static function EarthRadius($latitude)
    {
        $smjr = self::AxisSemiMajor();
        $smnr = self::AxisSemiMinor();
    
        //tan squared of lat
        $tan_lat2 = pow(tan(deg2rad($latitude)), 2);
    
        return ($smnr * sqrt(1.0 + $tan_lat2)) / sqrt(pow($smnr/$smjr, 2) + $tan_lat2);
    }

    /**
     * Utility method for converting from a local grid fix to the 
     * global Lat, Lon pair.  This method will work for small grid
     * approximations - <300km sq
     *
     * @param oLat origin latitude
     * @param oLon originlongitude 
     * @param mEast  The current local grid distance in meters traveled East (X dir) wrt to Origin
     * @param mNorth The current local grid distance in meters traveled North (Y dir) wrt to Origin
     */
    public static function LocalGrid2LatLon($oLat, $oLon, $mEast, $mNorth, $dfRadius = NULL)
    {
        if (NULL == $dfRadius)
        {
            $dfRadius = self::EarthRadius($oLat);
        }   
    
        $lat = rad2deg(asin($mNorth / $dfRadius)) + $oLat;
        $lon = rad2deg(asin($mEast / ($dfRadius * cos(deg2rad($oLat))))) + $oLon;

        return array($lat, $lon);
    }

    /**
     * Utility for finding bearing between 2 points
     *
     */
    public static function BearingBetween($lat1, $lon1, $lat2, $lon2)
    {
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
    
        return (rad2deg(atan2(sin($lon2 - $lon1) * cos($lat2),
           cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1))) +360) % 360;

    }


    //from http://www.movable-type.co.uk/scripts/latlong-vincenty.html
    /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
    /* Vincenty Inverse Solution of Geodesics on the Ellipsoid (c) Chris Veness 2002-2010             */
    /*                                                                                                */
    /* from: Vincenty inverse formula - T Vincenty, "Direct and Inverse Solutions of Geodesics on the */
    /*       Ellipsoid with application of nested equations", Survey Review, vol XXII no 176, 1975    */
    /*       http://www.ngs.noaa.gov/PUBS_LIB/inverse.pdf                                             */
    /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
    
    /**
     * Calculates geodetic distance between two points specified by latitude/longitude using 
     * Vincenty inverse formula for ellipsoids
     *
     * @param   {Number} lat1, lon1: first point in decimal degrees
     * @param   {Number} lat2, lon2: second point in decimal degrees
     * @returns (Number} distance in metres between points
     */
    public static function distVincenty($lat1, $lon1, $lat2, $lon2) 
    {
        $a = self::AxisSemiMajor();
        $b = self::AxisSemiMinor();
        $f = 1/298.257223563;  // WGS-84 ellipsoid params
    
        $L = deg2rad($lon2 - $lon1);
        $U1 = atan((1 - $f) * tan(deg2rad($lat1)));
        $U2 = atan((1 - $f) * tan(deg2rad($lat2)));
        $sinU1 = sin($U1);
        $cosU1 = cos($U1);
        $sinU2 = sin($U2);
        $cosU2 = cos($U2);
      
        $lambda = $L;
        $iterLimit = 100;
        do 
        {
            $sinLambda = sin($lambda);
            $cosLambda = cos($lambda);
            $sinSigma = sqrt(pow($cosU2 * $sinLambda, 2) + 
                pow($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda, 2));
            if (0 == $sinSigma) 
            {
                return 0;  // co-incident points
            }
    
            $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - pow($sinAlpha, 2);
            $cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
            if (is_NaN($cos2SigmaM)) 
            {
                $cos2SigmaM = 0;  // equatorial line: cosSqAlpha=0 (§6)
            }
            $C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
            $lambdaP = $lambda;
            $lambda = $L + (1 - $C) * $f * $sinAlpha *
              ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * 
              (-1 + 2 * pow($cos2SigmaM, 2))));
            $iterLimit--;
        } while (abs($lambda - $lambdaP) > pow(10, -12) && 0 < $iterLimit);
    
        if (0 == $iterLimit) 
        {
            return NAN;  // formula failed to converge
        }
    
        $uSq = $cosSqAlpha * (pow($a, 2) - pow($b, 2)) / pow($b, 2);
        $AA = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $BB = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
        $deltaSigma = $BB * $sinSigma * ($cos2SigmaM + $BB / 4 * ($cosSigma * (-1 + 2 * pow($cos2SigmaM, 2)) -
            $BB / 6 * $cos2SigmaM * (-3 + 4 * pow($sinSigma, 2)) * (-3 + 4 * pow($cos2SigmaM, 2))));
        $s = $b * $AA * ($sigma - $deltaSigma);
      
        return $s;
    }

}

?>
