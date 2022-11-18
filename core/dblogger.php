<?php

namespace core;

class dblogger {

    private $db;
    private $statistic_tbl;

    public function __construct(\mc\sql\database $db, string $statistic_tbl) {
        $this->db = $db;
        $this->statistic_tbl = $statistic_tbl;

        $this->db->query_sql("CREATE TABLE IF NOT EXISTS {$this->statistic_tbl} (
            `id` INTEGER PRIMARY KEY,
            `query` TEXT NOT NULL,
            `ip` TEXT NOT NULL,
            `time` TEXT NOT NULL)", "", false);
    }

    public function write(array $message) {
        $this->db->insert($this->statistic_tbl, $message);
    }

}
