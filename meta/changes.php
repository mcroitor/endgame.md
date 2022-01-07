<?php

namespace meta;

class changes {

/** table name constant */
	public const __name__ = 'changes';

/** table columns fields */
	public $id;
	public $nr_games;
	public $filename;
	public $date;

/** table columns names */
	public const ID = 'id';
	public const NR_GAMES = 'nr_games';
	public const FILENAME = 'filename';
	public const DATE = 'date';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
