<?php

use PHPUnit\Framework\TestCase;

final class PgnTest extends TestCase
{
    public function testParsesCompletePgn(): void
    {
        $raw = <<<PGN
[Event "Shakhmaty v SSSR"]
[Site "USSR"]
[Date "1935.??.??"]
[Round "-"]
[White "Kubbel, Leonid"]
[Black "Draw"]
[Result "1/2-1/2"]
[FEN "8/8/8/8/8/4K3/4P3/4k3 w - - 0 1"]

1. Kd3 Kd1 2. e4 1/2-1/2
PGN;

        $pgn = new Pgn($raw);

        $this->assertSame('Shakhmaty v SSSR', $pgn->getEvent());
        $this->assertSame('USSR', $pgn->getSite());
        $this->assertSame('1935.??.??', $pgn->getDate());
        $this->assertSame('Kubbel, Leonid', $pgn->getWhite());
        $this->assertSame('Draw', $pgn->getBlack());
        $this->assertSame('1/2-1/2', $pgn->getResult());
        $this->assertSame('8/8/8/8/8/4K3/4P3/4k3 w - - 0 1', $pgn->getFen());
        $this->assertSame('1. Kd3 Kd1 2. e4 1/2-1/2', $pgn->getMoves());
    }

    public function testMinimalPgnUsesDefaultsForMissingTags(): void
    {
        $raw = "[White \"Troitzky, Alexey\"]\n\n1. e4 1-0";
        $pgn = new Pgn($raw);

        $this->assertSame('?', $pgn->getEvent());
        $this->assertSame('Troitzky, Alexey', $pgn->getWhite());
        $this->assertSame('8/8/8/8/8/8/8/8 w - - 0 1', $pgn->getFen());
        $this->assertSame('1. e4 1-0', $pgn->getMoves());
    }

    public function testPgnParserSplitsMultipleGames(): void
    {
        $multi = <<<PGN
[Event "Study 1"]
[White "Author A"]
[FEN "8/8/8/8/8/8/4K3/4k2R w - - 0 1"]

1. Rh8# 1-0

[Event "Study 2"]
[White "Author B"]
[FEN "8/8/8/8/8/8/4K3/4k2Q w - - 0 1"]

1. Qe1# 1-0
PGN;

        $parser = new PGNParser($multi);
        $games = $parser->getGames();

        $this->assertCount(2, $games);
        $this->assertSame('Author A', $games[0]->getInfo()[ChessGame::WHITE]);
        $this->assertSame('Author B', $games[1]->getInfo()[ChessGame::WHITE]);
    }
}
