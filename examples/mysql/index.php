<?php
// Example code
include_once('./../../vendor/autoload.php');

/**
 * Pretty print array/object for debuging
 *
 * @param array|object $params Array/object to be print
 * @param boolean $exit Exit after print
 * @return void
 */
if (!function_exists('\pr')) {
    function pr($params, $exit = true)
    {
        echo "<pre>";
        print_r($params);
        echo "</pre>";

        if ($exit == true) {
            exit();
        }
    }
}

// PDO MySQL connection configuration
$config =  array(
    'driver' => 'pdo_mysql',
    'hostname' => 'localhost',
    'port' => '3306',
    'username' => 'root',
    'password' => '',
    'database' => 'test_fr_db_mysql',
    'charset' => 'utf8mb4'
);

// Create $db object and connect to database
$db = new \FR\Db\DbFactory();
try {
    $db = $db->init($config);
} catch (\Exception $e) {
    echo $e->getMessage();
    exit();
}
echo 'Database connected successfully. <br>';

// Get database configuration
$result = $db->getConfig();
echo ' Get database configuration <br>';
pr($result, false);

// Query other than `SELECT`, `INSERT`, `UPDATE`, `DELETE`
// Drop table if already exists
$query = ' DROP TABLE IF EXISTS  `fr_db_sample_table`; ';
$db->query($query);
echo '`fr_db_sample_table` Table has been droped. <br>';

/*
// Query function can also be used to call stored procedure
$query = " CALL STORED_PROCEDURE_NAME(:PARAM_1, :PARAM_2) ";
$values = [
    ':PARAM_1' => 'value-1',
    ':PARAM_2' => 'value-2',
];
$db->query($query, $values);
*/

// Import SQL Script
$query = file_get_contents('./backup.sql');
$db->importSQL($query);
echo 'Table has been created. SQL script `./backup.sql` imported successfully. <br>';

// How to check database instance
if ($db instanceof \FR\Db\DbInterface)
    echo 'How to check database instance type: \FR\Db\DbInterface <br>';

// Get database platform name
$platform_name = $db->getDbPlatformName();
echo 'Database platform name is: ' . $platform_name . ' <br>';

// Insert data into database
$values = [
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31c'),
        'username' => 'user1',
        'select' => 'Active',
        'date' => $db->getExpression()->setDate('1987-01-01')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31d'),
        'username' => 'user2',
        'select' => 'Active',
        'date' => $db->getExpression()->setDate('2020-02-29 17:50:25')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31e'),
        'username' => 'user3',
        'select' => 'Inactive',
        'date' => $db->getExpression()->setDate('2020-02-29')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31f'),
        'username' => 'user4',
        'select' => 'Active',
        'date' => $db->getExpression()->setDate('1987-12-31 05:30:10')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad311'),
        'username' => 'user5',
        'select' => 'Inactive',
        'date' => $db->getExpression()->setDate('1986-01-01')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad312'),
        'username' => 'user6',
        'select' => 'Inactive',
        'date' => $db->getExpression()->setDate('1987-12-01')
    ],
    [
        'uuid' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad313'),
        'username' => 'user7',
        'select' => 'Active',
        'date' => $db->getExpression()->setDate('1987-01-31')
    ],
];
$result = $db->insert('fr_db_sample_table', $values);
echo 'Sample data inserted in fr_db_sample_table table. <br>';
if ($result !== false)
    echo 'Last Generated Value: ' . $result . '<br>';

// Fetch single row for select query
$query = ' SELECT  user_id,
                   ' . $db->getExpression()->getUuid('uuid') . ' uuid, 
                   username,
                   `select`,
                   ' . $db->getExpression()->getDate('fr_db_sample_table.date') . ' user_date
            FROM fr_db_sample_table 
            WHERE ' . $db->getExpression()->getUuid('uuid') . ' = :uuid  LIMIT 1 ';
$values = array(':uuid' => 'c1dd8ce513b11e981f288b11109ad31d');
$result = $db->fetchRow($query, $values);
echo ' Fetch single row <br>';
pr($result, false);

// Fetch multiple rows for select query
$query = ' SELECT user_id,
                  ' . $db->getExpression()->getUuid('uuid') . ' uuid, 
                  username,
                  `select`,
                  ' . $db->getExpression()->getDate('fr_db_sample_table.date') . ' user_date
            FROM  fr_db_sample_table 
            WHERE `select` = :select ';
$values = array(':select' => 'Active');
$result = $db->fetchRows($query, $values);
echo ' Fetch multiple rows <br>';
pr($result, false);

// Fetch rows chunk for pagination using `SELECT` query
$query = ' SELECT * FROM fr_db_sample_table ORDER BY user_id ASC ';
$values = array();
$page_number = 2;
$records_per_page = 5;
$result = $db->fetchChunk($query, $values, $page_number, $records_per_page);
echo ' Fetch rows chunk for pagination <br>';
pr($result, false);

// Fetch single column for select query
$query = ' SELECT username FROM fr_db_sample_table ';
$result = $db->fetchColumn($query);
echo ' Fetch single column <br> ';
pr($result, false);

// Fetch string value for given key from single row
$query = ' SELECT username FROM  fr_db_sample_table 
            WHERE username = :username ';
$values = array(':username' => 'user5');
$result = $db->fetchKey('username', $query, $values);
echo ' Fetch string value: ' . $result . ' <br> ';

// Fetch using IN Clause
$array = ['user3', 'user7'];
//$array = [];
$in = $db->getExpression()->in($array);

$query = ' SELECT user_id, username FROM fr_db_sample_table ';
if (!empty($in->getFragment()))
    $query = ' SELECT user_id, username FROM fr_db_sample_table 
                WHERE username ' . $in->getFragment();

$values = array_merge([], $in->getValues());
$result = $db->fetchRows($query, $values);
echo ' Fetch using IN Clause <br> ';
pr($result, false);

// Update query
$query = ' UPDATE fr_db_sample_table SET `select` = :new 
            WHERE `select` = :old ';
$values = array(':old' => 'Inactive', ':new' => 'Active');
$result = $db->update($query, $values);
echo ' Update query return number of rows effected: ' . $result . ' <br>';

// Update query for datetime in varchar
$value = '2021-01-29 17:30:00';
//$value = '';
$date = $db->getExpression()->setDate($value);
$query = ' UPDATE fr_db_sample_table 
            SET username = ' . $date->getFragment() . ' 
            WHERE user_id = :user_id ';
$values = array_merge([':user_id' => '4'], $date->getValues());
$result = $db->update($query, $values);
echo ' Update datetime query return number of rows effected: ' . $result . ' <br>';

// Delete query
$query = ' DELETE FROM  fr_db_sample_table 
            WHERE user_id = :user_id OR username = :username ';
$values = array(':user_id' => '1', ':username' => 'user6');
$result = $db->delete($query, $values);
echo ' Delete query return number of rows effected: ' . $result . ' <br>';

// By default every query close db connection after execution. But for forcefully close use:
$db->disconnect();
echo ' Forcefully database disconnected. <br>';
