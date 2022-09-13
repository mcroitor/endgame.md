<?php

namespace core;

session_start();

class user {
    private const GUEST_NAME = "__guest__";
    private static $name;
    
    public static function check() {
        self::$name = filter_input(INPUT_SESSION, "name", FILTER_DEFAULT);
        if (self::is_authenticated() === false) {
            self::authenticate();
        }
    }
    
    private static function is_authenticated(): bool {
        return self::$name === self::GUEST_NAME;
    }
    
    private static function authenticate() {
        
    }
    
    public static function login() {
        $db = new \core\database(\config::dsn);
        self::$name = $db->select("user", "name");
    }
    
    public static function logout() {
        self::$name = self::GUEST_NAME;
        session_destroy();
    }
}
