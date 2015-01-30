<?php
/**
 * Common functions shared between team/public/jury interface
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

/** Text symbol used in output to represent a circle */
define('CIRCLE_SYM', '&#9679;');

function parseRunDiff($difftext){
	$line = strtok($difftext,"\n"); //first line
	if(sscanf($line, "### DIFFERENCES FROM LINE %d ###\n", $firstdiff) != 1)
		return htmlspecialchars($difftext);
	$return = $line . "\n";

	// Add second line 'team ? reference'
	$line = strtok("\n");
	$return .= $line . "\n";

	// We determine the line number width from the '_' characters and
	// the separator position from the character '?' on the second line.
	$linenowidth = mb_strrpos($line, '_') + 1;
	$midloc = mb_strpos($line, '?') - ($linenowidth+1);

	$line = strtok("\n");
	while(mb_strlen($line) != 0){
		$linenostr = mb_substr($line, 0, $linenowidth);
		$diffline = mb_substr($line, $linenowidth+1);
		$mid = mb_substr($diffline, $midloc-1, 3);
		switch($mid){
			case ' = ':
				$formdiffline = "<span class='correct'>".htmlspecialchars($diffline)."</span>";
				break;
			case ' ! ':
				$formdiffline = "<span class='differ'>".htmlspecialchars($diffline)."</span>";
				break;
			case ' $ ':
				$formdiffline = "<span class='endline'>".htmlspecialchars($diffline)."</span>";
				break;
			case ' > ':
			case ' < ':
				$formdiffline = "<span class='extra'>".htmlspecialchars($diffline)."</span>";
				break;
			default:
				$formdiffline = htmlspecialchars($diffline);
		}
		$return = $return . $linenostr . " " . $formdiffline . "\n";
		$line = strtok("\n");
	}
	return $return;
}

/**
 * Print a list of submissions, either all or only those that
 * match <key> = <value>. Output is always limited to the
 * current or last contest.
 */
