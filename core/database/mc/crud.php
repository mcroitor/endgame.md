<?php

namespace mc\sql;

use \mc\sql\database;

/**
 * Simple CRUD implementation
 */
class crud {
    private $_db;
    private $_table;
    private $_key;

    /**
     * crud constructor, must be passed a database object and a table name
     * the key is the primary key of the table, defaults to 'id' 
     * @param database $db
     * @param string $table
     * @param string $key
     */
    public function __construct(database $db, string $table, $key = "id")
    {
        $this->_db = $db;
        $this->_table = $table;
        $this->_key = $key;
    }

    /**
     * insert a new record. Returns the id of the new record
     *
     * @param array|object $data
     */
    public function insert($data) {
        $data = (array)$data;
        return $this->_db->insert($this->table(), $data);
    }

    /**
     * select a record by id / key
     * 
     * @param int|string $id
     * @return array
     */
    public function select($id) {
        $result = $this->_db->select($this->table(), ["*"], [$this->key() => $id], database::LIMIT1);
        if(count($result) === 0) {
            return null;
        }
        return $result[0];
    }

    /**
     * select <b>$limit</b> records from <b>$offset</b> record.
     * 
     * @param int $offset
     * @param int $limit
     * @return array 
     */
    public function all($offset = 0, $limit = 100) {
        return $this->_db->select($this->table(), ["*"], [], ["offset" => $offset, "limit" => $limit]);
    } 

    /**
     * update a record by id / key
     * parameter <b>$data</b> must include the key
     * 
     * @param array|object $data
     */
    public function update($data) {
        $data = (array)$data;
        $this->_db->update($this->table(), $data, [$this->key() => $data[$this->key()]]);
    }

    /**
     * delete a record by id / key
     * 
     * @param int|string $id
     */
    public function delete($id) {
        $this->db->delete($this->table(), [$this->key() => $id]);
    }

    /**
     * return the table name, userd for all CRUD operations
     * 
     * @return string
     */
    public function table() {
        return $this->_table;
    }

    /**
     * return the key name, userd for all CRUD operations
     * 
     * return string
     */
    public function key() {
        return $this->_key;
    } 
}
