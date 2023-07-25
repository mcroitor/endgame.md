<?php

namespace meta;

class composer {

/** table name constant */
	public const __name__ = 'composer';

/** table columns fields */
	public $family_name;
	public $first_name;
	public $second_name;
	public $id_alphabet;
	public $country;
	public $birth;
	public $id_method;
	public $death;
	public $id_own_alphabet;

/** table columns names */
	public const FAMILY_NAME = 'family_name';
	public const FIRST_NAME = 'first_name';
	public const SECOND_NAME = 'second_name';
	public const ID_ALPHABET = 'id_alphabet';
	public const COUNTRY = 'country';
	public const BIRTH = 'birth';
	public const ID_METHOD = 'id_method';
	public const DEATH = 'death';
	public const ID_OWN_ALPHABET = 'id_own_alphabet';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
