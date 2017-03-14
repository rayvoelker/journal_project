<?php

//probably want to remove this later on so that only pages from library2 can interface with this
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

//set the header for json output
header('Content-Type: application/json');

// sanitize the input
if ( isset($_GET['bib']) )  {
	// ensure that the barcode value is formatted somewhat sanely
	$bib = (int) $_GET['bib'];
	// ensure that the bib is value is in allowed range
	if( $bib < 0 || $bib > 2147483647 ) {
		//our bib should not be smaller or bigger than the min or max allowed size
		echo "{}";
		die();
	}
} //end if
else {
	//no input so die.
	echo "{}";
	die();
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

//perform the query
$sql = "
SELECT
-- *
b.record_type_code,
b.record_num,
b.creation_date_gmt,
b.record_last_updated_gmt,
l.items_display_order,
i.barcode,
UPPER(i.call_number_norm) as call_number_norm,
v.field_content as volume


FROM
-- don't worry about limiting to bib records only, since we do our join on the ids to the bib record
sierra_view.record_metadata	as b

JOIN
sierra_view.bib_record_item_record_link	as l
ON
  l.bib_record_id = b.id

LEFT OUTER JOIN
sierra_view.item_record_property	as i
ON
  i.item_record_id = l.item_record_id

LEFT OUTER JOIN
sierra_view.varfield	as v
ON
  (l.item_record_id = v.record_id) AND (v.varfield_type_code = 'v')

WHERE
b.record_num = " . $bib . "

ORDER BY
l.items_display_order
"
;

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

$encode_array = [];
$encode_array['count'] = count($row);


$encode_array['description'] = 'list of items attached to the bib record';
$encode_array['data'] = $row;
$encode_array['query'] = $sql;

echo json_encode($encode_array);

$encode_array = null;
$statement = null;
$connection = null;

?>