function putSubmissions($cdatas, $restrictions, $limit = 0, $highlight = null)
{
	global $DB, $username;

	/* We need two kind of queries: one for all submissions, and one
	 * with the results for the valid ones. Restrictions is an array
	 * of key/value pairs, to which the complete list of submissions
	 * is restricted.
	 */

	$cids = array_keys($cdatas);

	if ( isset($restrictions['verified']) ) {
		if ( $restrictions['verified'] ) {
			$verifyclause = '(j.verified = 1) ';
		} else {
			$verifyclause = '(j.verified = 0 OR (j.verified IS NULL AND s.judgehost IS NULL)) ';
		}
	}
	if ( isset($restrictions['judged']) ) {
		if ( $restrictions['judged'] ) {
			$judgedclause = '(j.result IS NOT NULL) ';
		} else {
			$judgedclause = '(j.result IS NULL) ';
		}
	}

	$sqlbody =
		'FROM submission s
		 LEFT JOIN team           t  USING (teamid)
		 LEFT JOIN problem        p  USING (probid)
		 LEFT JOIN contestproblem cp USING (probid, cid)
		 LEFT JOIN language       l  USING (langid)
		 LEFT JOIN judging        j  ON (s.submitid = j.submitid AND j.valid=1)
		 WHERE s.cid IN %Ai ' .
	    (isset($restrictions['teamid'])    ? 'AND s.teamid = %i '    : '%_') .
	    (isset($restrictions['categoryid'])? 'AND t.categoryid = %i ': '%_') .
	    (isset($restrictions['probid'])    ? 'AND s.probid = %i '    : '%_') .
	    (isset($restrictions['langid'])    ? 'AND s.langid = %s '    : '%_') .
	    (isset($restrictions['judgehost']) ? 'AND s.judgehost = %s ' : '%_') ;

	$res = $DB->q('SELECT s.submitid, s.teamid, s.probid, s.langid, s.cid,
	               s.submittime, s.judgehost, s.valid, t.name AS teamname,
	               cp.shortname, p.name AS probname, l.name AS langname,
	               j.result, j.judgehost, j.verified, j.jury_member, j.seen ' .
	              $sqlbody .
	              (isset($restrictions['verified']) ? 'AND ' . $verifyclause : '') .
	              (isset($restrictions['judged'])   ? 'AND ' . $judgedclause : '') .
	              'ORDER BY s.submittime DESC, s.submitid DESC ' .
	              ($limit > 0 ? 'LIMIT 0, %i' : '%_'), $cids,
	              @$restrictions['teamid'], @$restrictions['categoryid'],
	              @$restrictions['probid'], @$restrictions['langid'],
	              @$restrictions['judgehost'], $limit);

	// nothing found...
	if( $res->count() == 0 ) {
		echo "<p class=\"nodata\">No submissions</p>\n\n";
		return;
	}

	if ( IS_JURY ) {
		echo addForm('submission.php');
	}

	// print the table with the submissions.
	// table header
	echo "<table class=\"list sortable\">\n<thead>\n<tr>" .

		(IS_JURY ? "<th scope=\"col\" class=\"sorttable_numeric\">ID</th>" : '') .
		(IS_JURY && count($cids) > 1 ? "<th scope=\"col\" class=\"sorttable_numeric\">contest</th>" : '') .
		"<th scope=\"col\">time</th>" .
		(IS_JURY ? "<th scope=\"col\">team</th>" : '') .
		"<th scope=\"col\">problem</th>" .
		"<th scope=\"col\">lang</th>" .
		"<th scope=\"col\">result</th>" .
		(IS_JURY ? "<th scope=\"col\">verified</th><th scope=\"col\">by</th>" : '') .

		"</tr>\n</thead>\n<tbody>\n";

	// print each row with links to detailed information
	$iseven = $subcnt = $corcnt = $igncnt = $vercnt = $quecnt = 0;
	while( $row = $res->next() ) {

		$sid = (int)$row['submitid'];
		// always provide link if this is Jury. For team, provide link
		// to a different page, provided that the result is actually
		// present and valid.
		if ( IS_JURY ) {
			$link = ' href="submission.php?id=' . $sid . '"';
		} elseif ( $row['submittime'] < $cdatas[$row['cid']]['endtime'] &&
		           $row['result'] && $row['valid'] &&
		           (!dbconfig_get('verification_required',0) || $row['verified']) ) {
			$link = ' href="submission_details.php?id=' . $sid . '"';
		} else {
			$link = '';
		}

		echo "<tr class=\"" .
			( $iseven ? 'roweven': 'rowodd' );
		$iseven = !$iseven;

		if ( $row['valid'] ) {
			$subcnt++;
		} else {
			$igncnt++;
			echo ' sub_ignore';
		}
		if ( $sid == $highlight ) {
			echo ' highlight';
		}
		if (!IS_JURY && !$row['seen'] ) {
			echo ' unseen';
		}
		echo '">';

		if ( IS_JURY ) {
			echo "<td><a$link>s$sid</a></td>";
		}
		if ( IS_JURY && count($cids) > 1 ) {
			echo "<td><a$link>c${row['cid']}</a></td>";
		}
		echo "<td><a$link>" . printtime($row['submittime']) . "</a></td>";
		if ( IS_JURY ) {
			echo '<td title="t' .
				htmlspecialchars($row['teamid']) . '">' .
				"<a$link>" . htmlspecialchars(str_cut($row['teamname'],30)) . '</a></td>';
		}
		echo '<td class="probid" title="' . htmlspecialchars($row['probname']) . '">' .
			"<a$link>" . htmlspecialchars($row['shortname']) . '</a></td>';
		echo '<td class="langid" title="' . htmlspecialchars($row['langname']) . '">' .
			"<a$link>" . htmlspecialchars($row['langid']) . '</a></td>';
		echo "<td class=\"result\"><a$link>";
		if ( difftime($row['submittime'],$cdatas[$row['cid']]['endtime']) >= 0 ) {
			echo printresult('too-late');
			if ( IS_JURY && $row['result'] ) {
				echo " (" . printresult($row['result']) . ")";
			}
		} else if ( ! $row['result'] ||
		            ( !IS_JURY && ! $row['verified'] &&
		              dbconfig_get('verification_required', 0) ) ) {
			echo printresult($row['judgehost'] || !IS_JURY ? '' : 'queued');
		} else {
			echo printresult($row['result']);
		}
		echo "</a></td>";

		if ( IS_JURY ) {
			// only display verification if we're done with judging
			unset($verified, $jury_member);
			$claim = FALSE;
			if ( empty($row['result']) ) {
				$verified = '&nbsp;';
				$jury_member = '&nbsp;';
			} else {
				$verified = printyn($row['verified']);
				if ( empty($row['jury_member']) ) {
					$jury_member = '&nbsp;';
				} else {
					$jury_member = htmlspecialchars($row['jury_member']);
				}
				if ( !$row['verified'] ) {
					$vercnt++;
					if ( empty($row['jury_member']) ) {
						$claim = TRUE;
					} else {
						$verified = 'claimed';
					}
				}
			}

			echo "<td><a$link>$verified</a></td><td>";
			if ( $claim ) {
				echo "<a class=\"button\" href=\"submission.php?claim=1&amp;id=" .
					htmlspecialchars($row['submitid']) . "\">claim</a>";
			} else {
				if ( !$row['verified'] && $jury_member==$username ) {
					echo "<a class=\"button\" href=\"submission.php?unclaim=1&amp;id=" .
						htmlspecialchars($row['submitid']) . "\">unclaim</a>";
				} else {
					echo "<a$link>$jury_member</a>";
				}
			}
			echo "</td>";
		}
		echo "</tr>\n";

		if ( $row['result'] == 'correct' ) $corcnt++;
	}
	echo "</tbody>\n</table>\n\n";

	if ( IS_JURY ) {
		echo addEndForm();

		if ( $limit > 0 ) {
			$subcnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody, $cids,
			                 @$restrictions['teamid'], @$restrictions['categoryid'],
			                 @$restrictions['probid'], @$restrictions['langid'],
			                 @$restrictions['judgehost']);
			$corcnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody .
					 ' AND j.result LIKE \'correct\'', $cids,
			                 @$restrictions['teamid'], @$restrictions['categoryid'],
			                 @$restrictions['probid'], @$restrictions['langid'],
			                 @$restrictions['judgehost']);
			$igncnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody .
					 ' AND s.valid = 0', $cids,
			                 @$restrictions['teamid'], @$restrictions['categoryid'],
			                 @$restrictions['probid'], @$restrictions['langid'],
			                 @$restrictions['judgehost']);
			$vercnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody .
					 ' AND verified = 0 AND result IS NOT NULL', $cids,
			                 @$restrictions['teamid'], @$restrictions['categoryid'],
			                 @$restrictions['probid'], @$restrictions['langid'],
			                 @$restrictions['judgehost']);
			$quecnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody .
					 ' AND result IS NULL', $cids,
			                 @$restrictions['teamid'], @$restrictions['categoryid'],
			                 @$restrictions['probid'], @$restrictions['langid'],
			                 @$restrictions['judgehost']);
		}
		echo "<p>Total correct: $corcnt, submitted: $subcnt";
		if ( $vercnt > 0 ) echo ", unverified: $vercnt";
		if ( $igncnt > 0 ) echo ", ignored: $igncnt";
		if ( $quecnt > 0 ) echo ", judgement pending: $quecnt";
		echo "</p>\n\n";
	}

	return;
}

