<?php

require('init.php');
$id = getRequestID();

// select also on teamid so we can only select our own submissions
$row = $DB->q('SELECT s.valid,sf.sourcecode,sf.filename 
	       FROM submission s
               LEFT JOIN submission_file sf ON (sf.submitid = s.submitid)
               WHERE s.submitid = %i AND s.teamid = %i',$id,$teamid);

print_r($row);
               
$filename = $row['filename'];
               
header("Content-Type: text/plain; name=\"$filename\"");
header("Content-Disposition: inline; filename=\"$filename\"");
header("Content-Length: " . strlen($row['sourcecode']));               
echo $row['sourcecode'];
exit(0);               

?>