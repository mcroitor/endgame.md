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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

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

    public static function csrf_token(): string
    {
        return $_SESSION["csrf_token"] ?? "";
    }

    public static function csrf_field(): string
    {
        $token = htmlspecialchars(self::csrf_token(), ENT_QUOTES, "UTF-8");
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\" />";
    }

    public static function verify_csrf(?string $token = null): bool
    {
        $submitted = $token ?? (filter_input(INPUT_POST, "csrf_token") ?? "");
        $expected = self::csrf_token();
        return $expected !== "" && is_string($submitted) && hash_equals($expected, $submitted);
    }

    public static function require_csrf(): void
    {
        if (!self::verify_csrf()) {
            http_response_code(403);
            exit("Invalid CSRF token");
        }
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
        return user::name() !== self::GUEST_NAME;
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
        self::require_csrf();
        if(user::has_capability("user::authenticate") === false) {
            header("location:" . config::www);
            exit();
        }
        $login = filter_input(INPUT_POST, "login") ?? "";
        $password = filter_input(INPUT_POST, "password") ?? "";
        if ($login === "" || $password === "") {
            header("location:" . config::www);
            exit();
        }
        $user = config::$db->select("user", ["id", "name", "password", "role_id"], [
            "login" => $login,
        ]);
        if (count($user) !== 1) {
            header("location:" . config::www);
            exit();
        }

        $storedHash = (string) ($user[0]["password"] ?? "");
        $isModernValid = password_verify($password, $storedHash);
        $isLegacyValid = !$isModernValid && hash_equals(user::crypt($login, $password), $storedHash);
        if (!$isModernValid && !$isLegacyValid) {
            header("location:" . config::www);
            exit();
        }

        if ($isLegacyValid || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            config::$db->update(
                "user",
                ["password" => user::hash_password($password)],
                ["id" => $user[0]["id"]]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION["user"]["id"] = $user[0]["id"];
        $_SESSION["user"]["name"] = $user[0]["name"];
        $_SESSION["user"]["role_id"] = $user[0]["role_id"];
        $roles = config::$db->select_column(
            \meta\role::__name__,
            \meta\role::NAME,
            ["id" => $user[0]["role_id"]]
        );
        $_SESSION["user"]["role"] = $roles[0] ?? self::GUEST_ROLE;
        $_SESSION["user"]["capabilities"] = user::load_capabilities($_SESSION["user"]["role_id"]);
        header("location:" . config::www);
        exit();
    }

    /**
     * load capabilities by role id
     * @param int $roleId
     * @return array
     */
    private static function load_capabilities(int $roleId) {
        $capIds = config::$db->select_column(
            "role_capabilities",
            "capability_id",
            ["role_id" => $roleId, "permission" => 1]
        );
        $result = [];
        foreach ($capIds as $capId){
            $capNames = config::$db->select_column("capabilities", "name", ["id" => $capId]);
            if (!empty($capNames)) {
                $result[$capId] = $capNames[0];
            }
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
     * hash password using modern PASSWORD_DEFAULT algorithm
     */
    public static function hash_password(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * legacy crypt password (kept for backward-compatible verification)
     */
    public static function crypt(string $login, string $password) {
        return crypt($password . $login, $login);
    }

    /**
     * register new user
     * @return integer user id
     */
    public static function register(array $data) {
        // TODO: rewrite to POST method
        $data["password"] = user::hash_password($data["password"]);
        return config::$db->insert("user", $data);
    }
    
    /**
     * user menu
     * @return string
     */
    public static function user_menu() {
        if(user::has_capability("user::authenticate")) {
            return "";
        }

        $userMenu = '<a href="<!-- www -->/?q=user/info" class="menu-title twelve columns menu-item">Hello, ' .
            user::name() . '</a>';
        $userMenuLinks = config::$db->select("user_menu");
        $addedRefs = [];

        foreach($userMenuLinks as $menu) {
            if(user::has_capability($menu["capability_id"])) {
                $ref = $menu["reference"];
                $name = $menu["name"];
                $addedRefs[$ref] = true;
                $userMenu .= "<a href='{$ref}' class='menu-item twelve columns'>{$name}</a>";
            }
        }

        if (user::has_role("admin")) {
            if (!isset($addedRefs["/?q=management"])) {
                $userMenu .= "<a href='/?q=management' class='menu-item twelve columns'>Management</a>";
            }
            if (!isset($addedRefs["/?q=analytics/links"])) {
                $userMenu .= "<a href='/?q=analytics/links' class='menu-item twelve columns'>Analytics</a>";
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

    #[route("user/edit")]
    public static function edit(array $params)
    {
        if (!user::has_role("admin")) {
            header("location:" . config::www);
            exit();
        }
        $userId = isset($params[0]) ? (int) $params[0] : 0;
        if ($userId <= 0) {
            return "Invalid user ID.";
        }
        $users = config::$db->select(\meta\user::__name__, ["id", "name", "login", "role_id"], ["id" => $userId]);
        if (empty($users)) {
            return "User not found.";
        }
        $target = $users[0];
        $roles = config::$db->select(\meta\role::__name__, ["id", "name"]);

        $loginEsc = htmlspecialchars((string) $target["login"], ENT_QUOTES, "UTF-8");
        $nameEsc = htmlspecialchars((string) $target["name"], ENT_QUOTES, "UTF-8");
        $roleOptions = "";
        foreach ($roles as $r) {
            $rId = (int) $r["id"];
            $rName = htmlspecialchars((string) $r["name"], ENT_QUOTES, "UTF-8");
            $selected = $rId === (int) $target["role_id"] ? " selected" : "";
            $roleOptions .= "<option value='{$rId}'{$selected}>{$rName}</option>";
        }

        return <<<HTML
<h2>Edit User: {$loginEsc}</h2>
<form method="post" action="<!-- www -->/?q=user/update/{$userId}">
    <!-- csrf-input -->
    <div class="row">
        <div class="six columns">
            <label for="user-name">Display Name</label>
            <input type="text" id="user-name" name="name" value="{$nameEsc}" class="u-full-width" required />
        </div>
        <div class="six columns">
            <label for="user-role">Role</label>
            <select id="user-role" name="role_id" class="u-full-width">
                {$roleOptions}
            </select>
        </div>
    </div>
    <div class="row">
        <div class="six columns">
            <label for="user-password">New Password (leave blank to keep current)</label>
            <input type="password" id="user-password" name="new_password" class="u-full-width" autocomplete="new-password" />
        </div>
    </div>
    <div class="row">
        <div class="twelve columns">
            <input type="submit" value="Save" class="button-primary" />
            <a href="<!-- www -->/?q=management/users" class="button">Cancel</a>
        </div>
    </div>
</form>
HTML;
    }

    #[route("user/update")]
    public static function update(array $params)
    {
        self::require_csrf();
        if (!user::has_role("admin")) {
            header("location:" . config::www);
            exit();
        }
        $userId = isset($params[0]) ? (int) $params[0] : 0;
        $name = trim((string) (filter_input(INPUT_POST, "name") ?? ""));
        $roleId = (int) (filter_input(INPUT_POST, "role_id", FILTER_VALIDATE_INT) ?? 0);
        $newPassword = (string) (filter_input(INPUT_POST, "new_password") ?? "");

        if ($userId <= 0 || $name === "" || $roleId <= 0) {
            header("location:" . config::www . "/?q=management/users");
            exit();
        }

        $updateData = [
            \meta\user::NAME => $name,
            \meta\user::ROLE_ID => $roleId,
        ];
        if ($newPassword !== "") {
            $updateData[\meta\user::PASSWORD] = self::hash_password($newPassword);
        }

        config::$db->update(\meta\user::__name__, $updateData, ["id" => $userId]);
        header("location:" . config::www . "/?q=management/users");
        exit();
    }
}
