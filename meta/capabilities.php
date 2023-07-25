<?php

namespace meta;

class capabilities {

/** table name constant */
	public const __name__ = 'capabilities';

/** table columns fields */
	public $id;
	public $name;

/** table columns names */
	public const ID = 'id';
	public const NAME = 'name';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
