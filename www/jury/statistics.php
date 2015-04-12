<?php

require('init.php');

// Include flot javascript library
//$extrahead = '';
$extrahead .= '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="../js/flot/excanvas.min.js"></script><![endif]-->';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.stack.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.time.js"></script>';

//set sortorder variable
$sortOrder = 10;

// one bar per 10 minutes, should be in config somewhere
$bar_size = 6*60;

//count all students on system
$students = $DB->q('SELECT COUNT(*) as count
	                   FROM team t
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
			   WHERE tc.sortorder < %i AND LOWER(t.name) != %s', $sortOrder, 'domjudge');
$students = $students->next()['count'];

//create id, name, time factor array of all available languages
$languageIdArray = array();
$languageNameArray = array();
$languageTimeFactorArray = array();

$result = $DB->q('SELECT * FROM language l WHERE l.allow_submit = 1');
while ($language = $result->next()) {
	array_push($languageIdArray, $language['langid']);
	array_push($languageNameArray, $language['name']);
	array_push($languageTimeFactorArray, $language['time_factor']);
}

//create 2d array, row = language, column = stats
$dataArray = array(array());

if(empty($_GET['probid'])) {
	$title = "Statistics";
	//draw graph
	if(empty($_GET['langid'])) {
	$res = $DB->q('SELECT j.result AS result,
                   COUNT(j.result) as count,
                   (c.freezetime IS NOT NULL && s.submittime >= c.freezetime) AS afterfreeze,
                   (ROUND((s.submittime - c.starttime) / 60, 0) DIV %i) * %i AS minute
                   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   LEFT OUTER JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
                   GROUP BY minute, result', $bar_size, $bar_size, $sortOrder, $cid, 'domjudge');
	}
	else {
	$res = $DB->q('SELECT result,
	                   COUNT(result) as count,
	                   (c.freezetime IS NOT NULL && submittime >= c.freezetime) AS afterfreeze,
	                   (ROUND((s.submittime - c.starttime) / 60, 0) DIV %i) * %i AS minute
	                   FROM submission s
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
        	           LEFT OUTER JOIN contest c ON(c.cid=s.cid)
			   JOIN team t ON(s.teamid=t.teamid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
	                   AND submittime < c.endtime
	                   AND submittime >= c.starttime
			   AND s.langid = %s
	                   GROUP BY minute, result', $bar_size, $bar_size, $sortOrder, $cid, 'domjudge', $_GET['langid']);
	}

	//count how many students tried to solve in contest
	$tried = $DB->q('SELECT DISTINCT t.teamid
                   FROM team t
		   JOIN submission s ON(s.teamid=t.teamid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime', $sortOrder, $cid, 'domjudge');
	$counter = 0;
	while ($tried->next()) {
		$counter++;
	}
	$tried = $counter;

	//count how many students solved at least 1 problem in contest
	$solved = $DB->q('SELECT DISTINCT t.teamid
                   FROM team t
		   JOIN submission s ON(s.teamid=t.teamid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND j.result = %s', $sortOrder, $cid, 'domjudge', 'correct');
	$counter = 0;
	while ($solved->next()) {
		$counter++;
	}
	$solved = $counter;

	//count how many submissions were made in contest
	$submissions = $DB->q('SELECT COUNT(*) as count
                   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime', $sortOrder, $cid, 'domjudge');

	$submissions = $submissions->next()['count'];
	if ($submissions == null) {
		$submissions = 0;
	}

	//get the fastest problem solved for all problems
	$fastest = $DB->q('SELECT ROUND((s.submittime - c.starttime) / 60, 0) as timediff, t.name as name
		   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   ORDER BY timediff ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge');

	$fastest = $fastest->next();
	if ($fastest['timediff'] == null) {
		$fastest = 'None';
		$fastestName = 'None';
	}
	else {
		$fastestName = $fastest['name'];
		$fastest = $fastest['timediff'];
	}

	//get the shortest code in all problems
	$shortestCode = $DB->q('SELECT SUM(length(sf.sourcecode)) as sum, t.name as name
		   FROM submission_file sf
                   JOIN submission s ON(sf.submitid = s.submitid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   GROUP BY sf.submitid
		   ORDER BY SUM(length(sf.sourcecode)) ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge');
	$shortestCode = $shortestCode->next();
	if ($shortestCode['sum'] == null) {
		$shortestCode = 'None';
		$shortestCodeName = 'None';
	}
	else {
		$shortestCodeName = $shortestCode['name'];
		$shortestCode = $shortestCode['sum'];
	}

	//get the best runtime for all problems
	$bestRuntimeResult = $DB->q('SELECT (MAX(jr.runtime)*100)/(CEILING(p.timelimit * l.time_factor)) AS best_runtime, t.name AS team_name
			FROM judging_run jr
			JOIN (SELECT *
				 FROM judging j1
				 JOIN (SELECT MAX(judgingid) AS newid
					   FROM judging
				       GROUP BY submitid) j2 
			     	 ON (j1.judgingid = j2.newid)) j
				 ON (j.judgingid = jr.judgingid AND j.valid=1 AND j.result = %s)
			JOIN submission s ON (j.submitid = s.submitid)
			JOIN contest c ON(c.cid=s.cid)
			JOIN team t ON(s.teamid=t.teamid)
			JOIN team_category tc ON (t.categoryid=tc.categoryid)
			JOIN language l ON (l.langid = s.langid)
			JOIN problem p ON (p.probid = s.probid)
			WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
			AND s.submittime < c.endtime
			AND s.submittime >= c.starttime
			GROUP BY j.judgingid
			ORDER BY best_runtime ASC', 'correct', $sortOrder, $cid, 'domjudge');

	$bestRuntimeNameArray = array();
	$bestRuntime = $bestRuntimeResult->next();
	if ($bestRuntime['team_name'] == null) {
		$bestRuntime = 'None';
		array_push($bestRuntimeNameArray, 'None');
	}
	else {
		array_push($bestRuntimeNameArray, $bestRuntime['team_name']);
		$current = $bestRuntimeResult->next();
		while($current != null && $current['best_runtime'] === $bestRuntime['best_runtime']) {
			if (!in_array($current['team_name'], $bestRuntimeNameArray)) {
				array_push($bestRuntimeNameArray, $current['team_name']);
			}
			$current = $bestRuntimeResult->next();
		}
		$bestRuntime = $bestRuntime['best_runtime'];
	}
	//format bestRuntimeName for display
	$bestRuntimeName = implode(', ', $bestRuntimeNameArray);

	//with language
	for($langCount = 0; $langCount < count($languageIdArray); $langCount++) {	
		//count how many students tried to solve in contest
		$tried1 = $DB->q('SELECT DISTINCT t.teamid
	                   FROM team t
			   JOIN submission s ON(s.teamid=t.teamid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount]);
		$counter = 0;
		while ($tried1->next()) {
			$counter++;
		}
		$tried1 = $counter;
		$dataArray[$langCount][0] = $tried1;

		//count how many students solved at least 1 problem in contest
		$solved1 = $DB->q('SELECT DISTINCT t.teamid
	                   FROM team t
			   JOIN submission s ON(s.teamid=t.teamid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s
			   AND j.result = %s', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount], 'correct');
		$counter = 0;
		while ($solved1->next()) {
			$counter++;
		}
		$solved1 = $counter;
		$dataArray[$langCount][1] = $solved1;

		//count how many submissions were made in contest
		$submissions1 = $DB->q('SELECT COUNT(*) as count
	                   FROM submission s
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team t ON(s.teamid=t.teamid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount]);

		$submissions1 = $submissions1->next()['count'];
		if ($submissions1 == null) {
			$submissions1 = 0;
		}
		$dataArray[$langCount][2] = $submissions1;

		//get the fastest problem solved for all problems
		$fastest1 = $DB->q('SELECT ROUND((s.submittime - c.starttime) / 60, 0) as timediff, t.name as name
		   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND s.langid = %s
		   ORDER BY timediff ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount]);
	
		$fastest1 = $fastest1->next();
		$fastestName1 = 'None';
		if ($fastest1['timediff'] == null) {
			$fastest1 = 'None';
		}
		else {
			$fastestName1 = $fastest1['name'];
			$fastest1 = $fastest1['timediff'];
		}
		$dataArray[$langCount][3] = $fastest1;
		$dataArray[$langCount][4] = $fastestName1;

		//get the shortest code in all problems
		$shortestCode1 = $DB->q('SELECT SUM(length(sf.sourcecode)) as sum, t.name as name
			   FROM submission_file sf
	                   JOIN submission s ON(sf.submitid = s.submitid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team t ON(s.teamid=t.teamid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s
			   GROUP BY sf.submitid
		  	   ORDER BY SUM(length(sf.sourcecode)) ASC
			   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount]);

		$shortestCode1 = $shortestCode1->next();
		$shortestCodeName1 = 'None';
		if ($shortestCode1['sum'] == null) {
			$shortestCode1 = 'None';
		}
		else {
			$shortestCodeName1 = $shortestCode1['name'];
			$shortestCode1 = $shortestCode1['sum'];
		}
		$dataArray[$langCount][5] = $shortestCode1;
		$dataArray[$langCount][6] = $shortestCodeName1;

		//get the best runtime for all problems
		$bestRuntimeResult = $DB->q('SELECT (MAX(jr.runtime)*100)/(CEILING(p.timelimit * l.time_factor)) AS best_runtime, t.name AS team_name
				FROM judging_run jr
				JOIN (SELECT *
					 FROM judging j1
					 JOIN (SELECT MAX(judgingid) AS newid
						   FROM judging
					       GROUP BY submitid) j2 
				     	 ON (j1.judgingid = j2.newid)) j
					 ON (j.judgingid = jr.judgingid AND j.valid=1 AND j.result = %s)
				JOIN submission s ON (j.submitid = s.submitid)
				JOIN contest c ON(c.cid=s.cid)
				JOIN team t ON(s.teamid=t.teamid)
				JOIN team_category tc ON (t.categoryid=tc.categoryid)
				JOIN language l ON (l.langid = s.langid)
				JOIN problem p ON (p.probid = s.probid)
				WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
				AND s.submittime < c.endtime
				AND s.submittime >= c.starttime
				AND s.langid = %s
				GROUP BY j.judgingid
				ORDER BY best_runtime ASC', 'correct', $sortOrder, $cid, 'domjudge', $languageIdArray[$langCount]);

		$bestRuntimeNameArray = array();
		$bestRuntime1 = $bestRuntimeResult->next();

		if ($bestRuntime1['team_name'] == null) {
			$bestRuntime1 = 'None';
			array_push($bestRuntimeNameArray, 'None');
		}
		else {
			array_push($bestRuntimeNameArray, $bestRuntime1['team_name']);
			$current = $bestRuntimeResult->next();
			while($current != null && $current['best_runtime'] == $bestRuntime1['best_runtime']) {
				if (!in_array($current['team_name'], $bestRuntimeNameArray)) {
					array_push($bestRuntimeNameArray, $current['team_name']);
				}
				$current = $bestRuntimeResult->next();
			}
			$bestRuntime1 = $bestRuntime1['best_runtime'];
		}
		//format bestRuntimeName for display
		$bestRuntimeName1 = implode(', ', $bestRuntimeNameArray);

		$dataArray[$langCount][7] = $bestRuntime1;
		$dataArray[$langCount][8] = $bestRuntimeName1;

	}
}
else {
	$problemName = $DB->q('SELECT shortname FROM contestproblem WHERE probid = %i AND cid = %i', $_GET['probid'], $cid);
	$problemName = $problemName->next()['shortname'];
	$title = "Statistics - Problem " . $problemName;
	//draw graph
	if (empty($_GET['langid'])) {
		$res = $DB->q('SELECT j.result AS result,
	                   COUNT(j.result) as count,
	                   (c.freezetime IS NOT NULL && s.submittime >= c.freezetime) AS afterfreeze,
	                   (ROUND((s.submittime - c.starttime) / 60, 0) DIV %i) * %i AS minute
	                   FROM submission s
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   LEFT OUTER JOIN contest c ON(c.cid=s.cid)
			   JOIN team t ON(s.teamid=t.teamid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
	                   AND s.probid = %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
	                   GROUP BY minute, result', $bar_size, $bar_size, $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid']);
	}
	else {
		$res = $DB->q('SELECT j.result,
                   COUNT(j/result) as count,
                   (c.freezetime IS NOT NULL && submittime >= c.freezetime) AS afterfreeze,
                   (ROUND((s.submittime - c.starttime) / 60, 0) DIV %i) * %i AS minute
                   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   LEFT OUTER JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
                   AND s.probid = %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND s.langid = %s
                   GROUP BY minute, result', $bar_size, $bar_size, $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid'], $_GET['langid']);

	}
	//count how many students tried to solve the problem
	$tried = $DB->q('SELECT DISTINCT t.teamid
                   FROM team t
		   JOIN submission s ON(s.teamid=t.teamid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
		   AND s.probid = %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid']);
	$counter = 0;
	while ($tried->next()) {
		$counter++;
	}
	$tried = $counter;
	
	//count how many students solved the problem
	$solved = $DB->q('SELECT DISTINCT t.teamid
                   FROM team t
		   JOIN submission s ON(s.teamid=t.teamid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
		   AND s.probid = %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND j.result = %s', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid'], 'correct');
	$counter = 0;
	while ($solved->next()) {
		$counter++;
	}
	$solved = $counter;
	
	//count how many submissions were made for this problem
	$submissions = $DB->q('SELECT COUNT(*) as count
                   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
		   AND s.probid = %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid']);
	
	$submissions = $submissions->next()['count'];
	if ($submissions == null) {
		$submissions = 0;
	}

	//get the fastest time solved for this problem
	$fastest = $DB->q('SELECT ROUND((s.submittime - c.starttime) / 60, 0) as timediff, t.name as name
		   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND s.probid = %s
		   ORDER BY timediff ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid']);
	
	$fastest = $fastest->next();
	$fastestName = 'None';
	if ($fastest['timediff'] == null) {
		$fastest = 'None';
	}
	else {
		$fastestName = $fastest['name'];
		$fastest = $fastest['timediff'];
	}

	//get the shortest code for this problem
	$shortestCode = $DB->q('SELECT SUM(length(sf.sourcecode)) as sum, t.name as name
		   FROM submission_file sf
                   JOIN submission s ON(sf.submitid = s.submitid)
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND s.probid = %s
		   GROUP BY sf.submitid
		   ORDER BY SUM(length(sf.sourcecode)) ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid']);

	$shortestCode = $shortestCode->next();
	$shortestCodeName = 'None';
	if ($shortestCode['sum'] == null) {
		$shortestCode = 'None';
	}
	else {
		$shortestCodeName = $shortestCode['name'];
		$shortestCode = $shortestCode['sum'];
	}

	//get the best runtime for all problems
	$bestRuntimeResult = $DB->q('SELECT (MAX(jr.runtime)*100)/(CEILING(p.timelimit * l.time_factor)) AS best_runtime, t.name AS team_name
			FROM judging_run jr
			JOIN (SELECT *
				 FROM judging j1
				 JOIN (SELECT MAX(judgingid) AS newid
					   FROM judging
				       GROUP BY submitid) j2 
			     	 ON (j1.judgingid = j2.newid)) j
				 ON (j.judgingid = jr.judgingid AND j.valid=1 AND j.result = %s)
			JOIN submission s ON (j.submitid = s.submitid)
			JOIN contest c ON(c.cid=s.cid)
			JOIN team t ON(s.teamid=t.teamid)
			JOIN team_category tc ON (t.categoryid=tc.categoryid)
			JOIN language l ON (l.langid = s.langid)
			JOIN problem p ON (p.probid = s.probid)
			WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
			AND s.submittime < c.endtime
			AND s.submittime >= c.starttime
			AND s.probid = %s
			GROUP BY j.judgingid
			ORDER BY best_runtime ASC', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid']);

	$bestRuntimeNameArray = array();
	$bestRuntime = $bestRuntimeResult->next();
	if ($bestRuntime['team_name'] == null) {
		$bestRuntime = 'None';
		array_push($bestRuntimeNameArray, 'None');
	}
	else {
		array_push($bestRuntimeNameArray, $bestRuntime['team_name']);
		$current = $bestRuntimeResult->next();
		while($current != null && $current['best_runtime'] === $bestRuntime['best_runtime']) {
			if (!in_array($current['team_name'], $bestRuntimeNameArray)) {
				array_push($bestRuntimeNameArray, $current['team_name']);
			}
			$current = $bestRuntimeResult->next();
		}
		$bestRuntime = $bestRuntime['best_runtime'];
	}
	//format bestRuntimeName for display
	$bestRuntimeName = implode(', ', $bestRuntimeNameArray);
		
	//with language
	for($langCount = 0; $langCount < count($languageIdArray); $langCount++) {
		//count how many students tried to solve the problem
		$tried1 = $DB->q('SELECT DISTINCT t.teamid
	                   FROM team t
			   JOIN submission s ON(s.teamid=t.teamid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
			   AND s.probid = %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid'], $languageIdArray[$langCount]);
		$counter = 0;
		while ($tried1->next()) {
			$counter++;
		}
		$tried1 = $counter;
		$dataArray[$langCount][0] = $tried1;
	
		//count how many students solved the problem
		$solved1 = $DB->q('SELECT DISTINCT t.teamid
	                   FROM team t
			   JOIN submission s ON(s.teamid=t.teamid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
			   AND s.probid = %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s
			   AND j.result = %s', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid'], $languageIdArray[$langCount], 'correct');
		$counter = 0;
		while ($solved1->next()) {
			$counter++;
		}
		$solved1 = $counter;
		$dataArray[$langCount][1] = $solved1;
		
		//count how many submissions were made for this problem
		$submissions1 = $DB->q('SELECT COUNT(*) as count
		                   FROM submission s
		                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
		                   JOIN contest c ON(c.cid=s.cid)
				   JOIN team t ON(s.teamid=t.teamid)
				   JOIN team_category tc ON(t.categoryid=tc.categoryid)
		                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s AND LOWER(s.teamid) != %s
				   AND s.probid = %s
		                   AND s.submittime < c.endtime
		                   AND s.submittime >= c.starttime
				   AND s.langid = %s', $sortOrder, $cid, 'domjudge', 'coolteam', $_GET['probid'],  $languageIdArray[$langCount]);
	
		$submissions1 = $submissions1->next()['count'];
		if ($submissions1 == null) {
			$submissions1 = 0;
		}
		$dataArray[$langCount][2] = $submissions1;

		//get the fastest time solved for this problems
		$fastest1 = $DB->q('SELECT ROUND((s.submittime - c.starttime) / 60, 0) as timediff, t.name as name
		   FROM submission s
                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
                   JOIN contest c ON(c.cid=s.cid)
		   JOIN team t ON(s.teamid=t.teamid)
		   JOIN team_category tc ON(t.categoryid=tc.categoryid)
                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
                   AND s.submittime < c.endtime
                   AND s.submittime >= c.starttime
		   AND s.probid = %s
		   AND s.langid = %s
		   ORDER BY timediff ASC
		   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid'], $languageIdArray[$langCount]);
		
		$fastest1 = $fastest1->next();
		$fastestName1 = 'None';
		if ($fastest1['timediff'] == null) {
			$fastest1 = 'None';
		}
		else {
			$fastestName1 = $fastest1['name'];
			$fastest1 = $fastest1['timediff'];
		}
		$dataArray[$langCount][3] = $fastest1;
		$dataArray[$langCount][4] = $fastestName1;
	
		//get the shortest code for this problem
		$shortestCode1 = $DB->q('SELECT SUM(length(sf.sourcecode)) as sum, t.name as name
			   FROM submission_file sf
	                   JOIN submission s ON(sf.submitid = s.submitid)
	                   JOIN judging j ON(s.submitid=j.submitid AND j.valid=1 AND j.result = %s)
	                   JOIN contest c ON(c.cid=s.cid)
			   JOIN team t ON(s.teamid=t.teamid)
			   JOIN team_category tc ON(t.categoryid=tc.categoryid)
	                   WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
			   AND s.probid = %s
	                   AND s.submittime < c.endtime
	                   AND s.submittime >= c.starttime
			   AND s.langid = %s
			   GROUP BY sf.submitid
		  	   ORDER BY SUM(length(sf.sourcecode)) ASC
			   LIMIT 1', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid'], $languageIdArray[$langCount]);

		$shortestCode1 = $shortestCode1->next();
		$shortestCodeName1 = 'None';
		if ($shortestCode1['sum'] == null) {
			$shortestCode1 = 'None';
		}
		else {
			$shortestCodeName1 = $shortestCode1['name'];
			$shortestCode1 = $shortestCode1['sum'];
		}
		$dataArray[$langCount][5] = $shortestCode1;
		$dataArray[$langCount][6] = $shortestCodeName1;

		//get the best runtime for this problem
		$bestRuntimeResult = $DB->q('SELECT (MAX(jr.runtime)*100)/(CEILING(p.timelimit * l.time_factor)) AS best_runtime, t.name AS team_name
				FROM judging_run jr
				JOIN (SELECT *
					 FROM judging j1
					 JOIN (SELECT MAX(judgingid) AS newid
						   FROM judging
					       GROUP BY submitid) j2 
				     	 ON (j1.judgingid = j2.newid)) j
					 ON (j.judgingid = jr.judgingid AND j.valid=1 AND j.result = %s)
				JOIN submission s ON (j.submitid = s.submitid)
				JOIN contest c ON(c.cid=s.cid)
				JOIN team t ON(s.teamid=t.teamid)
				JOIN team_category tc ON (t.categoryid=tc.categoryid)
				JOIN language l ON (l.langid = s.langid)
				JOIN problem p ON (p.probid = s.probid)
				WHERE tc.sortorder < %i AND s.cid = %i AND s.valid = 1 AND LOWER(s.teamid) != %s
				AND s.probid = %s
				AND s.submittime < c.endtime
				AND s.submittime >= c.starttime
				AND s.langid = %s
				GROUP BY j.judgingid
				ORDER BY best_runtime ASC', 'correct', $sortOrder, $cid, 'domjudge', $_GET['probid'], $languageIdArray[$langCount]);
	
		$bestRuntimeNameArray = array();
		$bestRuntime1 = $bestRuntimeResult->next();
		if ($bestRuntime1['team_name'] == null) {
			$bestRuntime1 = 'None';
			array_push($bestRuntimeNameArray, 'None');
		}
		else {
			array_push($bestRuntimeNameArray, $bestRuntime1['team_name']);
			$current = $bestRuntimeResult->next();
			while($current != null && $current['best_runtime'] === $bestRuntime1['best_runtime']) {
				if (!in_array($current['team_name'], $bestRuntimeNameArray)) {
					array_push($bestRuntimeNameArray, $current['team_name']);
				}
				$current = $bestRuntimeResult->next();
			}
			$bestRuntime1 = $bestRuntime1['best_runtime'];
		}
		//format bestRuntimeName for display
		$bestRuntimeName1 = implode(', ', $bestRuntimeNameArray);

		$dataArray[$langCount][7] = $bestRuntime1;
		$dataArray[$langCount][8] = $bestRuntimeName1;
	}
}

require(LIBWWWDIR . '/header.php');
echo "<h1>" . $title . "</h1>\n\n";

//php code to generate dropdownlist with all enabled languages
echo "Programming Language: ";
echo "<select id='language' onchange='setLanguage()'>";
if(empty($_GET['langid'])) {
	echo "<option selected value=''>Overall</option>";
	for ($i = 0; $i < count($languageIdArray); $i++) {
		echo "<option value='" . $languageIdArray[$i] . "'>". $languageNameArray[$i] ."</option>";
	}
}
else {
	echo "<option value=''>Overall</option>";
	for ($i = 0; $i < count($languageIdArray); $i++) {
		if ($_GET['langid'] === $languageIdArray[$i]) {
			echo "<option selected value='" . $languageIdArray[$i] . "'>". $languageNameArray[$i] ."</option>";
		}
		else {
			echo "<option value='" . $languageIdArray[$i] . "'>". $languageNameArray[$i] ."</option>";
		}
	}
}

echo "</select>";

echo "<script>
function setLanguage() {

	var urlPathArray = window.location.pathname.split('/');
	var last = urlPathArray[urlPathArray.length-1];
	var parameters = window.location.search;
	var newPageName = last + '?';

	if (parameters.length > 0) {
		parameters = parameters.substring(1, parameters.length);
		if (parameters.indexOf('&') > -1) {
			parameters = parameters.split('&');
		}
		else {
			parameters = [parameters];
		}
		//we have to leave all parameters as is and just replace the lang parameter
		var newPageName = last + '?';
		var found = false;
	
		for (i = 0; i < parameters.length; i++) {
			if (parameters[i].substring(0, 7) === 'langid=') {
				parameters[i] = 'langid=' + document.getElementById('language').value;
				found = true;
			}
			newPageName += parameters[i];
			if (i < parameters.length-1) {
	 			newPageName += '&';
			}
		}
		if (found === false) {
			newPageName += '&langid=' + document.getElementById('language').value;
		}
	}
	else {
		newPageName += 'langid=' + document.getElementById('language').value;
	}
	
	var newPathName = '';
		for (i = 0; i < urlPathArray.length-1; i++) {
			newPathName += urlPathArray[i];
 			newPathName += '/';
		}
	newPathName += newPageName;
	var newUrl = window.location.protocol + '//' + window.location.host + newPathName;
	window.location.href = newUrl;

}

</script>";

// All problems
$problems = $DB->q('SELECT cp.probid AS probid, cp.shortname AS shortname, p.name AS name
					FROM problem p
					JOIN contestproblem cp ON(p.probid = cp.probid)
					JOIN contest c ON(cp.cid = c.cid)
					WHERE c.cid = %i', $cid);

print '<p>';
if(empty($_GET['langid'])) {
	print '<a href="statistics.php">All problems</a>&nbsp;&nbsp;&nbsp;';
}
else {
	print '<a href="statistics.php?langid=' . $_GET['langid'] . '">All problems</a>&nbsp;&nbsp;&nbsp;';
}
while($row = $problems->next()) {
	if(empty($_GET['langid'])) {
		print '<a title="' . $row['shortname']. '" href="statistics.php?probid=' . $row['probid'] . '">' . htmlspecialchars($row['name']) . '</a>&nbsp;&nbsp;&nbsp;';
	}
	else {
		print '<a title="' . $row['shortname']. '" href="statistics.php?probid=' . $row['probid'] . '&langid=' . $_GET['langid'] . '">' . htmlspecialchars($row['name']) . '</a>&nbsp;&nbsp;&nbsp;';
	}
}
print '</p>';

// Contest information
$start = $cdata['starttime'];
$end = $cdata['endtime'];
$length = ($end - $start) / 60;

?>


    <div id="placeholder" style="width:1000px;height:300px;"></div>

<script id="source">
	var data = <?= json_encode($res->gettable()); ?>;
	var contestlen = <?= $length; ?>;

$(function () {
		var answers = [{label : "correct", color : "#109D59", bars : { fill: 1 } },
		                {label : "wrong-answer", color : "#DC4437", bars : { fill: 1} },
				{label : "timelimit", color : "#FCBE1F", bars : { fill: 1} },
				{label : "run-error", color : "#4385F5", bars : { fill: 1} },
				{label : "compiler-error", color : "blue", bars : { fill: 1 }, },
				{label : "no-output", color : "purple", bars : { fill: 1 } }, ];
		var charts = [];
		for(var i = 0; i < answers.length; i++) {
			var cur = [];
			for(var j = 0; j < contestlen / <?= $bar_size ?>; j++)
				cur.push([1000*(<?php echo $start; ?>+60*(j * <?= $bar_size ?> + 0.1 * <?= $bar_size ?>)),0]);
			var answer = answers[i].label;
			for(var j = 0; j < data.length; j++) {
				if(data[j].result == answer) {
					cur[parseInt(data[j].minute) / <?= $bar_size ?>][1] = parseInt(data[j].count);
					
				}
			}
			var newchart = answers[i];
			newchart.data = cur;
			charts.push(newchart);
		}
		$.plot($("#placeholder"), charts, { 
			xaxis: {
			        min : 1000*<?php echo $start; ?>,
			        max : 1000* (<?php echo $start; ?>+60*contestlen),
			        mode: "time",
			        timeformat: "%b %d %H:%M",
			        monthNames: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"] },
			legend: { position : "nw"},
			series: {
				bars: { show: true, barWidth: <?= $bar_size * 0.9 ?>*1000*60, lineWidth : 0 },
				stack: 0
			}
		});
});
</script>
<?php
echo '<br><h2>LaTeX Statistics</h2>';
echo '\renewcommand{\statisticsStudents}{'.$students.'}<br><br>

	\renewcommand{\statisticsTriedOverall}{'.$tried.'}<br>
	\renewcommand{\statisticsSolvedOverall}{'.$solved.'}<br>
	\renewcommand{\statisticsSubmissionsOverall}{'.$submissions.'}<br>
	\renewcommand{\statisticsFastestOverall}{'.$fastest.'}<br>
	\renewcommand{\statisticsFastestNameOverall}{'.latexSpecialChars($fastestName).'}<br>
	\renewcommand{\statisticsBestRuntimeOverall}{'.ceil($bestRuntime).'}<br>
	\renewcommand{\statisticsBestRuntimeNameOverall}{'.latexSpecialChars($bestRuntimeName).'}<br>
	\renewcommand{\statisticsShortestOverall}{'.$shortestCode.'}<br>
	\renewcommand{\statisticsShortestNameOverall}{'.latexSpecialChars($shortestCodeName).'}<br><br>';

for ($i = 0; $i < count($languageIdArray); $i++) {
	$string1 = substr($languageIdArray[$i], 0, 1);
	$string2 = substr($languageIdArray[$i], 1, strlen($languageIdArray[$i]));
	$string1 = strtoupper($string1);
	$lang = $string1.$string2;

	echo '\renewcommand{\statisticsTried'.$lang.'}{'.$dataArray[$i][0].'}<br>
	\renewcommand{\statisticsSolved'.$lang.'}{'.$dataArray[$i][1].'}<br>
	\renewcommand{\statisticsSubmissions'.$lang.'}{'.$dataArray[$i][2].'}<br>
	\renewcommand{\statisticsFastest'.$lang.'}{'.$dataArray[$i][3].'}<br>
	\renewcommand{\statisticsFastestName'.$lang.'}{'.latexSpecialChars($dataArray[$i][4]).'}<br>
	\renewcommand{\statisticsBestRuntime'.$lang.'}{'.ceil($dataArray[$i][7]).'}<br>
	\renewcommand{\statisticsBestRuntimeName'.$lang.'}{'.latexSpecialChars($dataArray[$i][8]).'}<br>
	\renewcommand{\statisticsShortest'.$lang.'}{'.$dataArray[$i][5].'}<br>
	\renewcommand{\statisticsShortestName'.$lang.'}{'.latexSpecialChars($dataArray[$i][6]).'}<br><br>';
}

//http://stackoverflow.com/questions/2541616/how-to-escape-strip-special-characters-in-the-latex-document
function latexSpecialChars( $string ) {
	$map = array(
			"#"=>"\\#",
			"$"=>"\\$",
			"%"=>"\\%",
			"&"=>"\\&",
			"~"=>"\\~{}",
			"_"=>"\\_",
			"^"=>"\\^{}",
			"\\"=>"\\textbackslash{}",
			"{"=>"\\{",
			"}"=>"\\}",
	);
	return preg_replace( "/([\^\%~\\\\#\$%&_\{\}])/e", "\$map['$1']", $string );
}
?>
<?php
require(LIBWWWDIR . '/footer.php');
?>
