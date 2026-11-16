# Bugs

## BUG 001: Inverted authentication check in `user::is_authenticated()`

**Location:** `core/user.php`, lines 99-102

```php
public static function is_authenticated(): bool
{
    return user::name() === self::GUEST_NAME;
}
```

`is_authenticated()` returns `true` when the current user is the guest user (`__guest__`) and `false` for any authenticated user. It should use `!==` instead of `===`.

## BUG 002: Denied capabilities (`permission = 0`) are granted in `user::load_capabilities()`

**Location:** `core/user.php`, lines 162-174

```php
private static function load_capabilities(int $roleId) {
    $db = new \mc\sql\database(\config::dsn);
    $capIds = config::$db->select_column(
        "role_capabilities",
        "capability_id",
        ["role_id" => $roleId]
    );
    $result = [];
    foreach ($capIds as $capId){
        $result[$capId] = config::$db->select_column("capabilities", "name", ["id" => $capId])[0];
    }
    return $result;
}
```

`load_capabilities()` queries `role_capabilities` by `role_id` without checking `permission = 1`. In `update/sql/data/common.sql`, regular users (`role_id = 2`) have rows with `permission = 0` (deny) for `endgame::create`, `endgame::update`, and `endgame::delete`, so all those denied capabilities are loaded as allowed. Additionally, `$db` on line 163 is created and never used, and `[0]` on line 171 will fail if a capability ID does not exist.

## BUG 003: Unchecked `null` input and missing `exit()` in `user::login()`

**Location:** `core/user.php`, lines 136-145

```php
$login = filter_input(INPUT_POST, "login");
$password = filter_input(INPUT_POST, "password");
$user = config::$db->select("user", ["id", "name", "role_id"], [
    "login" => $login,
    "password" => user::crypt($login, $password)
]);
if (count($user) !== 1) {
    header("location:" . config::www);
    return;
}
```

When `login` or `password` POST parameters are missing, `filter_input()` returns `null`, causing a `TypeError` in `user::crypt(string $login, string $password)`. When `count($user) !== 1`, the method uses `return;` instead of `exit()`, allowing execution to continue after setting the `Location` header.

## BUG 004: Missing `return` statements in `crud::insert_or_update()`

**Location:** `core/database/mc/crud.php`, lines 86-103

```php
public function insert_or_update($data)
{
    /// no key - insert object
    if (empty($data[$this->_key])) {
        $this->insert($data);
    }

    $key = $data[$this->_key];
    echo "[debug] key found " . $key . PHP_EOL;
    $result = $this->select($key);
    /// object not found, insert object
    if (empty($result)) {
        $this->insert($data);
    }

    /// update object
    $this->update($data);
}
```

Both `if` blocks call `$this->insert($data)` without returning. When `$data[$this->_key]` is empty, execution falls through to `$data[$this->_key]` (undefined array key), `select()`, a second `insert()`, and `update()`. When the record is not found, it inserts the record and then immediately runs `update($data)` as well.

## BUG 005: Wrong key used to delete module in `management::uninstallModule()`

**Location:** `core/management.php`, lines 84-91

```php
public static function uninstallModule($module)
{
    $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__);
    $crud->delete($module);
    if (file_exists(config::modules_dir . "{$module}/install")) {
        include config::modules_dir . "{$module}/install/uninstall.php";
    }
}
```

`\mc\sql\crud` defaults to `$key = "id"`, while `$module` is the module name string (e.g., `"articles"`). Calling `$crud->delete($module)` executes `DELETE FROM modules WHERE id = '<module_name>'` instead of `WHERE name = '<module_name>'`, so the module row is never deleted.

## BUG 006: Commented-out `pgn` route in `api/index.php`

**Location:** `api/index.php`, lines 5-11

```php
// #[\mc\route("pgn")]
function get_pgn(array $params)
{
    $pgnId = empty($params) ? 1 : (int) $params[0];
    $crud = new \mc\sql\crud(\config::$db, "raw");
    return $crud->select($pgnId);
}
```

The `#[\mc\route("pgn")]` attribute is commented out, so `api/?q=pgn/<id>` (called by `pgnLive()` and `getPgn()` in `scripts/studydb.js`, lines 120 and 139) falls back to the default route and returns `""` instead of the PGN JSON.

