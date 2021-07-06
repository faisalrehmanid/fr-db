<?php

namespace FR\Db;

use Laminas\Db\Adapter\Adapter;
use FR\Db\DbInterface;

/**
 * This class implements common functions from \FR\Db\DbInterface
 * These functions are common in all type of databases. Each specific 
 * database type will must extend this class.
 * 
 * This class dependent on Laminas/Db module
 * 
 * Why abstract class?
 * Don't allow to create an object of this class instead it can be 
 * only inherit from base class.
 */
abstract class DbAbstract implements DbInterface
{
    /**
     * Database configurations
     *
     * @var array
     */
    protected $config = [];

    /**
     * @var object \Laminas\Db\Adapter\Adapter
     */
    protected $adapter;

    /**
     * @param array $config Database connection configuration
     * @see \FR\Db\DbFactory::init() docblock comments for more details
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Set Laminas\Db\Adapter\Adapter
        $this->adapter = new Adapter($config);

        // Connect database to check connection configuration
        $this->getAdapter()->getDriver()->getConnection()->connect();
        // Disconnect after connection has been checked
        $this->disconnect();
    }

    /**
     * Get database configurations
     * 
     * @param string $key Optional config key
     *
     * @return array | string If key is null then array will return
     *                        If key is given then string will return
     */
    public function getConfig($key = null)
    {
        if (empty($key))
            return $this->config;

        return @$this->config[$key];
    }

    /**
     * Get \Laminas\Db\Adapter\Adapter object
     *
     * @return object \Laminas\Db\Adapter\Adapter
     */
    protected function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Identify query type
     *
     * @param string $query SQL query
     * @return string 'SELECT', 'INSERT', 'UPDATE', 'DELETE'
     */
    protected function queryType($query)
    {
        $query = trim($query);

        if (strpos(strtoupper($query), 'SELECT') === 0)
            return 'SELECT'; // For SELECT query use all \FR\Db\DbInterface::fetch*()
        elseif (strpos(strtoupper($query), 'INSERT') === 0)
            return 'INSERT'; // For INSERT query use \FR\Db\DbInterface::insert()
        elseif (strpos(strtoupper($query), 'UPDATE') === 0)
            return 'UPDATE'; // For UPDATE query use \FR\Db\DbInterface::update()
        elseif (strpos(strtoupper($query), 'DELETE') === 0)
            return 'DELETE'; // For DELETE query use \FR\Db\DbInterface::delete()

        return 'OTHERS';    // For other query use \FR\Db\DbInterface::query()
    }

    /**
     * Just for debugging. Don't use this query to execute
     *
     * @param string $query SQL query
     * @param array $values Query parameters
     * @return string Exact SQL query with replaced query parameters
     */
    protected function preparedQuery($query, array $values = [])
    {
        if (empty($values)) // Nothing to replace in query
            return $query;

        // Replace $values keys in SQL query
        $keys = array_keys($values);
        foreach ($keys as $key) {

            // Do not quote value if its integer
            if (is_int($values[$key]))
                $value = $values[$key];
            else
                $value = $this->getAdapter()->getPlatform()->quoteValue($values[$key]);

            $query = str_replace($key, $value, $query);
        }

        return $query;
    }

    /**
     * Result array keys must be of same format
     *
     * @param array $data Two dimensional array
     * @return array Two dimensional array
     */
    protected function formatKeys(array $data = [])
    {
        if (empty($data))
            return [];

        // Always return lowercase keys in $data array
        $result = array_map(function ($data) {
            $single = [];
            foreach ($data as $k => $v) {
                $column = trim(strtolower($k));
                $single[$column] = $v;
            }

            return $single;
        }, $data);

        return $result;
    }

    /**
     * Validate query parameters each key must be like :key
     *
     * @param array $values Query parameters
     * @throws \Exception When key not formated as :key
     * @return void
     */
    protected function validateValues(array $values = [])
    {
        if (!empty($values)) {
            foreach ($values as $key => $value) {
                // $key must not have space
                if (strpos($key, ' ') !== false) {
                    throw new \Exception('Invalid binding key. Space found in `' . $key . '`');
                }

                // $key must start with :
                if (strpos($key, ':')  !== 0) {
                    throw new \Exception('Invalid binding key found `' . $key . '` it must be like `:' . $key . '`');
                }
            }
        }
    }

    /**
     * Pretty print array/object for debuging
     *
     * @param array|object $params Array/object to be print
     * @param boolean $exit Exit after print
     * @return void
     */
    protected function pr($params, $exit = true)
    {
        echo "<pre>";
        print_r($params);
        echo "</pre>";

        if ($exit == true) {
            exit();
        }
    }

    /**
     * Debugging query with query parameters
     *
     * @param string $query SQL query
     * @param array $values Query parameters
     * @return void
     */
    protected function debug($query, $values = [])
    {
        $array = [];
        $array['prepared_query'] = $this->preparedQuery($query, $values);
        $array['query'] = $query;
        $array['values'] = $values;
        $this->pr($array); // Print $array and exit
    }

    /**
     * Get database platform name
     *
     * @return string Always return lowercase string 'mysql', 'oracle' etc
     */
    public function getDbPlatformName()
    {
        return strtolower($this->getAdapter()->getDriver()->getDatabasePlatformName());
    }

