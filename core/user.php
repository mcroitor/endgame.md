<?php

namespace mc;

use config;

class user
{
    private const GUEST_NAME = "__guest__";
    private const GUEST_ROLE = "guest";

    /**
     * initialize session and set user session if not exists
     */
    public static function init()
    {
        session_start();

        if (empty($_SESSION["user"])) {
            $_SESSION["user"] = [
                "name" => self::GUEST_NAME,
                "role" => self::GUEST_ROLE,
                "id" => 0,
                "capabilities" => []
            ];
        }
        user::repair();
    }

    /**
     * repair user session if it is broken
     */
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
        if(empty($_SESSION["user"]["id"])) {
            $_SESSION["user"]["id"] = 0;
        }
    }

    /**
     * return user session information
     * @return array
     */
    protected static function session()
    {
        return $_SESSION["user"];
    }

    /**
     * return user name
     * @return string
     */
    public static function name()
    {
        return user::session()["name"];
    }

    /**
     * return user id
     * @return integer
     */
    public static function id() {
        return user::session()["id"];
    }

    /**
     * return user role
     * @return string
     */
    public static function role()
    {
        return user::session()["role"];
    }

    /**
     * return user capabilities
     * @return array
     */
    public static function capabilities()
    {
        return user::session()["capabilities"];
    }

    /**
     * validate if user is authenticated
     * @return bool
     */
    public static function is_authenticated(): bool
    {
        return user::name() === self::GUEST_NAME;
    }

    /**
     * check if user has a capability. Can be used capability id or
     * capability name for validation.
     * @param int|string $capability
     * @return bool
     */
    public static function has_capability(int|string $capability): bool
    {
        return isset(user::capabilities()[$capability]) ||
        array_search($capability, user::capabilities()) !== false;
    }

    /**
     * check if user has a role.
     * @param string $role
     * @return bool
     */
    public static function has_role(string $role): bool
    {
        return user::role() === $role;
    }

    /**
     * login user
     */
    #[route("user/login")]
    public static function login()
    {
        if(user::has_capability("user::authenticate") === false) {
            header("location:" . config::www);
            exit();
        }
        $db = new \mc\sql\database(\config::dsn);
        $login = filter_input(INPUT_POST, "login");
        $password = filter_input(INPUT_POST, "password");
        $user = $db->select("user", ["id", "name", "role_id"], [
            "login" => $login,
            "password" => user::crypt($login, $password)
        ]);
        if (count($user) !== 1) {
            header("location:" . config::www);
            return;
        }
        $_SESSION["user"]["id"] = $user[0]["id"];
        $_SESSION["user"]["name"] = $user[0]["name"];
        $_SESSION["user"]["role_id"] = $user[0]["role_id"];
        $_SESSION["user"]["capabilities"] = user::load_capabilities($_SESSION["user"]["role_id"]);
        header("location:" . config::www);
    }

    /**
     * load capabilities by role id
     * @param int $roleId
     * @return array
     */
    private static function load_capabilities(int $roleId) {
        $db = new \mc\sql\database(\config::dsn);
        $capIds = $db->select_column(
            "role_capabilities",
            "capability_id",
            ["role_id" => $roleId]
        );
        $result = [];
        foreach ($capIds as $capId){
            $result[$capId] = $db->select_column("capabilities", "name", ["id" => $capId])[0];
        }
        return $result;
    }

    /**
     * log out, destroy session and redirect
     */
    #[route("user/logout")]
    public static function logout()
    {
        session_destroy();
        header("location:" . config::www);
        exit();
    }

    /**
     * login form
     * @return string
     */
    public static function login_form()
    {
        if (user::has_capability("user::authenticate")) {
            return file_get_contents(config::template_dir . "loginform.template.php");
        }
        return file_get_contents(config::template_dir . "usermenu.template.php");
    }

    /**
     * crypt password
     */
    private static function crypt(string $login, string $password) {
        return crypt($password . $login, $login);
    }

    /**
     * register new user
     * @return integer user id
     */
    public static function register(array $data) {
        // TODO: rewrite to POST method
        $db = new \mc\sql\database(config::dsn);
        $data["password"] = user::crypt($data["login"], $data["password"]);
        return $db->insert("user", $data);
    }
    
    /**
     * user menu
     * @return string
     */
    public static function user_menu() {
        if(user::has_capability("user::authenticate")) {
            return "";
        }

        $userMenu = '<a href="/?q=user/info" class="menu-title twelve columns menu-item">Hello, ' .
            user::name() . '</a>';
        $db = new \mc\sql\database(config::dsn);
        $userMenuLinks = $db->select("user_menu");

        foreach($userMenuLinks as $menu) {
            if(user::has_capability($menu["capability_id"])) {
                $ref = $menu["reference"];
                $name = $menu["name"];
                $userMenu .= "<a href='{$ref}' class='menu-item twelve columns'>{$name}</a>";
            }
        }

        return $userMenu;
    }

    /**
     * user information
     */
    #[route("user/info")]
    public static function info() {
        if(user::has_capability("user::authenticate")) {
            header("location:" . config::www);
            exit();
        }
        $db = new \mc\sql\database(config::dsn);
        $userInfo = self::session();
        $userCapabilities = self::capabilities();
        $template = file_get_contents(config::template_dir . "userinfo.template.php");
        $template = new \mc\template($template, ["prefix" => "<!-- ", "suffix" => " -->"]);

        $userInfo["capabilities"] = "<table class='u-full-width'>";
        $userInfo["capabilities"] .= "<tr><th>Group</th><th>Capability</th></tr>";
        foreach($userCapabilities as $capability) {
            $group = explode("::", $capability)[0];
            $userInfo["capabilities"] .= "<tr><td>{$group}</td><td>{$capability}</td></tr>";
        }
        $userInfo["capabilities"] .= "</table>";
        return $template->fill($userInfo)->value();
    }
}