## BUG 007: Mismatched date parameter names between `studydb.js` and `api/index.php`

**Location:** `scripts/studydb.js`, lines 79-80 and `api/index.php`, lines 25-26

```javascript
        "from_date": get("from_date").value,
        "to_date": get("to_date").value,
```

```php
    $request->fromDate = empty($request->fromDate) ? "0000" : $request->fromDate;
    $request->toDate = empty($request->toDate) ? "2050" : $request->toDate;
```

`scripts/studydb.js` sends `from_date` and `to_date`, whereas `api/index.php` reads `$request->fromDate` and `$request->toDate`. As a result, user-selected date ranges are ignored and always default to `"0000"`–`"2050"`.

## BUG 008: SQL injection in `api/index.php` `get_data()`

**Location:** `api/index.php`, lines 37-53

```php
        \mc\sql\query::WHERE => [
            "author LIKE '%{$request->author}%'",
            "whitep BETWEEN {$request->wmin} and {$request->wmax}",
            "blackp BETWEEN {$request->bmin} and {$request->bmax}",
            "date BETWEEN {$request->fromDate} and {$request->toDate}",
        ],
    ];

    if ($request->stipulation !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "stipulation LIKE '{$request->stipulation}'";
    }
    if ($request->theme !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "theme LIKE '%{$request->theme}%'";
    }
    if ($request->piece_pattern != "") {
        $query_config[\mc\sql\query::WHERE][] = "piece_pattern='{$request->piece_pattern}'";
    }
```

User-controlled JSON fields from `php://input` are interpolated directly into raw SQL `WHERE` conditions without escaping or parameterized queries.

## BUG 009: SQL injection in `modules/pdf/pdf.php` `get()`

**Location:** `modules/pdf/pdf.php`, lines 138-154

```php
        $query = str_replace(
            ["{author}", "{white_min}", "{white_max}", "{black_min}", "{black_max}"],
            [$author, $white_min, $white_max, $black_min, $black_max],
            self::QUERY
        );
        if ($stipulation !== "-") {
            $query .= " AND stipulation LIKE '{$stipulation}' ";
        }
        if ($theme !== "-") {
            $query .= " AND theme LIKE '%{$theme}%' ";
        }
        if ($piece_pattern != "") {
            $query .= " AND piece_pattern='{$piece_pattern}' ";
        }

        $query .= " AND date >= {$from_date} AND date <= {$to_date} ORDER BY date ASC LIMIT 1000";
        $result = $db->query_sql($query);
```

`GET` parameters (`author`, `stipulation`, `theme`, `piece_pattern`, `from_date`, `to_date`) are concatenated directly into the SQL query without escaping or prepared statements.

## BUG 010: Undefined variable `$db` in `cli/reset-password.php`

**Location:** `cli/reset-password.php`, lines 40-42

```php
$user[\meta\user::PASSWORD] = \mc\user::crypt($login, $password);

$db->update(\meta\user::__name__, $user, ["id" => $user[\meta\user::ID]]);
```

`$db` is never defined in `cli/reset-password.php` (`\config::$db` is used on line 31), causing an undefined variable / null method call error when resetting a password.

## BUG 011: Broken `fen` validation and commented-out `Content-type` header in `modules/diagram/index.php`

**Location:** `modules/diagram/index.php`, lines 3-6 and 21-22

```php
$fen = filter_input(INPUT_GET, "fen");
if ($fen === false) {
    exit("<b>using</b>:<br />index.php?fen=&lt;FEN&gt;");
}
```

```php
// header('Content-type: image/png');
imagepng($d->toImage());
```

`filter_input(INPUT_GET, "fen")` returns `null` (not `false`) when `fen` is omitted, so `if ($fen === false)` never triggers and `null` is passed to `new Diagram($fen, $options)`. Additionally, `header('Content-type: image/png')` is commented out, so raw PNG bytes are sent without the image MIME type.

## BUG 012: Inverted EPD check and broken `strpos()` comparison in `Board`

**Location:** `modules/diagram/diagram.class.php`, lines 129-131 and 170-185

```php
        // fen string verifying
        if ($this->is_correct_epd($epd) === true) {
            $epd = "8/8/8/8/8/8/8/8";
        }
```