    /**
     * Disconnect database connection
     *
     * @return void
     */
    public function disconnect()
    {
        $this->getAdapter()->getDriver()->getConnection()->disconnect();
    }

    /**
     * Create 32 chars random string UUID (Universal Unique Identifier)
     *
     * @return string Lower case UUID HEX string, always return 32 chars string in length
     */
    public function createUuid()
    {
        // Random byte length 16, will create 32 chars HEX string
        $length = 16;

        if (function_exists('random_bytes')) {
            $random = random_bytes($length);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $random = openssl_random_pseudo_bytes($length);
        }

        if ($random !== false && strlen($random) === $length) {
            return strtolower(bin2hex($random));
        }

        $uuid = '';
        $characters = '0123456789abcdef';
        for ($i = 0; $i < ($length * 2); $i++) {
            $uuid .= $characters[rand(0, strlen($characters) - 1)];
        }

        return strtolower($uuid);
    }

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
    public function fetchRow($query, array $values = [], $disconnect = true, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'SELECT') // Check query type must be SELECT query
            throw new \Exception('fetchRow() function can only used for `SELECT` query');

        // Validate query parameters each key must be like :key
        if (!empty($values))
            $this->validateValues($values);

        if ($debug) {
            $this->debug($query, $values);
        }

        $resultSet = $this->getAdapter()->query($query, $values);
        if ($disconnect) // Disconnect database connection after query execution
            $this->disconnect();

        $data = $resultSet->toArray();
        if (empty($data)) // Always return an array
            return [];

        $result = $this->formatKeys($data);

        return $result[0];
    }

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
    public function fetchRows($query, array $values = [], $disconnect = true, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'SELECT') // Check query type must be SELECT query
            throw new \Exception('fetchRows() function can only used for `SELECT` query');

        // Validate query parameters each key must be like :key
        if (!empty($values))
            $this->validateValues($values);

        if ($debug) {
            $this->debug($query, $values);
        }

        $resultSet = $this->getAdapter()->query($query, $values);
        if ($disconnect) // Disconnect database connection after query execution
            $this->disconnect();

        $data = $resultSet->toArray();
        if (empty($data)) // Always return an array
            return [];

        $result = $this->formatKeys($data);

        return $result;
    }

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
    public function fetchColumn($query, array $values = [], $disconnect = true, $debug = false)
    {
        $data = $this->fetchRows($query, $values, $disconnect, $debug);

        if (empty($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $k => $v) {
            $v = array_values($v);
            $result[] = $v[0];
        }

        return $result;
    }

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
    public function fetchKey($key, $query, array $values = [], $disconnect = true, $debug = false)
    {
        $row = $this->fetchRow($query, $values, $disconnect, $debug);

        if (isset($row[$key])) {
            return $row[$key];
        }

        return '';
    }

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
    public function update($query, array $values = [], $disconnect = true, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'UPDATE') // Check query type must be UPDATE query
            throw new \Exception('update() function can only used for `UPDATE` query');

        // Validate query parameters each key must be like :key
        if (!empty($values))
            $this->validateValues($values);

        if ($debug) {
            $this->debug($query, $values);
        }

        $result = $this->getAdapter()->query($query, $values);
        if ($disconnect) // Disconnect database connection after query execution
            $this->disconnect();

        return $result->getAffectedRows();
    }

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
    public function delete($query, array $values = [], $disconnect = true, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'DELETE') // Check query type must be DELETE query
            throw new \Exception('delete() function can only used for `DELETE` query');

        // Validate query parameters each key must be like :key
        if (!empty($values))
            $this->validateValues($values);

        if ($debug) {
            $this->debug($query, $values);
        }

        $result = $this->getAdapter()->query($query, $values);
        if ($disconnect) // Disconnect database connection after query execution
            $this->disconnect();

        return $result->getAffectedRows();
    }

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
    public function query($query, array $values = [], $disconnect = true, $debug = false)
    {
        $type = $this->queryType($query);

        if ($type === 'SELECT')
            throw new \Exception('For `SELECT` query use any \FR\Db\DbInterface::fetch*()');
        if ($type === 'INSERT')
            throw new \Exception('For `INSERT` query use \FR\Db\DbInterface::insert()');
        if ($type === 'UPDATE')
            throw new \Exception('For `UPDATE` query use \FR\Db\DbInterface::update()');
        if ($type === 'DELETE')
            throw new \Exception('For `DELETE` query use \FR\Db\DbInterface::delete()');

        // Validate query parameters each key must be like :key
        if (!empty($values))
            $this->validateValues($values);

        if ($debug) {
            $this->debug($query, $values);
        }

        $result = $this->getAdapter()->query($query, $values);
        if ($disconnect) // Disconnect database connection after query execution
            $this->disconnect();

        return $result;
    }

    /**
     * Disconnect database connection after script execution
     * Its not guaranteed that __destruct will be called for sure. For example:
     * __destruct will not be called when script throw \Exception and break script execution
     * 
     * Don't trust on destructor for disconnect. This is just for extra safety.
     * Use $disconnect parameter provided in each query function
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
