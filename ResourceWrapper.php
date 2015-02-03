<?php

namespace RASTER;

class ResourceWrapper extends ResourceElementParent {

    public function __construct($parent, $initResources = array()) {
        $this->appendResources($initResources);
        $this->setParent($parent);
    }

}