```php
    private function is_correct_epd($epd) {
        $len = strlen($epd);
        $fields = 0;
        for ($i = 0; $i < $len; $i++) {
            if (strpos("12345678", $epd[$i]) === true) {
                $fields += $epd[$i];
            } elseif (strpos("kqrbnpKQRBNPX", $epd[$i]) === true) {
                $fields++;
            } elseif ($epd[$i] === '/') {
                
            } else {
                $fields += 100;
            }
        }
        return $fields === 64;
    }
```

`strpos()` returns an integer index or `false`—never `true`—so `is_correct_epd()` always returns `false`. Meanwhile, `fen2board()` checks `=== true` (inverted; it meant `=== false`), so valid FENs are not reset, and invalid FENs are also not reset and crash `explode("/", $epd)` / `$lines[$i]` with undefined array keys.

## BUG 013: Property name casing typo in `MakeBook::DrawDiagramPage()`

**Location:** `modules/pdf/lib/makebook.php`, line 102

```php
            $position->fontsize = mm2px($dgSize / 8 - 2);
            $this->DrawDiagram($position, 20 + $j * $sizeX, 10 + $i * $sizeY);
```

`ChessPosition` defines `$fontSize` (camelCase), not `$fontsize`. Assigning `$position->fontsize` creates a dynamic property that is ignored by `DrawDiagram()`, which reads `$position->fontSize`.

## BUG 014: `attic::getHtml()` fetches articles instead of attic records

**Location:** `modules/attic/attic.php`, lines 29-33

```php
    public static function getHtml(array $params) {
        $offset = isset($params[0])? (int)$params[0] : 0;
        $limit = isset($params[1]) ? (int)$params[1] : 5;
        $template = file_get_contents(__DIR__ . "/attic.template.php");
        $data = articles::get($offset, $limit);
```

`attic::getHtml()` calls `articles::get($offset, $limit)` instead of `attic::get($offset, $limit)` (or `self::get($offset, $limit)`), rendering records from the `article` table instead of the `attic` table.

## BUG 015: Unchecked `preg_match()` and wrong moves regex in `Pgn::parse()`

**Location:** `modules/pgn.php`, lines 23 and 51-57

```php
        self::MOVES => "/\[(.*?)\]/",
```

```php
    public function parse(string $game) {
        $matches = [];
        foreach (self::PATTERNS as $key => $pattern) {
            preg_match($pattern, $game, $matches);
            $this->$key = $matches[1];
        }
    }
```

`Pgn::parse()` accesses `$matches[1]` without checking whether `preg_match()` matched, causing an undefined array key error if any PGN header is missing. Furthermore, `self::MOVES` uses `"/\[(.*?)\]/"`, which matches the first header tag inside brackets rather than the game moves.

## BUG 016: Deleted endgame is updated afterwards in `analytics::fixPiecePatterns()`

**Location:** `modules/analytics/analytics.php`, lines 111-128

```php
            if($pattern === "") {
                $db->delete(
                    \meta\endgame::__name__,
                    [\meta\endgame::PID => $endgame[\meta\endgame::PID]]
                );
                $stat["removed"]++;
            }
            if ($pattern === $endgame[\meta\endgame::PIECE_PATTERN]) {
                continue;
            }
            $endgame[\meta\endgame::PIECE_PATTERN] = $pattern;
            $db->update(
                \meta\endgame::__name__,
                $endgame,
                [\meta\endgame::PID => $endgame[\meta\endgame::PID]]
            );
            $stat["fixed"]++;
```

When `$pattern === ""` (e.g., `piece_pattern` was `"unknown"` or `NULL`), the row is deleted, but execution does not `continue;` and proceeds to run `$db->update(...)` and increment `$stat["fixed"]`.

## BUG 017: Unescaped `FEN` in `LIKE` query and desynchronized `raw` IDs in `endgame::upload()`

**Location:** `modules/endgame/endgame.php`, lines 88-92 and 113-114

```php
            $test_fen = $db->select(
                "endgame",
                ["pid, fen"],
                ["fen LIKE '%{$headers[\ChessGame::FEN]}%'"]
            );
```

```php
            $db->insert("endgame", $data);
            $db->insert("raw", ["data" => $game->getRaw()]);
```

`$headers[\ChessGame::FEN]` is interpolated directly into a raw SQL `LIKE` string without escaping. In addition, `raw` is inserted without matching the inserted `endgame` `pid`, so if any row is ever deleted from `endgame`, `raw.id` and `endgame.pid` will no longer correspond.

