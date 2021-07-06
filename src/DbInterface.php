<?php

namespace FR\Db;

/**
 * Every database type must implements this DbInterface
 */
interface DbInterface
{
    /**
     * Get database configurations
     * 
     * @param string $key Optional config key
     *
     * @return array | string If key is null then array will return
     *                        If key is given then string will return
     */
    public function getConfig($key = null);

    /**
     * Get database platform name
     *
     * @return string Always return lowercase string
     */
    public function getDbPlatformName();

    /**
     * Disconnect database connection
     *
     * @return void
     */
    public function disconnect();

    /**
     * Create 32 chars random string UUID (Universal Unique Identifier)
     *
     * @return string Lower case UUID HEX string, always return 32 chars string in length
     */
    public function createUuid();

    /**
     * Fetch single row using `SELECT` query
     *
     * @param string $query `SELECT` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     * @return array Always return an array
     */
    public function fetchRow($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch multiple rows using `SELECT` query
     *
     * @param string $query `SELECT` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     * @return array Always return an array
     */
    public function fetchRows($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch single column using `SELECT` query
     *
     * @param string $query `SELECT` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     * @return array Always return an array
     */
    public function fetchColumn($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch string value for given key from single row
     *
     * @param string $key Name of key to get value
     * @param string $query `SELECT` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     * @return string Value string
     */
    public function fetchKey($key, $query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute `UPDATE` query
     *
     * @param string $query `UPDATE` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception update() function can only used for `UPDATE` query
     * @return int Number of affected rows
     */
    public function update($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute `DELETE` query
     *
     * @param string $query `DELETE` SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception delete() function can only used for `DELETE` query
     * @return int Number of affected rows
     */
    public function delete($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute SQL query other than `SELECT`, `INSERT`, `UPDATE`, `DELETE`
     * like calling stored procedure
     *
     * @param string $query SQL query
     * @param array $values Query parameters
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception For `SELECT` query use any \FR\Db\DbInterface::fetch*()
     * @throws \Exception For `INSERT` query use \FR\Db\DbInterface::insert()
     * @throws \Exception For `UPDATE` query use \FR\Db\DbInterface::update()
     * @throws \Exception For `DELETE` query use \FR\Db\DbInterface::delete()
     * @return object $result
     */
    public function query($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To import SQL Script
     *
     * @param string SQL script to execute
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @return void Nothing returns
     */
    public function importSQL($query, $disconnect = true);

    /**
     * Get collection of commonly used SQL expressions
     * Each database type will implement accordingly
     *
     * @return object \FR\Db\ExpressionInterface
     */
    public function getExpression();

    /**
     * Fetch rows chunk for pagination using `SELECT` query
     *
     * @param string $query `SELECT` SQL query
     * @param array $values Query parameters
     * @param int $page_number Page number starts from 1. Default is 1
     * @param int $records_per_page Records per page. Default is 50 records per page 
     * @param int $start Start index for pagination. Default is 0
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     * @return array Always return an array
     */
    public function fetchChunk(
        $query,
        array $values = [],
        $page_number = 1,
        $records_per_page = 50,
        $start = 0,
        $disconnect = true,
        $debug = false
    );

    /**
     * Insert data into database
     * 
     * @param string|array $table When string it must be table name
     *                            When array it must be like: 
     * ['table' => 'table-name', 'sequence' => 'sequence-name', 'column' => 'column-name']
     * @param array $values Data to be inserted
     * @param bool $disconnect Disconnect database connection after query execution. Default is true
     * @param boolean $debug Don't execute query. Just print it for debugging. Default is false
     * @throws \Exception $values could not be empty for insert() function
     * @return int|false Last generated value or false
     */
    public function insert(
        $table,
        array $values,
        $disconnect = true,
        $debug = false
    );
}
