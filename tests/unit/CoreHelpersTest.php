<?php

use mc\router;
use mc\template;
use PHPUnit\Framework\TestCase;

final class CoreHelpersTest extends TestCase
{
    public function testTranslitItConvertsCyrillicToLatin(): void
    {
        $this->assertSame('Kyubbel', translitIt('Кюббель'));
        $this->assertSame('Troitskiy', translitIt('Троицкий'));
        $this->assertSame('SHahmaty', translitIt('Шахматы'));
        $this->assertSame('Kubbel', translitIt('Kubbel'));
    }

    public function testTemplateFillsWithDefaultAndCustomDelimiters(): void
    {
        $tpl = new template('Hello, <!-- name -->! Role: <!-- role -->.', [
            'prefix' => '<!-- ',
            'suffix' => ' -->',
        ]);

        $filled = $tpl->fill(['name' => 'Admin', 'role' => 'admin']);
        $this->assertSame('Hello, Admin! Role: admin.', $filled->value());

        // Original template must remain immutable
        $this->assertSame('Hello, <!-- name -->! Role: <!-- role -->.', $tpl->value());
    }

    public function testTemplateWithEmptyModifiersReplacesDirectly(): void
    {
        $tpl = new template('a + b = c');
        $this->assertSame('1 + 2 = 3', $tpl->fill(['a' => '1', 'b' => '2', 'c' => '3'])->value());
    }

    public function testRouterRegistersRoutesAndRunsDefaultRoute(): void
    {
        router::init([
            'study/view' => static function (array $params): string {
                return 'pid=' . ($params[0] ?? 'none');
            },
        ]);

        $routes = router::getRoutes();
        $this->assertContains('study/view', $routes);
        $this->assertSame('', router::run());
    }

    public function testChessGameParsesHeadersAndRawPgn(): void
    {
        $raw = <<<PGN
[Event "64"]
[White "Rinck, Henri"]
[Date "1922.??.??"]
[Result "1-0"]
[FEN "8/8/8/8/8/8/4K3/4k2R w - - 0 1"]

1. Rh8# 1-0
PGN;

        $game = new ChessGame($raw);
        $info = $game->getInfo();

        $this->assertSame('64', $info[ChessGame::EVENT]);
        $this->assertSame('Rinck, Henri', $info[ChessGame::WHITE]);
        $this->assertSame('1922.??.??', $info[ChessGame::DATE]);
        $this->assertSame('1-0', $info[ChessGame::RESULT]);
        $this->assertSame('8/8/8/8/8/8/4K3/4k2R w - - 0 1', $info[ChessGame::FEN]);
        $this->assertStringContainsString('1. Rh8# 1-0', $game->getRaw());
    }
}