## BUG 018: Invalid foreign key table name in `update/sql/structure/common.sql`

**Location:** `update/sql/structure/common.sql`, line 30

```sql
CREATE TABLE role_capabilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INT NOT NULL,
    capability_id INT NOT NULL,
    permission INT NOT NULL DEFAULT 0, -- 0 - deny, 1 - allow
    FOREIGN KEY (role_id) REFERENCES role(id),
    FOREIGN KEY (capability_id) REFERENCES capability(id)
);
```

The foreign key references `capability(id)`, but the table is defined on line 12 as `capabilities`.

## BUG 019: Unchecked `$params[0]` and strict string-vs-int PID check in `analytics::author()`

**Location:** `modules/analytics/analytics.php`, lines 210-214

```php
        $action = $params[0];
        $pid = $params[1] ?? 0;
        if($pid === 0) {
            return "Invalid PID";
        }
```

Accessing `$params[0]` without checking `isset($params[0])` triggers an undefined array key warning when `?q=analytics/author` is called without arguments. Also, `$params[1]` from URL routing is a string, so `$pid === 0` evaluates to `false` when `$params[1]` is `"0"`.

## BUG 020: Unvalidated `pid` and destructive `str_replace("rn", ...)` in `cli/getpgn.php`

**Location:** `cli/getpgn.php`, lines 10-17

```php
$pid = filter_input(INPUT_GET, "pid");
$game = \config::$db->select("raw", ["*"], ["id" => $pid])[0]["data"];

header('Content-type: text/plain');
header("Content-Disposition: attachment; filename=endgame_{$pid}.pgn");
$game = str_replace(["rn", "\\r\\n"], " ", $game);
    
echo $game;
```

If `pid` is missing or not found in `raw`, `[0]["data"]` triggers an undefined array key error. Furthermore, `str_replace(["rn", "\\r\\n"], " ", $game)` replaces every literal `"rn"` substring in the PGN (corrupting words like `"Tournament"`, `"Bernstein"`, `"Return"`, etc.).

## BUG 021: Missing template delimiters and unguarded role lookup in `management::manageUsers()`

**Location:** `core/management.php`, lines 110-126

```php
        $template = new \mc\template(
            file_get_contents(config::template_dir . "manageoption.template.php")
        );
        $html = "<table class='u-full-width'>";
        $html .= "<tr><th>Username</th><th>Role</th><th>Action</th></tr>";

        $users = \config::$db->select(\meta\user::__name__, ["*"]);
        foreach ($users as $user) {
            $role = \config::$db->select(
                \meta\role::__name__,
                [\meta\role::NAME],
                ["id" => $user[\meta\user::ROLE_ID]]
            )[0][\meta\role::NAME];
            $html .= "<tr><td>{$user[\meta\user::LOGIN]}</td>" .
                "<td>{$role}</td>" .
                "<td><a href='/?q=user/edit/{$user[\meta\user::ID]}'>Edit</a></td></tr>";
        }
```

`manageUsers()` instantiates `\mc\template` without `["prefix" => "<!-- ", "suffix" => " -->"]`, whereas `theme/templates/manageoption.template.php` uses `<!-- title -->`, `<!-- description -->`, and `<!-- html -->` placeholders. As a result, none of the placeholders are replaced. Additionally, `[0][\meta\role::NAME]` is accessed without checking if a matching role exists, and the `Edit` link points to `/?q=user/edit/{id}`, which has no registered route.

## BUG 022: Wrong default `wmax` and `bmax` values in `modules/pdf/pdf.php`

**Location:** `modules/pdf/pdf.php`, lines 50-58, 68-76, and 129-132

```php
    private static function getWhiteMax()
    {
        return filter_input(
            INPUT_GET,
            "wmax",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 0]]
        );
    }
```

```php
    private static function getBlackMax()
    {
        return filter_input(
            INPUT_GET,
            "bmax",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 0]]
        );
    }
```

```php
        $white_max = (int) (self::getWhiteMax() ?? 32);
        $black_min = (int) (self::getBlackMin() ?? 0);
        $black_max = (int) (self::getBlackMax() ?? 32);
```

