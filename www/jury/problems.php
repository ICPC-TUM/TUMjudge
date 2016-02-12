<?php
/**
 * View the problems
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$title = 'Problems';

$problem_filter = true;
require(LIBWWWDIR . '/header.php');

echo "<h1>Problems</h1>\n\n";

// Select all data
$res = $DB->q('SELECT p.probid,p.name,p.timelimit,p.memlimit,p.outputlimit,
               p.problemtext_type,
			   p.difficulty,p.author,p.source,p.topic,
			   COUNT(testcaseid) AS testcases
               FROM problem p
               LEFT JOIN testcase USING (probid)
               GROUP BY probid ORDER BY probid');

// Get number of active contests per problem
if ( count($cids)!=0 ) {
	$activecontests = $DB->q("KEYVALUETABLE SELECT probid, count(cid)
	                          FROM contestproblem
	                          WHERE cid IN (%As) GROUP BY probid", $cids);
} else {
	$activecontests = array();
}

echo "<script type='text/javascript'>
$(function() {
	$(\"#topics_filter\").tokenInput([
                {id: 7, name: \"Ruby\"},
                {id: 11, name: \"Python\"},
                {id: 13, name: \"JavaScript\"},
                {id: 17, name: \"ActionScript\"},
                {id: 19, name: \"Scheme\"},
                {id: 23, name: \"Lisp\"},
                {id: 29, name: \"C#\"},
                {id: 31, name: \"Fortran\"},
                {id: 37, name: \"Visual Basic\"},
                {id: 41, name: \"C\"},
                {id: 43, name: \"C++\"},
                {id: 47, name: \"Java\"}
            ]);
});
</script>";

echo "<input id='topics_filter' name='topics_filter' placeholder='Enter Topics here'>";
/*echo "<datalist id='topics'>
<option value='DFS/BFS'>
<option value='Shortest Path'>
<option value='Minimum Spanning Tree'>
<option value='Number Theory'>
<option value='Geometry'>
<option value='Projective Geometry'>
<option value='Segment Tree'>
<option value='Dynamic Programming'>
<option value='Flow'>
<option value='Brute Force'>
<option value='Backtracking'>
<option value='Binary Search'>
<option value='Suffix Array'>
<option value='Trie'>
<option value='Big Integer'>
<option value='Union Find'>
<option value='Greedy'>
</datalist>";
*/


if( $res->count() == 0 ) {
	echo "<p class=\"nodata\">No problems defined</p>\n\n";
} else {
	echo "<table class=\"list sortable\">\n<thead>\n" .
	     "<tr><th scope=\"col\">ID</th><th scope=\"col\">name</th>" .
		 //add new fields
		 "<th scope=\"col\">difficulty</th>" .
		 "<th scope=\"col\">author</th>" .
		 "<th scope=\"col\">source</th>" .
		 "<th scope=\"col\">topic</th>" .
		 //
	     "<th scope=\"col\" class=\"sorttable_numeric\"># contests</th>" .
	     "<th scope=\"col\">time<br />limit</th>" .
	     "<th scope=\"col\">memory<br />limit</th>" .
	     "<th scope=\"col\">output<br />limit</th>" .
	     "<th scope=\"col\">test<br />cases</th>" .
	     "<th scope=\"col\"></th>" .
	    ( IS_ADMIN ? "<th scope=\"col\"></th><th scope=\"col\"></th>" : '' ) .
	     "</tr></thead>\n<tbody>\n";

	$lastcid = -1;

	while($row = $res->next()) {
		$classes = array();
		if ( !isset($activecontests[$row['probid']]) ) $classes[] = 'disabled';
		$link = '<a href="problem.php?id=' . urlencode($row['probid']) . '">';

		echo "<tr class=\"" . implode(' ',$classes) .
			"\"><td>" . $link . "p" .
				specialchars($row['probid'])."</a>".
			"</td><td>" . $link . specialchars($row['name'])."</a>".
			//insert extra problem data
			"</td><td>" . $link . specialchars($row['difficulty'])."</a>".
			"</td><td>" . $link . specialchars($row['author'])."</a>".
			"</td><td>" . $link . specialchars($row['source'])."</a>".
			"</td><td>" . $link . specialchars($row['topic'])."</a>".
			"</td><td>".
			$link . specialchars(isset($activecontests[$row['probid']])?$activecontests[$row['probid']]:0) . "</a>" .
			"</td><td>" . $link . (int)$row['timelimit'] . "</a>" .
			"</td><td>" . $link . (isset($row['memlimit']) ? (int)$row['memlimit'] : 'default') . "</a>" .
			"</td><td>" . $link . (isset($row['outputlimit']) ? (int)$row['outputlimit'] : 'default') . "</a>" .
			"</td><td><a href=\"testcase.php?probid=" . $row['probid'] .
			"\">" . $row['testcases'] . "</a></td>";
		if ( !empty($row['problemtext_type']) ) {
			echo '<td title="view problem description">' .
			     '<a href="problem.php?id=' . urlencode($row['probid']) .
			     '&amp;cmd=viewtext"><img src="../images/' . urlencode($row['problemtext_type']) .
			     '.png" alt="problem text" /></a></td>';
		} else {
			echo '<td></td>';
		}
		if ( IS_ADMIN ) {
			echo '<td title="export problem as zip-file">' .
			     exportLink($row['probid']) . '</td>' .
			     "<td class=\"editdel\">" .
			     editLink('problem', $row['probid']) . "&nbsp;" .
			     delLink('problem','probid',$row['probid']) . "</td>";
		}
		echo "</tr>\n";
	}
	echo "</tbody>\n</table>\n\n";
}

if ( IS_ADMIN ) {
	echo "<p>" . addLink('problem') . "</p>\n\n";
	if ( class_exists("ZipArchive") ) {
		$contests = $DB->q("KEYVALUETABLE SELECT cid,
		                    CONCAT('c', cid, ': ', shortname, ' - ', name) FROM contest");
		$values = array(-1 => 'Do not link to a contest');
		foreach ($contests as $cid => $contest) {
			$values[$cid] = $contest;
		}
		echo "\n" . addForm('problem.php', 'post', null, 'multipart/form-data') .
		     'Contest: ' .
		     addSelect('contest', $values, -1, true) .
		     'Problem archive(s): ' .
		     addFileField('problem_archive[]', null, ' required multiple accept="application/zip"') .
		     addSubmit('Upload', 'upload') .
		     addEndForm() . "\n";
	}
}

require(LIBWWWDIR . '/footer.php');
