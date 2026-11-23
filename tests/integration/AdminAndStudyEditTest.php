<?php

use PHPUnit\Framework\TestCase;

final class AdminAndStudyEditTest extends TestCase
{
    private static string $dbPath;
    private static $serverProc = null;
    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        self::$dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'endgame_admin_test_' . uniqid('', true) . '.sqlite';
        $pdo = new PDO('sqlite:' . self::$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $commonSql = file_get_contents(__DIR__ . '/../../site/update/sql/structure/common.sql');
        $articlesSql = file_get_contents(__DIR__ . '/../../site/update/sql/structure/articles.sql');
        $pdo->exec($commonSql);
        $pdo->exec($articlesSql);

        $pdo->exec("INSERT INTO modules (name, entry_point) VALUES ('articles', 'articles'), ('endgame', 'endgame'), ('statistics', 'statistics')");
        $pdo->exec("INSERT INTO role (id, name) VALUES (1, 'guest'), (2, 'user'), (3, 'admin')");
        $pdo->exec("INSERT INTO capabilities (id, name) VALUES
            (1, 'endgame::create'),
            (2, 'endgame::read'),
            (3, 'endgame::update'),
            (4, 'endgame::delete'),
            (10, 'user::authenticate')
        ");
        $pdo->exec("INSERT INTO role_capabilities (role_id, capability_id, permission) VALUES
            (1, 10, 1),
            (2, 2, 1),
            (3, 1, 1),
            (3, 2, 1),
            (3, 3, 1),
            (3, 4, 1)
        ");

        $adminHash = password_hash('adminpass', PASSWORD_DEFAULT);
        $userHash = password_hash('userpass', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (name, login, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin', 'admin', $adminHash, 3]);
        $stmt->execute(['Regular User', 'user', $userHash, 2]);

        $pdo->exec("INSERT INTO endgame (pid, source, author, date, stipulation, fen, award, whitep, blackp, commentary, cook, piece_pattern, theme)
            VALUES (1, 'Shakhmaty v SSSR', 'Kubbel, Leonid', '1935', 'Draw', '8/8/8/8/8/4K3/4P3/4k3 w - - 0 1', '', 2, 1, '', 0, 'P', 'Stalemate')");
        $pdo->exec("INSERT INTO endgame (pid, source, author, date, stipulation, fen, award, whitep, blackp, commentary, cook, piece_pattern, theme)
            VALUES (2, '64', 'Troitzky, Alexey', '1924', 'White wins', '8/8/8/8/8/8/4K3/4k2R w - - 0 1', '', 2, 1, '', 0, 'R', 'Mate')");
        $pdo->exec("INSERT INTO raw (id, data) VALUES (1, '[Event \"Shakhmaty v SSSR\"]\n[White \"Kubbel, Leonid\"]\n[FEN \"8/8/8/8/8/4K3/4P3/4k3 w - - 0 1\"]\n\n1. Kd3 Kd1 1/2-1/2')");
        $pdo->exec("INSERT INTO raw (id, data) VALUES (2, '[Event \"64\"]\n[White \"Troitzky, Alexey\"]\n[FEN \"8/8/8/8/8/8/4K3/4k2R w - - 0 1\"]\n\n1. Rh8# 1-0')");

        $port = 18600 + random_int(1, 400);
        self::$baseUrl = "http://127.0.0.1:{$port}";

        $siteDir = realpath(__DIR__ . '/../../site');
        $env = array_merge(getenv() ?: [], [
            'APP_DSN' => 'sqlite:' . self::$dbPath,
            'APP_WWW' => self::$baseUrl,
            'APP_DEBUG' => 'false',
        ]);

        $nullDev = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        self::$serverProc = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $siteDir],
            [0 => ['pipe', 'r'], 1 => ['file', $nullDev, 'w'], 2 => ['file', $nullDev, 'w']],
            $pipes,
            $siteDir,
            $env
        );

        for ($i = 0; $i < 30; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($fp) {
                fclose($fp);
                break;
            }
            usleep(100000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProc)) {
            proc_terminate(self::$serverProc);
            proc_close(self::$serverProc);
        }
        if (file_exists(self::$dbPath)) {
            @unlink(self::$dbPath);
        }
    }

    /**
     * @return array{status:int, headers:array<string,string>, body:string, cookie:string}
     */
    private function httpRequest(string $method, string $path, array $postData = [], string $cookie = '', bool $followRedirects = false): array
    {
        $headers = [];
        if ($cookie !== '') {
            $headers[] = "Cookie: {$cookie}";
        }
        $content = '';
        if ($method === 'POST') {
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
            $content = http_build_query($postData);
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $content,
                'follow_location' => $followRedirects ? 1 : 0,
                'ignore_errors' => true,
            ],
        ]);

        $body = file_get_contents(self::$baseUrl . $path, false, $ctx);
        $rawHeaders = $http_response_header ?? [];
        $status = 0;
        $parsedHeaders = [];
        $cookies = [];
        if ($cookie !== '') {
            foreach (explode(';', $cookie) as $part) {
                $kv = explode('=', trim($part), 2);
                if (count($kv) === 2) {
                    $cookies[$kv[0]] = $kv[1];
                }
            }
        }
        foreach ($rawHeaders as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                $status = (int) $m[1];
                continue;
            }
            $parts = explode(':', $h, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $val = trim($parts[1]);
                $parsedHeaders[$key] = $val;
                if ($key === 'set-cookie') {
                    $ck = explode(';', $val, 2)[0];
                    $kv = explode('=', $ck, 2);
                    if (count($kv) === 2) {
                        $cookies[trim($kv[0])] = trim($kv[1]);
                    }
                }
            }
        }
        $cookieOut = implode('; ', array_map(static fn($k, $v) => "{$k}={$v}", array_keys($cookies), $cookies));

        return [
            'status' => $status,
            'headers' => $parsedHeaders,
            'body' => $body === false ? '' : $body,
            'cookie' => $cookieOut,
        ];
    }

    private function extractCsrf(string $html): string
    {
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * @return array{cookie:string, csrf:string}
     */
    private function loginAs(string $login, string $password): array
    {
        $home = $this->httpRequest('GET', '/');
        $csrf = $this->extractCsrf($home['body']);
        $this->assertNotSame('', $csrf, 'CSRF token should be present on homepage');

        $loginResp = $this->httpRequest('POST', '/api/?q=user/login', [
            'csrf_token' => $csrf,
            'login' => $login,
            'password' => $password,
        ], $home['cookie']);

        $this->assertSame(302, $loginResp['status']);
        $afterHome = $this->httpRequest('GET', '/', [], $loginResp['cookie']);
        $newCsrf = $this->extractCsrf($afterHome['body']);
        return ['cookie' => $loginResp['cookie'], 'csrf' => $newCsrf];
    }

    public function testGuestIsBlockedFromManagementAndStudyEdit(): void
    {
        $home = $this->httpRequest('GET', '/');
        $this->assertStringContainsString('<meta name="can-edit-study" content="0">', $home['body']);

        foreach (['/?q=management', '/?q=management/roles', '/?q=management/capabilities', '/?q=endgame/edit/1'] as $route) {
            $resp = $this->httpRequest('GET', $route, [], $home['cookie']);
            $this->assertSame(302, $resp['status'], "Guest should be redirected from {$route}");
        }

        $csrfResp = $this->httpRequest('POST', '/?q=endgame/update/1', ['fen' => '8/8/8/8/8/8/8/8 w - - 0 1'], $home['cookie']);
        $this->assertSame(403, $csrfResp['status']);
    }

    public function testAdminCanManageRolesAndCapabilities(): void
    {
        $admin = $this->loginAs('admin', 'adminpass');

        // Management roles page renders
        $rolesPage = $this->httpRequest('GET', '/?q=management/roles', [], $admin['cookie']);
        $this->assertSame(200, $rolesPage['status']);
        $this->assertStringContainsString('Manage Roles', $rolesPage['body']);
        $this->assertStringContainsString('admin', $rolesPage['body']);
        $this->assertStringContainsString('guest', $rolesPage['body']);
        $csrf = $this->extractCsrf($rolesPage['body']);
        $this->assertNotSame('', $csrf);

        // Create a new role 'editor'
        $createRole = $this->httpRequest('POST', '/?q=role/create', [
            'csrf_token' => $csrf,
            'name' => 'editor',
        ], $admin['cookie']);
        $this->assertSame(302, $createRole['status']);
        $this->assertStringContainsString('?q=role/edit/', $createRole['headers']['location'] ?? '');

        $pdo = new PDO('sqlite:' . self::$dbPath);
        $editorId = (int) $pdo->query("SELECT id FROM role WHERE name = 'editor'")->fetchColumn();
        $this->assertGreaterThan(0, $editorId);

        // Assign endgame::update (3) and endgame::read (2) to editor
        $updateRole = $this->httpRequest('POST', "/?q=role/update/{$editorId}", [
            'csrf_token' => $csrf,
            'name' => 'editor_renamed',
            'caps' => ['2', '3'],
        ], $admin['cookie']);
        $this->assertSame(302, $updateRole['status']);

        $newName = $pdo->query("SELECT name FROM role WHERE id = {$editorId}")->fetchColumn();
        $this->assertSame('editor_renamed', $newName);
        $permUpdate = (int) $pdo->query("SELECT permission FROM role_capabilities WHERE role_id = {$editorId} AND capability_id = 3")->fetchColumn();
        $this->assertSame(1, $permUpdate);

        // Capabilities management: list, add, delete
        $capsPage = $this->httpRequest('GET', '/?q=management/capabilities', [], $admin['cookie']);
        $this->assertSame(200, $capsPage['status']);
        $this->assertStringContainsString('endgame::update', $capsPage['body']);
        $this->assertStringContainsString('editor_renamed', $capsPage['body']);

        $addCap = $this->httpRequest('POST', '/?q=capability/create', [
            'csrf_token' => $csrf,
            'name' => 'endgame::export',
        ], $admin['cookie']);
        $this->assertSame(302, $addCap['status']);
        $exportCapId = (int) $pdo->query("SELECT id FROM capabilities WHERE name = 'endgame::export'")->fetchColumn();
        $this->assertGreaterThan(0, $exportCapId);

        $delCap = $this->httpRequest('POST', "/?q=capability/delete/{$exportCapId}", [
            'csrf_token' => $csrf,
        ], $admin['cookie']);
        $this->assertSame(302, $delCap['status']);
        $this->assertFalse($pdo->query("SELECT id FROM capabilities WHERE id = {$exportCapId}")->fetchColumn());

        // Built-in role 'admin' (3) cannot be deleted
        $delAdmin = $this->httpRequest('POST', '/?q=role/delete/3', [
            'csrf_token' => $csrf,
        ], $admin['cookie']);
        $this->assertSame(302, $delAdmin['status']);
        $this->assertStringContainsString('cannot_delete_builtin', $delAdmin['headers']['location'] ?? '');

        // Custom role can be deleted
        $delEditor = $this->httpRequest('POST', "/?q=role/delete/{$editorId}", [
            'csrf_token' => $csrf,
        ], $admin['cookie']);
        $this->assertSame(302, $delEditor['status']);
        $this->assertFalse($pdo->query("SELECT id FROM role WHERE id = {$editorId}")->fetchColumn());
    }

    public function testAdminAndAuthorizedEditorCanEditAndDeleteStudies(): void
    {
        $admin = $this->loginAs('admin', 'adminpass');

        $home = $this->httpRequest('GET', '/', [], $admin['cookie']);
        $this->assertStringContainsString('<meta name="can-edit-study" content="1">', $home['body']);

        // Open edit form for study 1
        $editPage = $this->httpRequest('GET', '/?q=endgame/edit/1', [], $admin['cookie']);
        $this->assertSame(200, $editPage['status']);
        $this->assertStringContainsString('Edit Study (PID 1)', $editPage['body']);
        $this->assertStringContainsString('Kubbel, Leonid', $editPage['body']);
        $csrf = $this->extractCsrf($editPage['body']);
        $this->assertNotSame('', $csrf);

        // Update study 1 with new FEN, author, cook, commentary, leaving old [FEN "..."] in raw_pgn to test auto-sync
        $newFen = '8/8/8/8/8/8/4K2Q/4k3 w - - 0 1';
        $oldRawPgn = "[Event \"Shakhmaty v SSSR\"]\n[White \"Kubbel, Leonid\"]\n[FEN \"8/8/8/8/8/4K3/4P3/4k3 w - - 0 1\"]\n\n1. Kd3 Kd1 1/2-1/2";
        $updateResp = $this->httpRequest('POST', '/?q=endgame/update/1', [
            'csrf_token' => $csrf,
            'fen' => $newFen,
            'author' => 'Kubbel, Leonid (Fixed)',
            'date' => '1936',
            'source' => 'Shakhmaty v SSSR',
            'award' => '1st Prize',
            'stipulation' => 'White wins',
            'theme' => 'Mate',
            'cook' => '1',
            'commentary' => 'Fixed FEN typo',
            'raw_pgn' => $oldRawPgn,
        ], $admin['cookie']);
        $this->assertSame(302, $updateResp['status']);
        $this->assertStringContainsString('?pid=1', $updateResp['headers']['location'] ?? '');

        $pdo = new PDO('sqlite:' . self::$dbPath);
        $study = $pdo->query("SELECT * FROM endgame WHERE pid = 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($newFen, $study['fen']);
        $this->assertSame('Kubbel, Leonid (Fixed)', $study['author']);
        $this->assertSame('1936', $study['date']);
        $this->assertSame('1st Prize', $study['award']);
        $this->assertSame('White wins', $study['stipulation']);
        $this->assertSame(1, (int) $study['cook']);
        $this->assertSame(2, (int) $study['whitep']);
        $this->assertSame(1, (int) $study['blackp']);
        $this->assertSame('Q', $study['piece_pattern']);

        $rawData = (string) $pdo->query("SELECT data FROM raw WHERE id = 1")->fetchColumn();
        $this->assertStringContainsString($newFen, $rawData);

        // Delete study 2
        $delResp = $this->httpRequest('POST', '/?q=endgame/delete/2', [
            'csrf_token' => $csrf,
        ], $admin['cookie']);
        $this->assertSame(302, $delResp['status']);
        $this->assertFalse($pdo->query("SELECT pid FROM endgame WHERE pid = 2")->fetchColumn());
        $this->assertFalse($pdo->query("SELECT id FROM raw WHERE id = 2")->fetchColumn());

        // Non-admin regular user cannot edit or delete
        $regular = $this->loginAs('user', 'userpass');
        $regularHome = $this->httpRequest('GET', '/', [], $regular['cookie']);
        $this->assertStringContainsString('<meta name="can-edit-study" content="0">', $regularHome['body']);
        $deniedEdit = $this->httpRequest('GET', '/?q=endgame/edit/1', [], $regular['cookie']);
        $this->assertSame(302, $deniedEdit['status']);
    }
}