`getWhiteMax()` and `getBlackMax()` set `"default" => 0` instead of `32`. When `wmax` or `bmax` is omitted from the query string, `filter_input()` returns `0`, bypassing `?? 32` in `get()`, so the SQL query filters for `whitep <= 0 AND blackp <= 0` and returns no positions.

## BUG 023: Unchecked `raw` query result in `pdf::get()`

**Location:** `modules/pdf/pdf.php`, lines 179-180

```php
            $pgn = $db->select("raw", ["*"], ["id" => $value[\meta\endgame::PID]])[0]["data"];
            $solution = preg_replace('/\[\w+ ".+"\]\s/', "", $pgn);
```

`[0]["data"]` is accessed directly without checking whether `select()` returned a row. If the corresponding `raw` record is missing, PHP raises an undefined array key error and passes `null` to `preg_replace()`.

## BUG 024: Incomplete EPD rank validation and unchecked color lookup in `Diagram`

**Location:** `modules/diagram/diagram.class.php`, lines 170-185 and 324-327

```php
    private function is_correct_epd($epd) {
        $len = strlen($epd);
        $fields = 0;
        for ($i = 0; $i < $len; $i++) {
            if (strpos("12345678", $epd[$i]) !== false) {
                $fields += $epd[$i];
            } elseif (strpos("kqrbnpKQRBNPX", $epd[$i]) !== false) {
                $fields++;
            } elseif ($epd[$i] === '/') {
                
            } else {
                $fields += 100;
            }
        }
        return $fields === 64;
    }
```

```php
        $dark_color = imagecolorallocate(
                $d, $colors[$this->color][0], $colors[$this->color][1], $colors[$this->color][2]);
        $light_color = imagecolorallocate(
                $d, $colors[$this->color . "shift"][0], $colors[$this->color . "shift"][1], $colors[$this->color . "shift"][2]);
```

`is_correct_epd()` ignores `/` characters and only checks `$fields === 64`, so an EPD without 8 slash-separated ranks (e.g. `"88888888"`) passes validation and crashes `fen2board()` when indexing `$lines[$i]` for `$i = 1..7`. In `toImage()`, `$colors[$this->color]` is accessed without verifying that the requested `color` query parameter exists in `$colors`.

## BUG 025: Missing `endgame-form.template.php` in `endgame::newForm()`

**Location:** `modules/endgame/endgame.php`, lines 32-42

```php
    #[\mc\route('endgame/new')]
    public static function newForm()
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        \config::$logger->warn("not implemented yet");
        $template = \facade::template("endgame-form.template.php", self::TEMPLATES_DIR);
        return $template->fill(["path" => \config::www . "/modules/articles"])->value();
    }
```

`modules/endgame/templates/endgame-form.template.php` does not exist (only `endgame-search-form.template.php` and `endgame-import-form.template.php` exist). Navigating to `/?q=endgame/new` causes `file_get_contents()` to return `false` and throws a `TypeError` in `facade::file(): string`.

## BUG 026: Empty string inserted into integer columns in `endgame::upload()`

**Location:** `modules/endgame/endgame.php`, lines 105-111

```php
                \meta\endgame::AWARD => "",
                \meta\endgame::WHITEP => "",
                \meta\endgame::BLACKP => "",
                \meta\endgame::COMMENTARY => "",
                \meta\endgame::COOK => "",
                \meta\endgame::PIECE_PATTERN => "",
```

`whitep`, `blackp`, and `cook` are defined as `INTEGER NOT NULL` in `update/sql/structure/common.sql` and are queried with numeric `BETWEEN` filters in `api/index.php` and `modules/pdf/pdf.php`. Inserting `""` instead of counting pieces from the FEN and using integer `0` for `cook` prevents uploaded endgames from matching piece-count searches.

## BUG 027: Unpopulated `<!-- themes -->` select options in search form

**Location:** `modules/endgame/endgame.php`, lines 141-146 and `modules/endgame/templates/endgame-search-form.template.php`, lines 41-44

```php
    #[\mc\route("/")]
    public static function search_form()
    {
        $template = \facade::template("endgame-search-form.template.php", self::TEMPLATES_DIR);
        return $template->fill(["last-date" => date("Y")])->value();
    }
```

```html
            <div class="four columns">
                <select id="theme" class="u-full-width">
                    <!-- themes -->
                </select>
            </div>
```

