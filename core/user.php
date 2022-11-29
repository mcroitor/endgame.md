<?php

namespace mc;

use config;

class user
{
    private const GUEST_NAME = "__guest__";
    private const GUEST_ROLE = "guest";

    private const LOGOUT_FORM = "<a href='/api/?q=user/logout' id='login-form' class='navbar-link'>Log Out</a>";

    public static function init()
    {
        session_start();

        if (empty($_SESSION["user"])) {
            $_SESSION["user"] = [
                "name" => self::GUEST_NAME,
                "role" => self::GUEST_ROLE,
                "capabilities" => []
            ];
        }
        user::repair();
    }

    private static function repair()
    {
        if (empty($_SESSION["user"]["name"])) {
            $_SESSION["user"]["name"] = self::GUEST_NAME;
        }
        if (empty($_SESSION["user"]["role"])) {
            $_SESSION["user"]["role"] = self::GUEST_ROLE;
        }
        if (empty($_SESSION["user"]["capabilities"])) {
            $_SESSION["user"]["capabilities"] = [
                "user::authenticate"
            ];
        }
    }

    protected static function session()
    {
        return $_SESSION["user"];
    }

    public static function name()
    {
        return user::session()["name"];
    }

    public static function role()
    {
        return user::session()["role"];
    }

    public static function capabilities()
    {
        return user::session()["capabilities"];
    }

    public static function is_authenticated(): bool
    {
        return user::name() === self::GUEST_NAME;
    }

    public static function has_capability(string $capability): bool
    {
        return array_search($capability, user::capabilities()) !== false;
    }

    public static function has_role(string $role): bool
    {
        return user::role() === $role;
    }

    public static function login()
    {
        $db = new \mc\sql\database(\config::dsn);
        $login = filter_input(INPUT_POST, "login");
        $password = filter_input(INPUT_POST, "password");
        $user = $db->select("user", ["name", "role_id"], [
            "login" => $login,
            "password" => user::crypt($login, $password)
        ]);
        if (count($user) !== 1) {
            header("location:" . config::www);
            return;
        }
        $_SESSION["user"]["name"] = $user[0]["name"];
        $_SESSION["user"]["role_id"] = $user[0]["role_id"];
        $cap_ids = $db->select_column(
            "role_capabilities",
            "capability_id",
            ["role_id" => $_SESSION["user"]["role_id"]]
        );
        $_SESSION["user"]["capabilities"] = user::load_capabilities($cap_ids);
        header("location:" . config::www);
    }

    private static function load_capabilities(array $cap_ids) {
        $db = new \mc\sql\database(\config::dsn);
        $result = [];
        foreach ($cap_ids as $capability_id){
            $result[] = $db->select_column("capabilities", "name", ["id" => $capability_id])[0];
        }
        return $result;
    }

    public static function logout()
    {
        session_destroy();
        header("location:" . config::www);
    }

    public static function login_form()
    {
        if (user::has_capability("user::authenticate")) {
            return file_get_contents(config::template_dir . "loginform.template.php");
        }
        return user::LOGOUT_FORM;
    }

    private static function crypt(string $login, string $password) {
        return crypt($password . $login, $login);
    }

    public static function register(array $data) {
        $db = new \mc\sql\database(config::dsn);
        $data["password"] = user::crypt($data["login"], $data["password"]);
        return $db->insert("user", $data);
    }
}
