<?php

namespace RASTER;

abstract class StaticDB_Constants {

    public static $INT = 1;
    public static $INTEGER = 1;
    public static $NUMBER = 1;
    //
    public static $STRING = 4;
    public static $VARCHAR = 4;
    public static $CHARACTER = 4;
    //
    public static $BOOL = 7;
    public static $BOOLEAN = 7;
    //
    public static $EMAIL = 9;
    public static $MAIL = 9;

    /**
     * 
     * Uses the mysqli_real_escape_string Function to escape the key and the value of an array.
     * After that you should be able to use these values for sql querys.
     * @param \mysqli $db The database is neccesary to escape the array 
     * @param array $array
     * @return array
     */
    public static function real_escape_array(\mysqli $db, $array) {
        $return = array();
        foreach ($array as $key => $value) {
            $k = mysqli_real_escape_string($db, $key);
            $v = mysqli_real_escape_string($db, $value);
            $return[$k] = $v;
        }
        return $return;
    }

}
