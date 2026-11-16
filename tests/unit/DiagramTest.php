<?php

use PHPUnit\Framework\TestCase;

final class DiagramTest extends TestCase
{
    public function testValidFenProducesExpectedBoard(): void
    {
        $board = new Board('8/8/8/8/8/8/4K3/4k2R w - - 0 1');
        $grid = $board->board;

        $this->assertCount(8, $grid);
        $this->assertSame('K', $grid[6][4]);
        $this->assertSame('k', $grid[7][4]);
        $this->assertSame('R', $grid[7][7]);
    }

    public function testInvalidFenWithoutEightRanksFallsBackToEmptyBoard(): void
    {
        $board = new Board('88888888');
        $grid = $board->board;

        $this->assertCount(8, $grid);
        foreach ($grid as $rank) {
            $this->assertSame(array_fill(0, 8, ' '), $rank);
        }
    }

    public function testInvalidPieceCharacterFallsBackToEmptyBoard(): void
    {
        $board = new Board('8/8/8/8/8/8/4Z3/8');
        $grid = $board->board;

        $this->assertCount(8, $grid);
        $this->assertSame(' ', $grid[6][4]);
    }

    public function testDiagramRendersGdImageWithInvalidColorFallback(): void
    {
        $diagram = new Diagram('8/8/8/8/8/8/4K3/4k2R w - - 0 1', [
            'size' => 24,
            'color' => 'nonexistent_color',
        ]);

        $image = $diagram->toImage();
        $this->assertInstanceOf(GdImage::class, $image);
    }
}
