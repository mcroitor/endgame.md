<?php

use mc\sql\crud;
use mc\sql\database;
use mc\sql\query;
use PHPUnit\Framework\TestCase;

final class DatabaseQueryTest extends TestCase
{
    public function testQueryBuilderSelectWhereOrderLimit(): void
    {
        $q = new query([
            query::TYPE => query::SELECT,
            query::TABLE => 'endgame',
            query::FIELDS => ['pid', 'author'],
            query::WHERE => ["author LIKE '%Kubbel%'", 'whitep BETWEEN 2 AND 7'],
            query::ORDER => ['date' => 'ASC', 'pid' => 'ASC'],
            query::LIMIT => ['limit' => 12, 'offset' => 24],
        ]);

        $sql = $q->build();
        $this->assertStringContainsString('SELECT pid, author FROM endgame', $sql);
        $this->assertStringContainsString("WHERE author LIKE '%Kubbel%' AND whitep BETWEEN 2 AND 7", $sql);
        $this->assertStringContainsString('ORDER BY date ASC, pid ASC', $sql);
        $this->assertStringContainsString('LIMIT 12 OFFSET 24', $sql);
    }

    public function testDatabaseCrudAndPreparedStatementsOnInMemorySqlite(): void
    {
        $db = new database('sqlite::memory:');
        $db->query_sql(
            'CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, note TEXT)',
            'Error: ',
            false
        );

        $id = $db->insert('items', ['name' => 'Kubbel', 'note' => null]);
        $this->assertSame('1', $id);

        $rows = $db->exec_prepared('SELECT * FROM items WHERE name LIKE ?', ['%Kub%']);
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['note']);

        $db->update('items', ['note' => null, 'name' => 'Troitzky'], ['id' => 1]);
        $crud = new crud($db, 'items');
        $fetched = $crud->select(1);
        $this->assertSame('Troitzky', $fetched['name']);
        $this->assertNull($fetched['note']);

        $crud->delete(1);
        $this->assertFalse($db->exists('items', ['id' => 1]));
    }
}
