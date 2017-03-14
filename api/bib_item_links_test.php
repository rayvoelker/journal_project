<?php
//probably want to remove this later on so that only pages from library2 can interface with this
// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");


//set the header for json output
header('Content-Type: application/json');

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

$callnumber = false;
//check to see if callnumber is set or not
if ( isset($_GET['callnumber']) )  {
	$callnumber = true;		
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
select COUNT(*) as count
FROM (

SELECT
-- p.best_title,
-- p.best_title_norm,
-- 'b' || r.record_num || 'a' as bib_num,
-- r.record_num as bib_record_num,
-- 
-- coalesce(count(i.id), 0) as item_count
r.record_num

FROM
sierra_view.record_metadata	as r

JOIN
sierra_view.bib_record_property	as p
ON
  p.bib_record_id = r.id
  

JOIN
sierra_view.bib_record_location	as l
ON
  l.bib_record_id = r.id

JOIN
sierra_view.subfield	as s1
ON
  (s1.record_id = r.id) AND (s1.marc_tag = '099' and s1.content = 'PERIOD' and s1.field_type_code = 'y')
";

if ($callnumber === true){
	$sql .= "
JOIN
sierra_view.subfield	as s2
ON
(s2.record_id = r.id) AND (s2.marc_tag = '910' AND s2.content = 'CALL NUMBER PROJECT' AND s2.field_type_code = 'y' )
";	
}

$sql .= "
-- LEFT OUTER JOIN
-- sierra_view.bib_record_item_record_link	as i
-- ON
--   i.bib_record_id = r.id

WHERE
r.record_type_code = 'b'
AND p.bib_level_code = 's'
AND P.material_code = 's'
AND l.location_code = 'rc'

GROUP BY
-- p.best_title,
-- p.best_title_norm,
r.record_num

-- ORDER BY
-- p.best_title_norm
) as q
";

$statement = $connection->prepare($sql);
$statement->execute();
$count_row = $statement->fetch(PDO::FETCH_ASSOC);

//perform the actual query
$sql = "
SELECT
p.best_title,
p.best_title_norm,
'b' || r.record_num || 'a' as bib_num,
r.record_num as bib_record_num,
coalesce(count(i.id), 0) as item_count,
'http://library2.udayton.edu/api/journal_project/item_info.php?bib=' || r.record_num as items_api_link

FROM
sierra_view.record_metadata	as r

JOIN
sierra_view.bib_record_property	as p
ON
  p.bib_record_id = r.id
  

JOIN
sierra_view.bib_record_location	as l
ON
  l.bib_record_id = r.id

JOIN
sierra_view.subfield	as s1
ON
  (s1.record_id = r.id) AND (s1.marc_tag = '099' and s1.content = 'PERIOD' and s1.field_type_code = 'y')
";


if ($callnumber === true) {
	$sql .= "
JOIN
sierra_view.subfield	as s2
ON
  (s2.record_id = r.id) AND (s2.marc_tag = '910' AND s2.content = 'CALL NUMBER PROJECT' AND s2.field_type_code = 'y' )
";
}

$sql .= "
LEFT OUTER JOIN
sierra_view.bib_record_item_record_link	as i
ON
  i.bib_record_id = r.id

WHERE
r.record_type_code = 'b'
AND p.bib_level_code = 's'
AND P.material_code = 's'
AND l.location_code = 'rc'

GROUP BY
p.best_title,
p.best_title_norm,
r.record_num

ORDER BY
p.best_title_norm

LIMIT " . $limit . " OFFSET " . $offset . " 
";

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

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
	if ($callnumber === true) {
		$encode_array['page_links'][$i] .= '&callnumber=true';
	}
}

// find the current page (make sure to check for returned values of false if not in the array)
$index_test = 'http://' . $_SERVER[HTTP_HOST] . $_SERVER[PHP_SELF] . '?offset=' . $offset;
if ($callnumber === true) {
	$index_test .= '&callnumber=true';
}
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