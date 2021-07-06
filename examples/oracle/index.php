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

// Oracle connection configuration
$config =  array(
    'driver' => 'oci8',
    'connection' => 'ERPDEVDB',
    'username' => 'COMM_GATEWAY',
    'password' => 'COMM_GATEWAY',
    'character_set' => 'AL32UTF8'
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
// Drop Sequence if already exists PL/SQL
$query = " BEGIN FOR I IN (SELECT NULL FROM USER_SEQUENCES WHERE SEQUENCE_NAME = 'FR_DB_SAMPLE_TABLE_SEQ') 
LOOP EXECUTE IMMEDIATE 'DROP SEQUENCE FR_DB_SAMPLE_TABLE_SEQ'; END LOOP; END; ";
$db->query($query);
echo '`FR_DB_SAMPLE_TABLE_SEQ` Sequence has been droped. <br>';

// Drop table if already exists
$query = " BEGIN FOR I IN (SELECT NULL FROM USER_TABLES WHERE TABLE_NAME = 'FR_DB_SAMPLE_TABLE') LOOP EXECUTE IMMEDIATE 'DROP TABLE FR_DB_SAMPLE_TABLE CASCADE CONSTRAINTS'; END LOOP; END; ";
$db->query($query);
echo '`FR_DB_SAMPLE_TABLE` Table has been droped. <br>';

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
echo 'Table and Sequence has been created. SQL script `./backup.sql` imported successfully. <br>';

// How to check database instance
if ($db instanceof \FR\Db\DbInterface)
    echo 'How to check database instance type: \FR\Db\DbInterface <br>';

// Get database platform name
$platform_name = $db->getDbPlatformName();
echo 'Database platform name is: ' . $platform_name . ' <br>';

// Insert data into database
$values = [
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31c'),
        'USERNAME' => 'user1',
        'SELECT' => 'Active',
        'DATE' => $db->getExpression()->setDate('1987-01-01')
    ],
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31d'),
        'USERNAME' => 'user2',
        'SELECT' => 'Active',
        'DATE' => $db->getExpression()->setDate('2020-02-29 17:50:25')
    ],

    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31e'),
        'USERNAME' => 'user3',
        'SELECT' => 'Inactive',
        'DATE' => $db->getExpression()->setDate('2020-02-29')
    ],
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad31f'),
        'USERNAME' => 'user4',
        'SELECT' => 'Active',
        'DATE' => $db->getExpression()->setDate('1987-12-31 05:30:10')
    ],
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad311'),
        'USERNAME' => 'user5',
        'SELECT' => 'Inactive',
        'DATE' => $db->getExpression()->setDate('1986-01-01')
    ],
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad312'),
        'USERNAME' => 'user6',
        'SELECT' => 'Inactive',
        'DATE' => $db->getExpression()->setDate('1987-12-01')
    ],
    [
        'UUID' => $db->getExpression()->setUuid('c1dd8ce513b11e981f288b11109ad313'),
        'USERNAME' => 'user7',
        'SELECT' => 'Active',
        'DATE' => $db->getExpression()->setDate('1987-01-31')
    ],
];

/*
// Insert without sequence
$result = $db->insert('FR_DB_SAMPLE_TABLE', $values); 
*/

// Insert with sequence
$result = $db->insert(
    [
        'table' => 'FR_DB_SAMPLE_TABLE',
        'sequence' => 'FR_DB_SAMPLE_TABLE_SEQ',
        'column' => 'USER_ID'
    ],
    $values
);
echo 'Sample data inserted in FR_DB_SAMPLE_TABLE table. <br>';
if ($result !== false)
    echo 'Last Generated Value: ' . $result . '<br>';

// Fetch single row for SELECT query
$query = ' SELECT  USER_ID,
                   ' . $db->getExpression()->getUuid('UUID') . ' UUID, 
                   USERNAME,
                   "SELECT",
                   ' . $db->getExpression()->getDate('FR_DB_SAMPLE_TABLE.DATE') . ' USER_DATE
            FROM  FR_DB_SAMPLE_TABLE 
            WHERE ' . $db->getExpression()->getUuid('UUID') . ' = :UUID ';
