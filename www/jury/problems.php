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
var topics_regex = new Array();
topics_regex['BFS/DFS'] = /[A-Za-z0-9,\s]*(((Depth|Breadth)(\s|-)First(\s|-)Search)|((B|D)FS))[A-Za-z0-9,\s]*/gi;
topics_regex['Shortest Path'] = /[A-Za-z0-9,\s]*((Shortest(\s|-)Path)|Dijkstra|Bellman(\s|-)Ford)[A-Za-z0-9,\s]*/gi;
topics_regex['Minimum Spanning Tree'] = /[A-Za-z0-9,\s]*((Minimum(\s|-)Spanning(\s|-)Tree)|Prim|Kruskal)[A-Za-z0-9,\s]*/gi;
topics_regex['Number Theory'] = /[A-Za-z0-9,\s]*((Chinese(\s|-)Remainder(\s|-)Theorem)|Number(\s|-)Theory|gcd|lcm|Euclidean)[A-Za-z0-9,\s]*/gi;
topics_regex['Geometry'] = /[A-Za-z0-9,\s]*((Geometry)\b(?<!projective(\s|-)Geometry)|Convex(\s|-)hull|Point(\s|-)in(\s|-)polygon)[A-Za-z0-9,\s]*/gi;
topics_regex['Projective Geometry'] = /[A-Za-z0-9,\s]*(projective(\s|-)geometry)[A-Za-z0-9,\s]*/gi;
topics_regex['Dynamic Programming'] = /[A-Za-z0-9,\s]*(dynamic(\s|-)programming)[A-Za-z0-9,\s]*/gi;
topics_regex['Union Find'] = /[A-Za-z0-9,\s]*(union(\s|-)find)[A-Za-z0-9,\s]*/gi;
topics_regex['Flow'] = /max(imum)?(\s|-)flow|min(imum)?(\s|-)cut|push(\s|-)relabel|goldberg(\s|-)tarjan|dinic/gi;
topics_regex['Brute Force'] = /[A-Za-z0-9,\s]*(brute(\s|-)force|backtracking)[A-Za-z0-9,\s]*/gi;
topics_regex['Greedy'] = /[A-Za-z0-9,\s]*(greedy)[A-Za-z0-9,\s]*/gi;


var topics = [
	{name: \"BFS/DFS\"},
	{name: \"Shortest Path\"},
	{name: \"Minimum Spanning Tree\"},
	{name: \"Number Theory\"},
	{name: \"Geometry\"}
	{name: \"Projective Geometry\"}
	{name: \"Dynamic Programming\"}
	{name: \"Union Find\"}
	{name: \"Flow\"}
	{name: \"Brute Force\"}
	{name: \"Greedy\"}
	];
$(function() {
	$(\"#topics_filter\").tokenInput(topics, {
	  allowFreeTagging:true, 
	  tokenValue: 'name',
	  preventDuplicates: true,
          hintText: 'Add topic to search for',
          searchingText: 'searching topics...',
	});
});

function resetFilter() {
	//Reset table visibility
	$(\".list tbody tr\").each(function(){\$(this).css(\"display\",\"table-row\")});
}

function resetAll() {
	$(\"#topics_filter\").tokenInput(\"clear\");
}

function filterProblems() {
	resetFilter()
	filterTopics();
}

function filterDifficulty() {
	
}

function filterTopics() {
	var selected_topics = $(\"#topics_filter\").tokenInput(\"get\");
	if(selected_topics.length === 0) {
		return;
	}
	
	$(\".list tbody tr\").each(function() {
	   var found = false;
	   for (var i=0;i<selected_topics.length;i++) {
		if(topics_regex[selected_topics[i].name].exec($(this).find(\"td:nth-child(6)\").text()) !== null) {
			found = true;
			break;
		}
	   }
	   if(!found) {
		 $(this).css(\"display\",\"none\");
	   }
	});
}
</script>";

echo "<input id='topics_filter' name='topics_filter' placeholder='Enter Topics here'>";
echo "<button onClick='javascript:filterProblems();'>Filter</button>";
echo "<button onClick='javascript:resetAll();'>Reset Filter</button>";
/*echo "<datalist id='topics'>
<option value='DFS/BFS'>
<option value='Shortest Path'>
<option value='Minimum Spanning Tree'>
<option value='Number Theory'>
<option value='Geometry'>
<option value='Projective Geometry'>
<option value='Dynamic Programming'>
<option value='Flow'>
<option value='Brute Force'>
<option value='Binary Search'>
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
