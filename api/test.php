<?php

//set the limit of the query
$limit=500;

// sanitize the input
if ( isset($_GET['offset']) )  {
	// ensure that the barcode value is formatted somewhat sanely
	$offset = (int) $_GET['offset'];
	// ensure that the offset is value is in allowed range
	if( $offset < 0 || $offset > 2147483647 ) {
		//our offset should not be smaller or bigger than the min or max allowed size
		echo "{}";
		die();
	}
} //end if
else {
	$offset=0;
}

/*
include file (item_barcode.php) supplies the following
arguments as the example below illustrates :
	$username = "username";
	$password = "password";

	$dsn = "pgsql:"
		. "host=sierra-db.school.edu;"
		. "dbname=iii;"
		. "port=1032;"
		. "sslmode=require;"
		. "charset=utf8;"
*/

//reset all variables needed for our connection
$username = null;
$password = null;
$dsn = null;
$connection = null;

require_once($_SERVER['DOCUMENT_ROOT'] . '/../includes/sql/sqlinv_group.php');

//make our database connection
try {
	// $connection = new PDO($dsn, $username, $password, array(PDO::ATTR_PERSISTENT => true));
	$connection = new PDO($dsn, $username, $password);
}

catch ( PDOException $e ) {
	$row = null;
	$statement = null;
	$connection = null;

	echo "problem connecting to database...\n";
	error_log('PDO Exception: '.$e->getMessage());
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');

//get the count of total records first
$sql = "
SELECT

-- find how many bib records we have first
count( distinct(r.record_num) )

FROM
sierra_view.subfield	as s

JOIN
sierra_view.record_metadata as r
ON
  s.record_id = r.id

JOIN
sierra_view.bib_record_property	as p
ON
  p.bib_record_id = r.id

LEFT OUTER JOIN
sierra_view.bib_record_location	as l
ON
  l.bib_record_id = r.id

WHERE
r.record_type_code = 'b'
AND (s.marc_tag = '099' and s.content = 'PERIOD' and s.field_type_code = 'y')
AND p.bib_level_code = 's'
AND p.material_code = 's'
AND l.location_code = 'rc'
";

$statement = $connection->prepare($sql);
$statement->execute();
$count_row = $statement->fetch(PDO::FETCH_ASSOC);

//perform the actual query
$sql = '
SELECT

p.best_title, p.best_title_norm, \'b\' || r.record_num || \'a\' as bib_num, r.record_num as bib_record_num,
s.content as call_num, l.location_code, p.bib_level_code, p.material_code, l.copies,
count(*) as item_count

FROM
sierra_view.subfield	as s

JOIN
sierra_view.record_metadata as r
ON
  s.record_id = r.id

JOIN
sierra_view.bib_record_property	as p
ON
  p.bib_record_id = r.id

JOIN
sierra_view.bib_record_location	as l
ON
  l.bib_record_id = r.id


LEFT OUTER JOIN
sierra_view.bib_record_item_record_link	i
ON
  r.id = i.bib_record_id

WHERE
r.record_type_code = \'b\'
AND (s.marc_tag = \'099\' and s.content = \'PERIOD\' and s.field_type_code = \'y\')
AND p.bib_level_code = \'s\'
AND p.material_code = \'s\'
AND l.location_code = \'rc\'

group by 
p.best_title,
p.best_title_norm,
bib_num,
r.record_num,
call_num,
location_code,
bib_level_code,
material_code,
copies

ORDER BY
p.best_title_norm

LIMIT ' . $limit . ' OFFSET ' . $offset . '
'
;

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');

$encode_array = [];
$encode_array['count'] = $count_row['count'];
$encode_array['limit'] = $limit;
$encode_array['offset'] = $offset;

// find the number of pages we need to produce
// if the returned count divided by the limit produces no remainder, then it's evenly divisible
if (($count_row['count'] % $limit) == 0) {
	//using floor here is probably not necessary, but doing it anyway
	$encode_array['pages'] = floor($count_row['count'] / $limit);	
}
else {
	$encode_array['pages'] = floor($count_row['count'] / $limit) + 1;
}

$encode_array['page_links'] = [];
for ($i=0; $i<$encode_array['pages']; $i++) {
	$encode_array['page_links'][$i] = 'http://' . $_SERVER[HTTP_HOST] . $_SERVER[PHP_SELF] . '?offset=' . $limit * $i;
}

// find the current page (make sure to check for returned values of false if not in the array)
$index_test = 'http://' . $_SERVER[HTTP_HOST] . $_SERVER[PHP_SELF] . '?offset=' . $offset;
$index_of_page = array_search($index_test, $encode_array['page_links']);
if ( $index_of_page === false ) {
	$encode_array['current_page'] = NULL;
}
else {
	//remember we're giving the page number, and page numbers start at 1
	$encode_array['current_page'] = $index_of_page + 1;
}

$encode_array['description'] = 'list of bib records that match the criteria outlined in the sql below';
$encode_array['data'] = $row;
$encode_array['query'] = $sql;

echo json_encode($encode_array);

$encode_array = null;
$statement = null;
$connection = null;

?>