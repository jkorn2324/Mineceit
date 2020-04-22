<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-21
 * Time: 23:59
 */

declare(strict_types=1);

namespace mineceit\data\mysql;

use mineceit\MineceitCore;

class MysqlStream
{


    /** @var array */
    private $stream;

    /** @var string */
    public $username;

    /** @var string -> The ip of the db */
    public $host;

    /** @var string */
    public $password;

    /** @var int */
    public $port;

    /** @var string */
    public $database;

    /** @var array|MysqlTable[] */
    private $tables = [];

    public function __construct()
    {
        $data = MineceitCore::getMysqlData();
        $this->username = strval($data['username']);
        $this->host = strval($data['ip']);
        $this->password = strval($data['password']);
        $this->port = intval($data['port']);
        $this->database = strval($data['database']);
        $this->stream = [];
    }

    /**
     * @param MysqlTable $table
     *
     * Adds to the stream a query to create a table.
     */
    public function createTable(MysqlTable $table) : void {
        $this->stream[] = $table->queryCreateTable();
        $this->tables[$table->getName()] = $table;
    }

    /**
     * @param MysqlTable $table
     *
     * Adds to the stream a query to alter an existing table.
     */
    public function alterTable(MysqlTable $table) : void {
        $this->tables[$table->getName()] = $table;
        $this->stream[] = $table->queryAlterTables();
    }

    /**
     * @param MysqlRow $row
     * @param array $statements
     * @param string $statementTable
     *
     * Adds to the stream a query to create a row.
     */
    public function insertRow(MysqlRow $row, $statements = [], string $statementTable = '') : void {

        $len = strlen($statementTable);

        if($len > 0 and !isset($this->tables[$statementTable])) return;

        $this->stream[] = $row->queryInsert($statements, $statementTable);
    }


    /**
     * @param MysqlRow $row
     *
     * Inserts a row, if it already exists it updates it.
     */
    public function insertNUpdate(MysqlRow $row) : void {

        $this->stream[] = $row->queryInsertNUpdate();
    }

    /**
     * @param MysqlRow $row
     * @param array $statements
     *
     * Adds to the stream a query to update a row.
     */
    public function updateRow(MysqlRow $row, $statements = []) : void {

        $this->stream[] = $row->queryUpdate($statements);
    }

    /**
     * @param string $table
     * @param array $statement
     *
     * Removes a row in a table.
     */
    public function removeRows(string $table, $statement = []) : void {

        $stream = "DELETE FROM {$table}";

        $len = count($statement);

        if($len > 0) {

            $implode = implode(" AND ", $statement);

            $stream .= " WHERE {$implode}";
        }

        $this->stream[] = $stream;
    }


    /**
     * @param array $tables
     * @param array $statements
     * @param array $values
     *
     * Adds the select query string.
     */
    public function selectTables($tables = [], $statements = [], $values = []) : void {

        $tables = implode(', ', $tables);

        $len = count($values);

        $value = "*";

        if($len > 0)
            $value = implode(", ", $values);

        $query = "SELECT {$value} FROM {$tables}";

        $length = count($statements);

        if($length > 0) {

            $statement = implode(" AND ", $statements);

            $query .= " WHERE $statement";
        }

        $this->stream[] = $query;
    }


    /**
     * @param array $tables
     * @param array $values
     *
     * Selects the tables in order by descending.
     */
    public function selectTablesInOrder($tables = [], $values = []) : void {

        $value = "*";
        $len = count($values);

        $keys = array_keys($values);

        if($len > 0) {
            $value = implode(', ', $keys);
        }

        $table = implode(', ', $tables);

        $orders = [];
        foreach($keys as $key) {
            $v = $values[$key];
            if($v) $orders[] = "{$key} DESC";
        }

        $len = count($orders);
        $order = "";
        if($len > 0) {
            $order = " ORDER BY " . implode(", ", $orders);
        }


        $query = "SELECT {$value} FROM {$table}{$order}";

        $this->stream[] = $query;
    }


    /**
     * @param string $table
     * @param array $rows
     * @param bool $inOrder
     *
     * Adds to the query the average rows.
     */
    public function selectAverageRows(string $table, array $rows, bool $inOrder = true) : void {

        $averageRows = [];
        $otherRows = [];

        $keys = array_keys($rows);

        foreach($keys as $key) {
            $row = $rows[$key];
            $string = "{$table}.{$key}";
            if($row)
                $averageRows[] = $string;
            else $otherRows[] = $string;
        }

        $length = strval(count($averageRows));

        $avgRows = "(" . implode(" + ", $averageRows) . ") / {$length}";

        $otherRowLength = count($otherRows);

        $otherRowStr = "";

        if($otherRowLength > 0)
            $otherRowStr = ', ' . implode(", ", $otherRows);

        $query = "SELECT @AVERAGE := {$avgRows}{$otherRowStr} FROM {$table}";

        $inOrderString = ($inOrder) ? " ORDER BY @AVERAGE DESC" : "";

        $this->stream[] = $query . $inOrderString;
    }

    /**
     * @param string $table
     * @param string $dividend
     * @param string $divisor
     * @param bool $inOrder
     *
     * Adds to the query the division of two columns in the same table.
     */
    public function selectDividedColumnsOfRows(string $table, string $dividend, string $divisor, bool $inOrder = true) : void {

        // SELECT @RESULT = $dividend / $divisor FROM $table ORDER BY @RESULT DESC

        $result = "SELECT @RESULT := ROUND({$dividend}/{$divisor}) FROM {$table}";

        if($inOrder) {
            $result .= " ORDER BY @RESULT DESC";
        }

        $this->stream[] = $result;
    }

    /**
     * @param string $table
     * @return array
     *
     * Gets the columns of a particular table.
     */
    public function getColumnsOf(string $table) : array {
        if(isset($this->tables[$table]))
            return $this->tables[$table]->getColumns();
        return [];
    }

    /**
     * @return array
     *
     * Returns the stream.
     */
    public function getStream() : array {
        return $this->stream;
    }
}