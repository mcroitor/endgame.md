<?php

namespace core;

session_start();

class user {
    private const GUEST_NAME = "__guest__";
    private static $name;
    
    public static function is_authenticated(): bool {
        return self::$name === self::GUEST_NAME;
    }

    public static function has_capability(string $capability): bool {
        return true;
    }

    public static function has_role(string $role): bool {
        return true;
    }
    
    public static function login() {
        $db = new \core\database(\config::dsn);
        self::$name = $db->select("user", ["name"]);
    }
    
    public static function logout() {
        self::$name = self::GUEST_NAME;
        session_destroy();
    }
}
