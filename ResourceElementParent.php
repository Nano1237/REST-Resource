<?php

namespace RASTER;

abstract class ResourceElementParent {

    /**
     *
     * Contains the Parent Resource (if it is a wrapper it contains the parent of th wrapper, and so on).
     * That means, it contains the next ancestor Resource
     * @var \RASTER\Resource || null
     */
    protected $parent = null;

    /**
     *
     * Contains an array containing Resources or Wrappers
     * @var array
     */
    protected $children = array();

    /**
     * 
     * Just a getter for the parent property
     * @return \RASTER\Resource || null
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Updates the parent if it is a \RASTER\Resource 
     * @param type $newParent
     * @return \RASTER\ResourceElementParent Returns itself
     */
    public function setParent($newParent) {
        if ($newParent instanceof \RASTER\Resource) {
            $this->parent = $newParent;
        }
        return $this;
    }

    /**
     * Appends a Resource (or a resourceWrapper) to the current element.
     * If a string is given, it trys to get the resource from the \RASTER\ResourceBuilder
     * @param \RASTER\ResourceElementParent|String $resource
     * @return \RASTER\ResourceElementParent Returns itself
     */
    public function appendResource($resource) {
        if ($resource instanceof \RASTER\ResourceElementParent) {
            $resource->setParent($this->parent);
            array_push($this->children, $resource);
        } elseif (is_string($resource)) {
            array_push($this->children, \RASTER\ResourceBuilder::get($resource));
        } else {
            //error
        }
        return $this;
    }

    /**
     * 
     * Appends multiple resources wrapped by an array
     * @param array $resources instances of \RASTER\ResourceElementParent in an array
     * @return \RASTER\ResourceElementParent Returns itself
     */
    public function appendResources($resources = array()) {
        foreach ($resources as $data) {
            $this->appendResource($data);
        }
        return $this;
    }

    /**
     * 
     * Removes a specific resource from this elements children
     * @param string $name The given name (or unique id) of the resource
     * @param string $type the type of the resource (id or name)
     * @return \RASTER\ResourceElementParent Returns itself
     */
    public function removeResource($name, $type = 'name') {
        foreach ($this->children as $index => $data) {
            if ($data->getProperty($type) === $name) {
                unset($this->children[$index]);
            }
        }
        return $this;
    }

    /**
     * 
     * Removes multiple Resources from this elements children.
     * Works like the method $this->removeResource(), because it runs this method with the values in the array.
     * If its an asociative array, then it takes the key as name/id and the value as type.
     * If not it takes the value as name (always name, never id)
     * @param array $resource The Resources (name or name and value) in an array
     * @return \RASTER\ResourceElementParent Returns itself
     */
    public function removeResources($resource = array()) {
        foreach ($resource as $name => $type) {
            if (is_numeric($name)) {
                $this->removeResource('name', $type);
            } else {
                $this->removeResource($type, $name);
            }
        }
        return $this;
    }

    /**
     * 
     * Searches for multiple resources by name, or name and type.
     * If found it returns a new Wrapper with the containing Resources.
     * @param array $resource find the given resources in this element
     * @return \RASTER\ResourceWrapper|null
     */
    public function getResources($resource = array()) {
        $cache = array();
        foreach ($resource as $name => $type) {
            if (is_numeric($name)) {
                $res = $this->getResource('name', $type);
            } else {
                $res = $this->getResource($type, $name);
            }
            array_push($cache, $res);
        }
        return $this->wrap($cache);
    }

    /**
     * 
     * Wraps Resources into a \RASTER\ResourceWrapper
     * @param \RASTER\Resource|\RASTER\ResourceWrapper $resources The resources
     * @return \RASTER\ResourceWrapper|null
     */
    public function wrap($resources) {
        if (count($resources) <= 0) {
            return null;
        }
        if ($this instanceof \RASTER\Resource) {
            $p = $this;
        } elseif ($this instanceof \RASTER\ResourceWrapper) {
            $p = $this->getParent();
        }
        return new \RASTER\ResourceWrapper($p, $resources);
    }

    /**
     * 
     * Returns as specific resource by name or name and type
     * @param string $name The name (or id) of the resource
     * @param string $type name or id
     * @return \RASTER\ResourceWrapper|null
     */
    public function getResource($name, $type = 'name') {
        return $this->findResources($type, $name);
    }

    /**
     * 
     * Searches for a Resource in the elements children and returns a wrapper with the found children
     * @param string $type The type of the property (id or name)
     * @param string $name The name or the id of the element
     * @return \RASTER\ResourceWrapper|null
     */
    private function findResources($type, $name) {
        $cache = array();
        foreach ($this->children as $data) {
            if ($data->getProperty($type) === $name) {
                array_push($cache, $data);
            }
        }
        return $this->wrap($cache);
    }

    /**
     * Returns the Element as JSON string
     * @return string
     */
    public function toJson() {
        return json_encode($this->toArray());
    }

    /**
     * Returns the Element as Array
     * @return array
     */
    public function toArray() {
        $return = array(
            'link' => array(
                'href' => $this->updatetHref ? $this->updatetHref : $this->url,
                'type' => $this->type
            ),
            'data' => $this->data
        );
        foreach ($this->children as $value) {
            array_push($return['data'], $value->toArray());
        }
        return $return;
    }

    /**
     * @todo Shoud sometime return the element as xml stirng (or object?)
     */
    public function toXml() {
        
    }

    /**/
    /**/
    /**/
    /**/

    /**
     * 
     * Runs the query information on all children (or on the element itself if it isnt a wrapper).
     * The elements shoud then contain the resolved data
     * @param array $queryData The data that shoud be run as query
     * @return \RASTER\ResourceElementParent
     */
    public function query($type, $queryData) {
        if ($this instanceof \RASTER\Resource) {
            $this->runQuery($type, $queryData);
        } elseif ($this instanceof \RASTER\ResourceWrapper) {
            foreach ($this->children as $data) {
                $data->query($type, $queryData);
            }
        }
        return $this;
    }

}
