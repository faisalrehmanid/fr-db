<?php
@$_pagination       = $params['_pagination'];
@$_page_number      = $params['_page_number'];
@$_records_per_page = $params['_records_per_page'];
@$_start            = $params['_start'];
@$_order_by         = $params['_order_by'];

$_paginations = ['none', 'paging', 'loadmore'];
if (!in_array($_pagination, $_paginations)) {
    throw new \Exception('`_pagination` must be ' . implode(', ', $_paginations));
}

if (empty($_page_number) || !ctype_digit($_page_number) || !($_page_number > 0))
    $_page_number = '1';

$_records_per_pages = ['25', '50', '75', '100'];
if (!in_array($_records_per_page, $_records_per_pages)) {
    $_records_per_page = '100';
}

if (empty($_start) || !ctype_digit($_start) || !($_start > 0))
    $_start = '0';

$_order_bys = [
    'user_created_at_asc'       => 'Created At Asc',
    'user_created_at_desc'      => 'Created At Desc',
    'user_last_logedin_at_asc'  => 'Last Login Asc',
    'user_last_logedin_at_desc' => 'Last Login Desc',
];
if (!in_array($_order_by, array_keys($_order_bys))) {
    $_order_by = 'user_last_logedin_at_desc';
}

$exp    = $this->DB->getExpression();
$WHERE  = '';
$values = [];

if ($_pagination == 'paging') {
    // Get total records
    $query = " SELECT COUNT(A.USER_ID) TOTAL_RECORDS
                    FROM " . $this->schema_dot . " USERS A 
                    WHERE 1 = 1 ";
    $_total_records = $this->DB->fetchKey('total_records', $query);

    // Get total records found
    $query = " SELECT COUNT(A.USER_ID) TOTAL_RECORDS_FOUND
                    FROM " . $this->schema_dot . " USERS A 
                    WHERE 1 = 1 " . $WHERE;
    $_total_records_found = $this->DB->fetchKey('total_records_found', $query, $values);
}

$query = ' SELECT ' . $exp->getUuid('A.USER_ID') . ' USER_ID,
                        A.USERNAME,
                        A.USER_FULL_NAME,
                        A.USER_LOGIN_FAILED_ATTEMPTS,
                        A.USER_STATUS,
                        A.USER_LAST_LOGEDIN_IP,
                        ' . $exp->getDate('A.USER_LAST_LOGEDIN_AT') . ' USER_LAST_LOGEDIN_AT,
                        ' . $exp->getDate('A.USER_CREATED_AT') . ' USER_CREATED_AT
                FROM    ' . $this->schema_dot . ' USERS A
                WHERE   1 = 1 ' . $WHERE;

if ($_order_by == 'user_created_at_asc')
    $query .= " ORDER BY A.USER_CREATED_AT ASC ";
else if ($_order_by == 'user_created_at_desc')
    $query .= " ORDER BY A.USER_CREATED_AT DESC ";

if ($_order_by == 'user_last_logedin_at_asc')
    $query .= " ORDER BY A.USER_LAST_LOGEDIN_AT ASC ";
else if ($_order_by == 'user_last_logedin_at_desc')
    $query .= " ORDER BY A.USER_LAST_LOGEDIN_AT DESC ";

// echo $query;
// pr($values);

if ($_pagination == 'none')
    $data = $this->DB->fetchRows($query, $values);

if ($_pagination == 'loadmore')
    $data = $this->DB->fetchChunk($query, $values, $_page_number, $_records_per_page, $_start);

if ($_pagination == 'paging')
    $data = $this->DB->fetchChunk($query, $values, $_page_number, $_records_per_page);

if (!empty($data)) {
    $format = $this->config['datetime_format_php'];

    foreach ($data as $k => $row) {
        $data[$k]['user_last_logedin_at_format']  = ($row['user_last_logedin_at']) ?  date($format, strtotime($row['user_last_logedin_at'])) : '';
        $data[$k]['user_created_at_format']  = ($row['user_created_at']) ?  date($format, strtotime($row['user_created_at'])) : '';
    }
}

if ($_pagination == 'none')
    return ['data' => $data];

if ($_pagination == 'loadmore')
    return [
        'meta' => [
            'page_number' => $_page_number,
            'records_per_page' => $_records_per_page,
            'order_by' => $_order_by,
            'count' => (string) count($data)
        ],
        'data' => $data,
    ];

if ($_pagination == 'paging')
    return [
        'meta' => [
            'page_number' => $_page_number,
            'records_per_page' => $_records_per_page,
            'order_by' => $_order_by,
            'count' => (string) count($data),
            'total_records' => $_total_records,
            'total_records_found' => $_total_records_found
        ],
        'data' => $data,
        'records_per_pages' => $_records_per_pages,
        'order_bys' => $_order_bys
    ];
