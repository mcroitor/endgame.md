<?php

namespace meta;

class user {

/** table name constant */
	public const __name__ = 'user';

/** table columns fields */
	public $id;
	public $name;
	public $login;
	public $password;
	public $role_id;

/** table columns names */
	public const ID = 'id';
	public const NAME = 'name';
	public const LOGIN = 'login';
	public const PASSWORD = 'password';
	public const ROLE_ID = 'role_id';

/** constructor */
	public function __construct(array|object $data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
	}
}
