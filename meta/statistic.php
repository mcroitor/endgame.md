<?php

namespace meta;

class statistic {

/** table name constant */
	public const __name__ = 'statistic';

/** table columns fields */
	public $id;
	public $query;
	public $ip;
	public $time;

/** table columns names */
	public const ID = 'id';
	public const QUERY = 'query';
	public const IP = 'ip';
	public const TIME = 'time';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
