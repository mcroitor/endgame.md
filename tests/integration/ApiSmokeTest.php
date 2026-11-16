<?php

use PHPUnit\Framework\TestCase;

final class ApiSmokeTest extends TestCase
{
    private static string $dbPath;
    private static $serverProc = null;
    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        self::$dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'endgame_test_' . uniqid('', true) . '.sqlite';
        $pdo = new PDO('sqlite:' . self::$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $commonSql = file_get_contents(__DIR__ . '/../../site/update/sql/structure/common.sql');
        $articlesSql = file_get_contents(__DIR__ . '/../../site/update/sql/structure/articles.sql');

        $pdo->exec($commonSql);
        $pdo->exec($articlesSql);
        $pdo->exec("INSERT INTO modules (name, entry_point) VALUES ('articles', 'articles'), ('endgame', 'endgame')");

        $pdo->exec("INSERT INTO endgame (pid, source, author, date, stipulation, fen, award, whitep, blackp, commentary, cook, piece_pattern, theme)
            VALUES (1, 'Shakhmaty v SSSR', 'Kubbel, Leonid', '1935', 'Draw', '8/8/8/8/8/4K3/4P3/4k3 w - - 0 1', '', 2, 1, '', 0, 'KPk', 'Stalemate')");
        $pdo->exec("INSERT INTO raw (id, data) VALUES (1, '[Event \"Shakhmaty v SSSR\"]\n[White \"Kubbel, Leonid\"]\n[FEN \"8/8/8/8/8/4K3/4P3/4k3 w - - 0 1\"]\n\n1. Kd3 Kd1 1/2-1/2')");

        $port = 18080 + random_int(1, 500);
        self::$baseUrl = "http://127.0.0.1:{$port}";

        $siteDir = realpath(__DIR__ . '/../../site');
        $env = array_merge(getenv() ?: [], [
            'APP_DSN' => 'sqlite:' . self::$dbPath,
            'APP_WWW' => self::$baseUrl,
            'APP_DEBUG' => 'false',
        ]);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        self::$serverProc = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $siteDir],
            $descriptors,
            $pipes,
            $siteDir,
            $env
        );

        // Wait up to 3 seconds for built-in server to listen
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

    public function testApiDataFiltersStudies(): void
    {
        $payload = json_encode([
            'page' => 0,
            'author' => 'Kubbel',
            'wmin' => 1,
            'wmax' => 7,
            'bmin' => 1,
            'bmax' => 7,
            'stipulation' => '-',
            'theme' => '-',
            'from_date' => 1900,
            'to_date' => 2050,
            'cook' => 'all',
            'sort' => 'pid_asc',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
            ],
        ]);

        $body = file_get_contents(self::$baseUrl . '/api/?q=data', false, $ctx);
        $this->assertNotFalse($body);

        $json = json_decode($body, true);
        $this->assertSame(1, $json['stat']);
        $this->assertCount(1, $json['html']);
        $this->assertSame('Kubbel, Leonid', $json['html'][0]['#author#']);
    }

    public function testApiPgnReturnsStudyData(): void
    {
        $body = file_get_contents(self::$baseUrl . '/api/?q=pgn/1');
        $this->assertNotFalse($body);

        $json = json_decode($body, true);
        $this->assertStringContainsString('Kubbel, Leonid', $json['data']);
    }

    public function testDirectPgnDownloadReturnsPlainText(): void
    {
        $body = file_get_contents(self::$baseUrl . '/?q=pgn/1');
        $this->assertNotFalse($body);
        $this->assertStringContainsString('1. Kd3 Kd1 1/2-1/2', $body);
    }

    public function testDiagramEndpointReturnsPngAndCacheHeaders(): void
    {
        $fen = urlencode('8/8/8/8/8/4K3/4P3/4k3 w - - 0 1');
        $body = file_get_contents(self::$baseUrl . "/modules/diagram/?fen={$fen}&size=24");
        $this->assertNotFalse($body);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $body);
    }
}