/**
 * Output team information (for team and public interface)
 */
function putTeam($teamid) {

	global $DB;

	$team = $DB->q('MAYBETUPLE SELECT t.*, c.name AS catname,
	                a.name AS affname, a.country FROM team t
	                LEFT JOIN team_category c USING (categoryid)
	                LEFT JOIN team_affiliation a ON (t.affilid = a.affilid)
	                WHERE teamid = %i', $teamid);

	if ( empty($team) ) error ("No team found by this id.");

	$countryflag = "../images/countries/" . urlencode($team['country']) . ".png";
	$teamimage = "../images/teams/" . urlencode($team['teamid']) . ".jpg";

	echo "<h1>Team ".htmlspecialchars($team['name'])."</h1>\n\n";

	if ( is_readable($teamimage) ) {
		echo '<img id="teampicture" src="' . $teamimage .
			'" alt="Picture of team ' .
			htmlspecialchars($team['name']) . '" />';
	}

?>

<table>
<tr><td>Name:    </td><td><?php echo htmlspecialchars($team['name'])?></td></tr>
<tr><td>Category:</td><td><?php echo htmlspecialchars($team['catname'])?></td></tr>
<?php

	if ( !empty($team['members']) ) {
		echo '<tr><td>Members:</td><td>' .
			nl2br(htmlspecialchars($team['members'])) . "</td></tr>\n";
	}

	if ( !empty($team['affilid']) ) {
		echo '<tr><td>Affiliation:</td><td>';
		echo htmlspecialchars($team['affname']);
		echo "</td></tr>\n";
		if ( !empty($team['country']) ) {
			echo '<tr><td>Country:</td><td>';
			if ( is_readable($countryflag) ) {
				echo '<img src="' . $countryflag . '" alt="' .
					htmlspecialchars($team['country']) . '" /> ';
			}
			echo htmlspecialchars($team['country']) . "</td></tr>\n";
		}
	}

	if ( !empty($team['room']) ) {
		echo '<tr><td>Location:</td><td>' .
			htmlspecialchars($team['room']) . "</td></tr>\n";
	}

	echo "</table>\n\n";
}

/**
 * Output clock
 */
function putClock() {
	global $cdata, $username;
	global $cid, $cdatas;
	// Show a contest selection form, if there are contests
	if ( count($cdatas) > 1 ) {
		echo addForm('change_contest.php', 'get', 'selectcontestform');
		$contests = array_reverse(array_map(function($c) { return $c['contestname']; }, $cdatas), true);
		echo addHidden('cid', $cid);
		echo addEndForm();
		echo "<script type=\"text/javascript\">
		      function chooseContest(cid) {
		        document.getElementById('selectcontestform').cid.value = cid;
		        document.getElementById('selectcontestform').submit();
	              };
                      </script>";
		echo "<ul class=\"nav navbar-nav navbar-right\">";
		echo "<li class=\"dropdown\">";
		echo "<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\">contest <span class=\"caret\"></span></a>";
		echo "<ul class=\"dropdown-menu\" role=\"menu\">";
		foreach($contests AS $tcid => $tcname) {
			echo "<li".($tcid == $cid ? ' class="active"' : '')."><a href=\"#\" onclick=\"javascript: chooseContest(".$tcid.");\">".$tcname."</a></li>";
		}
		echo "</ul>";
		echo "</ul>";
	} elseif ( count($cdatas) == 1 && IS_JURY ) {
		echo "<p class=\"navbar-text navbar-right\">\n";
		$contest = $cdatas[$cid];
		echo 'contest: ' . $contest['shortname'];
		echo "</p>\n";
	}

	// timediff to end of contest
	if ( difftime(now(), $cdata['starttime']) >= 0 &&
	     difftime(now(), $cdata['endtime'])   <  0 ) {
		$left = "" . printtimediff(now(),$cdata['endtime']);
	} else if ( difftime(now(), $cdata['activatetime']) >= 0 &&
	            difftime(now(), $cdata['starttime'])    <  0 ) {
		$left = "(to start)" . printtimediff(now(),$cdata['starttime']);
	} else {
		$left = "";
	}
	echo "<p id=\"timeleft\" class=\"navbar-text navbar-right\"><span class=\"glyphicon glyphicon-time\"></span>" . $left . "</p>";

	if ( logged_in() ) {
		echo "<p id=\"username\" class=\"navbar-text navbar-right\"><span class=\"glyphicon glyphicon-user\"></span> " . $username
			. ( have_logout() ? " <a href=\"../auth/logout.php\">×</a>" : "" )
			. "</p>";
	}


	echo "<script type=\"text/javascript\">
	var initial = " . time() . ";
	var activatetime = " . $cdata['activatetime'] . ";
	var starttime = " . $cdata['starttime'] . ";
	var endtime = " . $cdata['endtime'] . ";
	var offset = 0;
	var date = new Date(initial*1000);
	var timeleftelt = document.getElementById(\"timeleft\");

	setInterval(function(){updateClock();},1000);
	updateClock();
</script>\n";
}

/**
 * Output a footer for pages containing the DOMjudge version and server host/port/time
 */
function putDOMjudgeVersion() {
	echo "<hr /><address>DOMjudge/" . DOMJUDGE_VERSION .
		" at ".$_SERVER['SERVER_NAME']." Port ".$_SERVER['SERVER_PORT'].", page generated <span id=\"timecur\">" . strftime('%a %d %b %Y %T %Z') . "</span></address>\n";
}

/**
 * Check whether the logged in user has DOMjudge administrator level,
 * as defined in passwords.php. If not, error and stop further execution.
 */
function requireAdmin() {
	if (!checkrole('admin')) {
		error("This function is only accessible to administrators.");
	}
}

/**
 * Translate error codes from PHP's file upload function into
 * concrete error strings.
 */
function checkFileUpload($errorcode) {
	switch ( $errorcode ) {
		case UPLOAD_ERR_OK: // everything ok!
			return;
		case UPLOAD_ERR_INI_SIZE:
			error('The uploaded file is too large (exceeds the upload_max_filesize directive).');
		case UPLOAD_ERR_FORM_SIZE:
			error('The uploaded file is too large (exceeds the MAX_FILE_SIZE directive).');
		case UPLOAD_ERR_PARTIAL:
			error('The uploaded file was only partially uploaded.');
		case UPLOAD_ERR_NO_FILE:
			error('No file was uploaded.');
		case UPLOAD_ERR_NO_TMP_DIR:
			error('Missing a temporary folder. Contact staff.');
		case UPLOAD_ERR_CANT_WRITE:
			error('Failed to write file to disk. Contact staff.');
		case UPLOAD_ERR_EXTENSION:
			error('File upload stopped by extension. Contact staff.');
		default:
			error('Unknown error while uploading: '. $_FILES['code']['error'] .
				'. Contact staff.');
	}
}

/**
 * Outputs a problem description text, either as download or inline.
 * It is assumed that the headers have not been sent yet, and this
 * function terminates the PHP script execution.
 */
function putProblemText($probid)
{
	global $DB, $cdata;

	$prob = $DB->q("MAYBETUPLE SELECT cid, shortname, problemtext, problemtext_type
			FROM problem INNER JOIN contestproblem USING (probid)
			WHERE OCTET_LENGTH(problemtext) > 0
			AND probid = %i
			AND cid = %i", $probid, $cdata['cid']);

	if ( empty($prob) ||
	     !(IS_JURY ||
	       ($prob['cid']==$cdata['cid'] && difftime($cdata['starttime'],now())<=0)) ) {
		error("Problem p$probid not found or not available");
	}

	switch ( $prob['problemtext_type'] ) {
	case 'pdf':
		$mimetype = 'application/pdf';
		break;
	case 'html':
		$mimetype = 'text/html';
		break;
	case 'txt':
		$mimetype = 'text/plain';
		break;
	default:
		error("Problem p$probid text has unknown type");
	}


	$filename = "prob-$prob[shortname].$prob[problemtext_type]";

	header("Content-Type: $mimetype; name=\"$filename\"");
	header("Content-Disposition: inline; filename=\"$filename\"");
	header("Content-Length: " . strlen($prob['problemtext']));

	echo $prob['problemtext'];

	exit(0);
}

/**
 * Outputs bulleted list of problem statements for this contest
 */
function putProblemTextList()
{
	global $cid, $cdata, $DB;
	$fdata = calcFreezeData($cdata);

	if ( ! have_problemtexts() ) {
		echo "<p class=\"nodata\">No problem texts available for this contest.</p>\n\n";
	} elseif ( !$fdata['cstarted'] ) {
		echo "<p class=\"nodata\">Problem texts will appear here at contest start.</p>\n\n";
	} else {

		// otherwise, display list
		$res = $DB->q('SELECT probid,shortname,name,color,problemtext_type
			       FROM problem INNER JOIN contestproblem USING (probid) WHERE cid = %i AND allow_submit = 1 AND
			       problemtext_type IS NOT NULL ORDER BY shortname', $cid);

		if ( $res->count() > 0 ) {
			echo "<ul>\n";
			while($row = $res->next()) {
				print '<li> ' .
				      '<img src="../images/' . urlencode($row['problemtext_type']) .
				      '.png" alt="' . htmlspecialchars($row['problemtext_type']) .
				      '" /> <a href="problem.php?id=' . urlencode($row['probid']) . '">' .
				      'Problem ' . htmlspecialchars($row['shortname']) . ': ' .
				      htmlspecialchars($row['name']) . "</a></li>\n";
			}
			echo "</ul>\n";
		}
	}
}

/**
 * Returns true if at least one problem in the current contest has a
 * problem statement text in the database.
 */
function have_problemtexts()
{
	global $DB, $cid;
	return $DB->q('VALUE SELECT COUNT(*) FROM problem
		       INNER JOIN contestproblem USING (probid)
		       WHERE problemtext_type IS NOT NULL
		       AND cid = %i', $cid) > 0;
}

/**
 * Maps domjudge language id to Ace language id
 */
function langidToAce($langid) {
	switch ($langid) {
	case 'c':
	case 'cpp':
	case 'cxx':
		return 'c_cpp';
	case 'pas':
		return 'pascal';
	case 'hs':
		return 'haskell';
	case 'pl':
		return 'perl';
	case 'bash':
		return 'sh';
	case 'py2':
	case 'py3':
		return 'python';
	case 'adb':
		return 'ada';
	case 'plg':
		return 'prolog';
	case 'rb':
		return 'ruby';
	}
	return $langid;
}

/**
 * Output JavaScript function that contains the language extensions as
 * configured in the database so the frontend can use them to automatically
 * detect the language from the filename extension.
 */
function putgetMainExtension($langdata) {
	echo "function getMainExtension(ext)\n{\n";
	echo "\tswitch(ext) {\n";
	foreach ( $langdata as $langid => $langdata ) {
		$exts = json_decode($langdata['extensions']);
		if ( !is_array($exts) ) continue;
		foreach ( $exts as $ext ) {
			echo "\t\tcase '" . $ext . "': return '" . $langid . "';\n";
		}
	}
	echo "\t\tdefault: return '';\n\t}\n}\n\n";
}
