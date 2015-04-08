<?php

require('init.php');
$title = 'Point Overview';

require(LIBWWWDIR . '/header.php');
echo "<h1>" . $title . "</h1>\n\n";

global $teamdata;

$pointsArray = array(
	'#00ff00' => 4,
	'#ffff00' => 6,
	'#ff0000' => 8,
	'#ffffff' => 0,
	'default' => 1
);
$coursePointArray = array();
$courseTotalArray = array();



$res = $DB->q('SELECT *
	                   FROM contest c
	                   WHERE c.enabled = 1
			   AND c.activatetime < UNIX_TIMESTAMP(NOW())
			   ORDER BY c.cid DESC');

//header2 to show points achieved in course/maxPoints in course
$header2 = '';
$header3 = '';
$first = '';
while ($contest = $res->next()) {
	$split = explode('-', $contest['contestname']);
	$first = trim($split[0]);
	
	//first course
	if (empty($coursePointArray)) {
		$course = $first;
		$coursePointArray[$course] = 0;
		$courseTotalArray[$course] = 0;
	}
	else {
		//found new course
		if (!array_key_exists($first, $coursePointArray)) {
			$percentage = round(100*$coursePointArray[$course]/$courseTotalArray[$course],2);
			$header2 = '<h2>'.$course.': '.$coursePointArray[$course].'/'.$courseTotalArray[$course].' Points ('.$percentage.'%)</h2>';
			//print header
			if($coursePointArray[$course] > 0) {
				echo $header2.$header3;
			}
			$header3 = '';
			$course = $first;
			$coursePointArray[$course] = 0;
			$courseTotalArray[$course] = 0;
		}
		//found old course, continue
		else {
			continue;
		}
	}

	//get all contests for that course
	$try = $DB->q('SELECT *
			   FROM contest c
			   WHERE c.enabled = 1
			   AND c.activatetime < now()
			   AND c.contestname LIKE %s
			   ORDER BY c.cid ASC', (empty($first) ? $contest['contestname'] : $first) . '%' );

	while ($contest = $try->next()) {
		$split = explode('-', $contest['contestname']);
		$second = ($split[1] == NULL ? 'Contest: ' : trim($split[1]).': ');
		//get all problems in contest
		$probs = $DB->q('SELECT *
			               FROM problem p
						   JOIN contestproblem cp ON (p.probid = cp.probid)
			               WHERE cp.cid = %i
						   ORDER BY cp.shortname', $contest['cid']);

		$pr = $probs->next();
		$problemTable = '';

		$contestSum = 0;
		$contestMaxPoints = 0;
		//create contest problem table
		while($pr != NULL) {
			if (!array_key_exists($pr['color'], $pointsArray)) {
				$contestMaxPoints+= $pointsArray['default'];
			}
			else {
				$contestMaxPoints+= $pointsArray[$pr['color']];
			}
			//check if team has correct,incorrect or no submission for problem
			$solved = $DB->q('SELECT DISTINCT s.probid AS probid
								FROM submission s
								JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
				                JOIN contest c ON(c.cid=s.cid)
				                WHERE c.cid = %i
								AND s.probid = %s
								AND s.teamid = %s
					   			AND j.result = %s', $contest['cid'], $pr['probid'], $teamdata['teamid'], 'correct');

			if ($solved->next()['probid'] != NULL) {
				$solved = 'score_correct';
				if (!array_key_exists($pr['color'], $pointsArray)) {
					$contestSum+= $pointsArray['default'];
				}
				else {
					$contestSum+= $pointsArray[$pr['color']];
				}
			}
			else {
				$solved = $DB->q('SELECT DISTINCT s.probid AS probid
								FROM submission s
								JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
				                JOIN contest c ON(c.cid=s.cid)
				                WHERE c.cid = %i
								AND s.probid = %s
								AND s.teamid = %s', $contest['cid'], $pr['probid'], $teamdata['teamid']);
			
				if ($solved->next()['probid'] != NULL) {
					$solved = 'score_incorrect';
				}
				else {
					$solved = 'score_neutral';
				}
			}

			//generate table with all problems in contest
			$problemTable.= '<span style="padding:5px" class = "'.$solved.' problem-entry" title="problem \'' . htmlspecialchars($pr['name']) . '\'" scope="col">';
			$str =  (!empty($pr['color']) ? ' <div class="circle" style="background: ' .
				    htmlspecialchars($pr['color']) . ';"></div>' : '' ) .
					htmlspecialchars($pr['shortname']);
			$problemTable.= $str . '</span>';
		

			$pr = $probs->next();
		}

		//add points achieved, maximum allowed to total course array
		$courseTotalArray[$first]+=$contestMaxPoints;
		$coursePointArray[$first]+=$contestSum;

		//create h3 with contest problem info
		$header3 .= '<h3>'.$second.$contestSum.'/'.$contestMaxPoints.'<br></h3>' .
					$problemTable.'<br><br>';
	}
}
//print last course information
$percentage = round(100*$coursePointArray[$course]/$courseTotalArray[$course],2);
$header2 = '<h2>'.$course.': '.$coursePointArray[$course].'/'.$courseTotalArray[$course].' Points ('.$percentage.'%)</h2>';
//print last header
if($coursePointArray[$course] > 0) {
	echo $header2.$header3;
}

require(LIBWWWDIR . '/footer.php');
?>
