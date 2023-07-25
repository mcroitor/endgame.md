<?php

namespace meta;

class modules {

/** table name constant */
	public const __name__ = 'modules';

/** table columns fields */
	public $id;
	public $name;
	public $entry_point;

/** table columns names */
	public const ID = 'id';
	public const NAME = 'name';
	public const ENTRY_POINT = 'entry_point';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
