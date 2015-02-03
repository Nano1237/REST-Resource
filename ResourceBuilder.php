<?php

namespace RASTER;

abstract class ResourceBuilder {

    private static $definedResources = array();

    public static function define($name, $data) {
        if (isset(self::$definedResources[$name])) {
            exit('RESOURCE ' . $name . ' already defined!');
        }
        self::$definedResources[$name] = $data;
    }

    public static function get($name, $parent = null) {
        if (!isset(self::$definedResources[$name])) {
            return null;
        }
        return new \RASTER\Resource($name, self::$definedResources[$name], $parent);
    }

}