$values = array(':UUID' => 'c1dd8ce513b11e981f288b11109ad31d');
$result = $db->fetchRow($query, $values);
echo ' Fetch single row <br>';
pr($result, false);

// Fetch multiple rows for SELECT query
$query = ' SELECT USER_ID,
                  ' . $db->getExpression()->getUuid('UUID') . ' UUID, 
                  USERNAME,
                  "SELECT",
                  ' . $db->getExpression()->getDate('FR_DB_SAMPLE_TABLE.DATE') . ' USER_DATE
            FROM  FR_DB_SAMPLE_TABLE 
            WHERE "SELECT" = :V ';
$values = array(':V' => 'Active');
$result = $db->fetchRows($query, $values);
echo ' Fetch multiple rows <br>';
pr($result, false);

// Fetch rows chunk for pagination using `SELECT` query
$query = ' SELECT * FROM  FR_DB_SAMPLE_TABLE ORDER BY USER_ID ASC ';
$values = array();
$page_number = 2;
$records_per_page = 5;
$result = $db->fetchChunk($query, $values, $page_number, $records_per_page);
echo ' Fetch rows chunk for pagination <br>';
pr($result, false);

// Fetch single column for SELECT query
$query = ' SELECT USERNAME FROM FR_DB_SAMPLE_TABLE ';
$result = $db->fetchColumn($query);
echo ' Fetch single column <br> ';
pr($result, false);

// Fetch string value for given key from single row
$query = ' SELECT USERNAME FROM  FR_DB_SAMPLE_TABLE 
            WHERE UPPER(USERNAME) = UPPER(:USERNAME) ';
$values = array(':USERNAME' => 'user5');
$result = $db->fetchKey('USERNAME', $query, $values);
echo ' Fetch string value: ' . $result . ' <br> ';

// Fetch using IN Clause
$array = ['user3', 'user7'];
//$array = [];
$in = $db->getExpression()->in($array);

$query = ' SELECT USER_ID, USERNAME FROM  FR_DB_SAMPLE_TABLE ';
if (!empty($in->getFragment()))
    $query = ' SELECT USER_ID, USERNAME FROM FR_DB_SAMPLE_TABLE WHERE USERNAME ' . $in->getFragment();

$values = array_merge([], $in->getValues());
$result = $db->fetchRows($query, $values);
echo ' Fetch using IN Clause <br> ';
pr($result, false);

// Update query
$query = ' UPDATE FR_DB_SAMPLE_TABLE SET "SELECT" = :NEW WHERE "SELECT" = :OLD ';
$values = array(':OLD' => 'Inactive', ':NEW' => 'Active');
$result = $db->update($query, $values);
echo ' Update query return number of rows effected: ' . $result . ' <br>';

// Update query for datetime in VARCHAR
$value = '2021-01-29 17:30:00';
//$value = '';
$date = $db->getExpression()->setDate($value);
$query = ' UPDATE  FR_DB_SAMPLE_TABLE SET USERNAME = TO_CHAR(' . $date->getFragment() . ', \'YYYY-MM-DD HH24:MI:SS\') WHERE USER_ID = :USER_ID ';
$values = array_merge([':USER_ID' => '4'], $date->getValues());
$result = $db->update($query, $values);
echo ' Update datetime query return number of rows effected: ' . $result . ' <br>';

// Delete query
$query = ' DELETE FROM  FR_DB_SAMPLE_TABLE 
            WHERE USER_ID = :USER_ID OR UPPER(USERNAME) = UPPER(:USERNAME) ';
$values = array(':USER_ID' => '1', ':USERNAME' => 'user6');
$result = $db->delete($query, $values);
echo ' Delete query return number of rows effected: ' . $result . ' <br>';

// By default every query close db connection after execution. But for forcefully close use:
$db->disconnect();
echo ' Forcefully database disconnected. <br>';
