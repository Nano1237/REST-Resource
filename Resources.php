<?php

namespace RASTER;

class Resource extends ResourceElementParent {

    protected $url;
    protected $updatetHref = false;
    protected $type = 'self';
    protected $data = array();
    protected $fields = array();
    protected $primary_keys = array();
    protected $db_table = '';
    protected $otherData;
    protected $db;
    protected $name;
    protected $id;

    public function __construct($name, $resourceData, $parent = null) {
        if (!isset($resourceData['db']) || !($resourceData['db'] instanceof \mysqli)) {
            exit('DB ERROR');
        }
        $this->db = $resourceData['db'];
        if (!isset($resourceData['href'])) {
            exit('NO HREF SET!');
        }
        if ($parent !== null) {
            $this->parent = $parent;
        }
        $this->name = $name;
        $this->url = $resourceData['href'];
        $this->resolveHref();
        $this->db_table = $resourceData['table'];
        $this->fields = $resourceData['fields'];
        unset($resourceData['href']);
        $this->otherData = $resourceData;
    }

    private function resolveHref() {
        foreach (explode('/', $this->url) as $value) {
            if (substr($value, 0, 1) === ':') {
                array_push($this->primary_keys, trim($value, ':'));
            }
        }
    }

    public function getProperty($type) {
        if ($type === 'name') {
            return $this->getName();
        }
    }

    public function getName() {
        return $this->name;
    }

    protected function realEscape($data) {
        return mysqli_real_escape_string($this->db, $data);
    }

    protected function maskData($type, $data) {
        if ($type === StaticDB_Constants::$INT) {
            return $this->realEscape($data);
        }
        return '"' . $this->realEscape($data) . '"';
    }

    protected function buildCompareQuery($data) {
        return implode(' AND ', $this->dataToArray($data));
    }

    protected function dataToArray($data) {
        $query_match = array();
        foreach ($data as $index => $value) {
            if (isset($this->fields[$index]) && isset($this->fields[$index]['name'])) {
                $type = isset($this->fields[$index]['type']) ? $this->fields[$index]['type'] : StaticDB_Constants::$STRING;
                array_push($query_match, '`' . $this->fields[$index]['name'] . '`=' . $this->maskData($type, $value));
            }
        }
        return $query_match;
    }

    protected function querySelect($data) {
        $query = 'SELECT * ';

        $query .= 'FROM `' . $this->db_table . '` ';
        $query .= 'WHERE ' . $this->buildCompareQuery($data);
        return $this->db->query($query);
    }

    protected function queryInsert($data) {
        $query = 'INSERT INTO `' . $this->db_table . '` ';
        $query .= 'SET ' . implode(',', $this->dataToArray($data));
//        echo $query;
        $this->db->query($query);
        $data['id'] = $this->db->insert_id; //@todo push $this->db->insert_id in primaryfield

        return $this->querySelect($data);
    }

    protected function queryUpdate($data) {
        $query = 'UPDATE `' . $this->db_table . '` ';
        $data_cahce = $data;
        $primaray_data = array();
        foreach ($data as $field => $value) {
            if (in_array($field, $this->primary_keys)) {
                $primaray_data[$field] = $value;
                unset($data[$field]);
            }
        }
        if (count($primaray_data) !== count($this->primary_keys)) {
            return '';
            //FEHLER!
        }
        $query .= 'SET ' . $this->buildCompareQuery($data) . ' ';
        $query .= 'WHERE ' . $this->buildCompareQuery($primaray_data); //@todo update bestimmen
        return $this->querySelect($data_cahce);
//        exit();
    }

    protected function queryDelete($data) {
        $query = 'DELETE FROM `' . $this->db_table . '` ';
        $query .= 'WHERE ' . $this->buildCompareQuery($data); //@todo update bestimmen
        echo $query;
        return $this->querySelect($data);
    }

    /**/
    /**/
    /**/
    /**/

    private function updateHref($data) {
        $newUrl = array();
        foreach (explode('/', $this->url) as $value) {
            if (substr($value, 0, 1) === ':') {
                array_push($newUrl, $data[trim($value, ':')]);
            } else {
                array_push($newUrl, $value);
            }
        }
        $this->updatetHref = implode('/', $newUrl);
    }

    protected function setData($newData) {
        foreach ($this->fields as $index => $value) {
            $this->data[$index] = $newData[$value['name']];
        }
        $this->updateHref($this->data);
    }

    protected function queryToResource($query) {
        $result = array();
        while ($row = $query->fetch_assoc()) {
            $newRes = \RASTER\ResourceBuilder::get($this->name);
            $newRes->setData($row);
            array_push($result, $newRes);
        }
        $this->appendResources($result);
    }

    protected function runQuery($type, $queryData) {

        switch (strtolower($type)) {
            case 'get':
                $select = $this->querySelect($queryData);
                break;
            case 'post':
                $select = $this->queryInsert($queryData);
                break;
            case 'put':
                $select = $this->queryUpdate($queryData);
                break;
            case 'delete':
                $select = $this->queryDelete($queryData);
                break;
        }
        $this->queryToResource($select);
        return $this;
    }

}
