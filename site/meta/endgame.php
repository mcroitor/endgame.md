<?php

namespace meta;

class endgame {

/** table name constant */
	public const __name__ = 'endgame';

/** table columns fields */
	public $pid;
	public $fen;
	public $author;
	public $date;
	public $source;
	public $award;
	public $stipulation;
	public $commentary;
	public $whitep;
	public $blackp;
	public $piece_pattern;
	public $theme;
	public $cook;

/** table columns names */
	public const PID = 'pid';
	public const FEN = 'fen';
	public const AUTHOR = 'author';
	public const DATE = 'date';
	public const SOURCE = 'source';
	public const AWARD = 'award';
	public const STIPULATION = 'stipulation';
	public const COMMENTARY = 'commentary';
	public const WHITEP = 'whitep';
	public const BLACKP = 'blackp';
	public const PIECE_PATTERN = 'piece_pattern';
	public const THEME = 'theme';
	public const COOK = 'cook';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
