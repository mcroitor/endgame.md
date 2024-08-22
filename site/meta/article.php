<?php

namespace meta;

class article {

/** table name constant */
	public const __name__ = 'article';

/** table columns fields */
	public $id;
	public $title;
	public $body;
	public $author;
	public $published;

/** table columns names */
	public const ID = 'id';
	public const TITLE = 'title';
	public const BODY = 'body';
	public const AUTHOR = 'author';
	public const PUBLISHED = 'published';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