`search_form()` never populates `<!-- themes -->`. Because `<select id="theme">` has no `<option>` elements, `get("theme").value` in `scripts/studydb.js` evaluates to `""` rather than `"-"`, breaking theme selection and causing `api/index.php` and `modules/pdf/pdf.php` to always add `theme LIKE '%%'`.

## BUG 028: PGN download endpoint outputs JSON instead of PGN text

**Location:** `modules/endgame/endgame.php`, lines 16-30 and `scripts/studydb.js`, lines 138-141

```php
    #[\mc\route("pgn")]
    public static function get(array $params)
    {
        $pgnId = empty($params) ? 1 : (int)$params[0];
        $crud = new \mc\sql\crud(\config::$db, "raw");
        $pgn = $crud->select($pgnId);
        if (empty($pgn)) {
            header("location:" . \config::www);
            exit();
        }
        header('Content-Type: text/txt');
        header("Content-Disposition: attachment; filename=endgame_{$pgnId}.pgn");
        echo json_encode($pgn);
        exit();
    }
```

```javascript
function getPgn(pid) {
    window.open('/api/?q=pgn/' + pid, 'getpgn', '');
    return false;
}
```

`endgame::get()` (`/?q=pgn/<id>`) sets a `.pgn` attachment header (`Content-Disposition: attachment; filename=endgame_{$pgnId}.pgn`) but echoes `json_encode($pgn)` instead of `$pgn["data"]`. Meanwhile, `getPgn()` in `scripts/studydb.js` opens `/api/?q=pgn/<id>`, which also returns raw JSON rather than a downloadable PGN file.

## BUG 029: Incomplete `clearAll()` reset and missing PGN check in `scripts/studydb.js`

**Location:** `scripts/studydb.js`, lines 107-124

```javascript
function clearAll() {
    get("stat").innerHTML = '';
    get("diag").innerHTML = '';
    get("author").value = '';
    get("wmin").value = '3';
    get("wmax").value = '3';
    get("bmin").value = '3';
    get("bmax").value = '3';
    get("from_date").value = '';
    get("to_date").value = '';
}

async function pgnLive(pid) {
    let pgn = await request('api/?q=pgn/' + pid);
    console.log(pgn.data);
```

`clearAll()` resets `wmin`/`wmax`/`bmin`/`bmax` to `'3'` (instead of the form defaults `2` and `7`), clears `from_date`/`to_date` to `''`, and does not reset `stipulation`, `theme`, or `cook`. In `pgnLive(pid)`, if the `raw` record is not found (`api/?q=pgn/<pid>` returns `[]`), `pgn.data` is `undefined` and is passed directly to `LichessPgnViewer`.

## BUG 030: Unchecked `null` POST parameters in `articles::create()`

**Location:** `modules/articles/articles.php`, lines 61-67

```php
        $data = [
            \meta\article::AUTHOR => \mc\user::id(),
            \meta\article::TITLE => filter_input(INPUT_POST, "article-title"),
            \meta\article::BODY => filter_input(INPUT_POST, "article-body"),
            \meta\article::PUBLISHED => date("Y-m-d H:i:s"),
        ];
        self::$crud->insert($data);
```

When `article-title` or `article-body` is omitted from the POST request, `filter_input()` returns `null`, causing `insert()` to fail on the `NOT NULL` constraints defined in `update/sql/structure/articles.sql`.

## BUG 031: `null` values not handled in `database::update()` and `query::build_values()`

**Location:** `core/database/mc/database.php`, lines 190-199 and `core/database/mc/query.php`, lines 218-224

```php
    public function update(string $table, array $values, array $conditions): array
    {
        $tmp2 = [];
        foreach ($values as $key => $value) {
            $value = $this->pdo->quote($value);
            $tmp2[] = "{$key}={$value}";
        }
```

```php
    protected function build_values(): string {
        if(empty($this->values)){
            return "";
        }
        // TODO: quote values!
        return \implode(", ", $this->values);
    }
```

Unlike `database::insert()`, `database::update()` passes `$value` directly to `$this->pdo->quote($value)` without checking `is_null($value)`, which fails/deprecates on `null` values in PHP 8.1+. In `query::build_values()`, values are neither quoted nor formatted as `column = value` assignments for `UPDATE` queries.
