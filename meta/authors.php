<?php

namespace meta;

class authors {

/** table name constant */
	public const __name__ = 'authors';

/** table columns fields */
	public $author_name;

/** table columns names */
	public const AUTHOR_NAME = 'author_name';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
