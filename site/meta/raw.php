<?php

namespace meta;

class raw {

/** table name constant */
	public const __name__ = 'raw';

/** table columns fields */
	public $id;
	public $data;

/** table columns names */
	public const ID = 'id';
	public const DATA = 'data';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
