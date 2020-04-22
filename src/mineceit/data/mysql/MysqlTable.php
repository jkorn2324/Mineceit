<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-22
 * Time: 00:04
 */

declare(strict_types=1);

namespace mineceit\data\mysql;


class MysqlTable
{


    /** @var array */
    private $columns = [];

    /** @var array */
    private $columnTypes = [];

    /** @var string */
    private $name;

    public const COLUMN_TYPE_INT = "TYPE_INT";

    public const COLUMN_TYPE_STRING = "TYPE_STRING";

    public const COLUMN_TYPE_BOOL = "TYPE_BOOL";

    public const COLUMN_TYPE_FLOAT = "TYPE_FLOAT";

    public function __construct(string $tableName)
    {
        $this->name = $tableName;
    }

    /**
     * @param bool $autoIncrement
     * @param bool $unsigned
     *
     * Adds an Id to the columns.
     */
    public function putId(bool $autoIncrement = true, bool $unsigned = true) : void {
        $string = "INT(8) " . ($unsigned ? "UNSIGNED" : "");
        $this->columns["id"] = $string . (($autoIncrement) ? ' AUTO_INCREMENT' : '') . ' PRIMARY KEY';
        $this->columnTypes["id"] = self::COLUMN_TYPE_INT;
    }

    /**
     * @param string $columnName
     * @param bool|null $defaultValue
     *
     * Adds a boolean value to columns.
     */
    public function putBoolean(string $columnName, bool $defaultValue = null) : void {
        $string = "BOOL";
        if($defaultValue !== null) {
            $string .= " DEFAULT " . intval($defaultValue);
        }
        $this->columns[$columnName] = $string;
        $this->columnTypes[$columnName] = self::COLUMN_TYPE_BOOL;
    }


    /**
     * @param string $columnName
     * @param int|null $defaultValue
     * @param int $maxPlaces
     *
     * Adds a integer value to the columns.
     */
    public function putInt(string $columnName, int $defaultValue = null, int $maxPlaces = 6) : void {

        $string = "INT({$maxPlaces})";

        if($defaultValue !== null) {
            $string .= " DEFAULT " . $defaultValue;
        }
        $this->columns[$columnName] = $string;
        $this->columnTypes[$columnName] = self::COLUMN_TYPE_INT;
    }


    /**
     * @param string $columnName
     * @param int $maxCharacters
     * @param string|null $defaultValue
     *
     * Adds a string to the columns.
     */
    public function putString(string $columnName, int $maxCharacters = 60, string $defaultValue = null) : void {

        $string = "VARCHAR({$maxCharacters})";
        if($defaultValue !== null) {
            $string .= " DEFAULT '{$defaultValue}'";
        } else {
            $string .= " NOT NULL";
        }
        $this->columns[$columnName] = $string;
        $this->columnTypes[$columnName] = self::COLUMN_TYPE_STRING;
    }


    /**
     * @param bool $notExists
     * @return string
     *
     * Creates the string that creates table.
     */
    public function queryCreateTable(bool $notExists = true) : string {

        $keys = array_keys($this->columns);
        $array = [];
        foreach($keys as $key) {
            $value = $this->columns[$key];
            $array[] = "$key $value";
        }

        $initial = "CREATE TABLE " . ($notExists ? "IF NOT EXISTS " : "");

        return "$initial $this->name (" . implode(", ", $array) . ")";
    }


    /**
     * @param bool $notExists
     * @return array
     *
     * Creates the string that alters a table.
     */
    public function queryAlterTables(bool $notExists = true) : array {

        $keys = array_keys($this->columns);

        $array = [];

        foreach($keys as $key) {

            $value = $this->columns[$key];
            $str = "$key $value";

            $other = ($notExists) ? "IF NOT EXISTS " : "";

            $command = "ALTER TABLE {$this->name} ADD COLUMN $other{$str}";

            $array[] = $command;
        }

        return $array;
    }

    /**
     * @return array
     *
     * Returns the columns with their corresponding types.
     */
    public function getColumns() : array {
        return $this->columnTypes;
    }

    /**
     * @return string
     *
     * Returns the name of the table.
     */
    public function getName() : string {
        return $this->name;
    }
}