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
		return specialchars($difftext);
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
				$formdiffline = "<span class='correct'>".specialchars($diffline)."</span>";
				break;
			case ' ! ':
				$formdiffline = "<span class='differ'>".specialchars($diffline)."</span>";
				break;
			case ' $ ':
				$formdiffline = "<span class='endline'>".specialchars($diffline)."</span>";
				break;
			case ' > ':
			case ' < ':
				$formdiffline = "<span class='extra'>".specialchars($diffline)."</span>";
				break;
			default:
				$formdiffline = specialchars($diffline);
		}
		$return = $return . $linenostr . " " . $formdiffline . "\n";
		$line = strtok("\n");
	}
	return $return;
}

/**
 * Print a list of submissions from contests contained in the $cdatas
 * contest data array, either all or only those that match
 * <key> [= <value>] pairs specified in $restrictions:
 *  - 'verified'  if set, only list submissions that are verified
 *  - 'judged'    if set, only list submissions with completed judgings
 *  - 'teamid', 'probid', 'langid', 'categoryid', 'judgehost' can be
 *    set to an ID to filter on that respective team, language, etc.
 * Output is limited to the number $limit, or unlimited by default.
 * If $highlight is a submission ID, then that one is highlighted.
 */
function putSubmissions($cdatas, $restrictions, $limit = 0, $highlight = null)
{
	global $DB, $username;

	/* We need two kind of queries: one for all submissions, and one
	 * with the results for the valid ones.
	 */

	$cids = array_keys($cdatas);

	$verifyclause = '';
	if ( isset($restrictions['verified']) ) {
		if ( $restrictions['verified'] ) {
			$verifyclause = 'AND (j.verified = 1) ';
		} else {
			$verifyclause = 'AND (j.verified = 0 OR (j.verified IS NULL AND s.judgehost IS NULL)) ';
		}
	}
	$judgedclause = '';
	if ( isset($restrictions['judged']) ) {
		if ( $restrictions['judged'] ) {
			$judgedclause = 'AND (j.result IS NOT NULL) ';
		} else {
			$judgedclause = 'AND (j.result IS NULL) ';
		}
	}
	$rejudgingclause = '';
	if ( isset($restrictions['rejudgingdiff']) ) {
		if ( $restrictions['rejudgingdiff'] ) {
			$rejudgingclause = 'AND (j.result != jold.result) ';
		} else {
			$rejudgingclause = 'AND (j.result = jold.result) ';
		}
	}

	if ( isset($restrictions['old_result']) && !isset($restrictions['rejudgingid']) ) {
		error('cannot specify restriction on old_result without specifying a rejudgingid');
	}

	// Special case the rejudgingid restriction by showing the
	// corresponding judging and the old (active) judging result:
	$sqlbody =
	    'FROM submission s
	     LEFT JOIN team           t  USING (teamid)
	     LEFT JOIN problem        p  USING (probid)
	     LEFT JOIN contestproblem cp USING (probid, cid)
	     LEFT JOIN language       l  USING (langid) ' .
	    (isset($restrictions['rejudgingid']) ?
	    'LEFT JOIN judging        j    ON (s.submitid = j.submitid    AND j.rejudgingid = %i)
	     LEFT JOIN judging        jold ON (j.prevjudgingid IS NULL AND s.submitid = jold.submitid AND jold.valid = 1 OR j.prevjudgingid = jold.judgingid) ' :
	    'LEFT JOIN judging        j    ON (s.submitid = j.submitid    AND j.valid = 1) %_ ') .
	    'WHERE s.cid IN (%Ai) ' . $verifyclause . $judgedclause . $rejudgingclause .
	    (isset($restrictions['teamid'])      ? 'AND s.teamid = %i '      : '%_ ') .
	    (isset($restrictions['categoryid'])  ? 'AND t.categoryid = %i '  : '%_ ') .
	    (isset($restrictions['probid'])      ? 'AND s.probid = %i '      : '%_ ') .
	    (isset($restrictions['langid'])      ? 'AND s.langid = %s '      : '%_ ') .
	    (isset($restrictions['judgehost'])   ? 'AND s.judgehost = %s '   : '%_ ') .
	    (isset($restrictions['rejudgingid']) ? 'AND (s.rejudgingid = %i OR ' .
	                                           '     j.rejudgingid = %i) ' : '%_ %_ ') .
	    (isset($restrictions['old_result'])  ? 'AND jold.result = %s '   : '%_ ') .
	    (isset($restrictions['result'])      ? 'AND j.result = %s '   : '%_ ');

	// No contests; automatically nothing found and the query can not be run...
	if ( empty($cids) ) {
		echo "<p class=\"nodata\">No submissions</p>\n\n";
		return;
	}
	$res = $DB->q('SELECT s.submitid, s.teamid, s.probid, s.langid, s.cid,
	               s.submittime, s.judgehost, s.valid, t.name AS teamname,
	               cp.shortname, p.name AS probname, l.name AS langname,
	               j.result, j.judgehost, j.verified, j.jury_member, j.seen ' .
	              (isset($restrictions['rejudgingid']) ? ', jold.result AS oldresult ' : '') .
	              $sqlbody .
	              'ORDER BY s.submittime DESC, s.submitid DESC ' .
	              ($limit > 0 ? 'LIMIT 0, %i' : '%_'), @$restrictions['rejudgingid'], $cids,
	              @$restrictions['teamid'], @$restrictions['categoryid'],
	              @$restrictions['probid'], @$restrictions['langid'],
	              @$restrictions['judgehost'],
	              @$restrictions['rejudgingid'], @$restrictions['rejudgingid'],
	              @$restrictions['old_result'], @$restrictions['result'],
	              $limit);

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
		(IS_JURY && isset($restrictions['rejudgingid']) ?
		 "<th scope=\"col\">old result</th>" : '') .
		 "<th scope=\"col\"></th>".
		"</tr>\n</thead>\n<tbody>\n";

	// print each row with links to detailed information
	$iseven = $subcnt = $corcnt = $igncnt = $vercnt = $quecnt = 0;
	while( $row = $res->next() ) {

		$sid = (int)$row['submitid'];
		// always provide link if this is Jury. For team, provide link
		// to a different page, provided that the result is actually
		// present and valid.
		if ( IS_JURY ) {
			// If rejudging list, link to the new rejudging:
			$linkurl = 'submission.php?id=' . $sid .
			    (isset($restrictions['rejudgingid']) ?
			     '&amp;rejudgingid=' . $restrictions['rejudgingid'] : '');
			$link = ' href="' . $linkurl . '"';
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
				specialchars($row['teamid']) . '">' .
				"<a$link>" . specialchars(str_cut($row['teamname'],30)) . '</a></td>';
		}
		echo '<td class="probid" title="' . specialchars($row['probname']) . '">' .
			"<a$link>" . specialchars($row['shortname']) . '</a></td>';
		echo '<td class="langid" title="' . specialchars($row['langname']) . '">' .
			"<a$link>" . specialchars($row['langid']) . '</a></td>';
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
					$jury_member = specialchars($row['jury_member']);
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
				echo "<a class=\"button\" href=\"$linkurl&amp;claim=1\">claim</a>";
			} else {
				if ( !$row['verified'] && $jury_member==$username ) {
					echo "<a class=\"button\" href=\"$linkurl&amp;unclaim=1\">unclaim</a>";
				} else {
					echo "<a$link>$jury_member</a>";
				}
			}
			echo "</td>";
			if ( isset($restrictions['rejudgingid']) ) {
				echo "<td class=\"result\"><a href=\"submission.php?id=$sid\">" .
				    printresult($row['oldresult']) . "</a></td>";
			}
		}
		
		//Don't show sourcecode link for jury
		if ( !IS_JURY ) {
			 echo "<td><a href='show_source.php?id=$sid' title='show source code'><img src='../images/code.png'></a></td>";
		} else {
			echo "<td></td>";
		}
		
		echo "</tr>\n";

		if ( $row['result'] == 'correct' ) $corcnt++;
	}
	echo "</tbody>\n</table>\n\n";

	if ( IS_JURY ) {
		echo addEndForm();

		if ( $limit > 0 ) {
			$query_extras = array('subcnt' => '',
				'corcnt' => ' AND j.result LIKE \'correct\'',
				'igncnt' => ' AND s.valid = 0',
				'vercnt' => ' AND verified = 0 AND result IS NOT NULL',
				'quecnt' => ' AND result IS NULL');

			foreach ( $query_extras as $cnt => $query_extra ) {
				$$cnt = $DB->q('VALUE SELECT count(s.submitid) ' . $sqlbody . $query_extra,
				               @$restrictions['rejudgingid'], $cids,
				               @$restrictions['teamid'], @$restrictions['categoryid'],
				               @$restrictions['probid'], @$restrictions['langid'],
				               @$restrictions['judgehost'],
				               @$restrictions['rejudgingid'], @$restrictions['rejudgingid'],
				               @$restrictions['old_result'], @$restrictions['result']);
			}
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

	echo "<h1>Team ".specialchars($team['name'])."</h1>\n\n";

	if ( is_readable($teamimage) ) {
		echo '<img id="teampicture" src="' . $teamimage .
			'" alt="Picture of team ' .
			specialchars($team['name']) . '" />';
	}

?>

<table>
<tr><td>Name:    </td><td><?php echo specialchars($team['name'])?></td></tr>
<tr><td>Category:</td><td><?php echo specialchars($team['catname'])?></td></tr>
<?php

	if ( !empty($team['members']) ) {
		echo '<tr><td>Members:</td><td>' .
			nl2br(specialchars($team['members'])) . "</td></tr>\n";
	}

	if ( !empty($team['affilid']) ) {
		echo '<tr><td>Affiliation:</td><td>';
		echo specialchars($team['affname']);
		echo "</td></tr>\n";
		if ( !empty($team['country']) ) {
			echo '<tr><td>Country:</td><td>';
			if ( is_readable($countryflag) ) {
				echo '<img src="' . $countryflag . '" alt="' .
					specialchars($team['country']) . '" /> ';
			}
			echo specialchars($team['country']) . "</td></tr>\n";
		}
	}

	if ( !empty($team['room']) ) {
		echo '<tr><td>Location:</td><td>' .
			specialchars($team['room']) . "</td></tr>\n";
	}

	echo "</table>\n\n";
}

/**
 * Output clock
 */
function putClock() {
	global $cdata, $username;
	global $cid, $cdatas;
	global $DB, $userdata, $refresh;

	echo addForm('change_contest.php', 'get', 'selectcontestform');
	echo addHidden('cid', $cid);
	echo addEndForm();
	echo "<script type=\"text/javascript\">
	      function chooseContest(cid) {
	        document.getElementById('selectcontestform').cid.value = cid;
	        document.getElementById('selectcontestform').submit();
              };
              </script>";
	echo "<li class=\"dropdown\">";
	echo "<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\"><span class=\"glyphicon glyphicon-flag\"></span> contests <span class=\"caret\"></span></a>";
	echo "<ul class=\"dropdown-menu\" role=\"menu\">";

        $current_contests = array();
        $future_contests = array();
        $past_contests = array();
        foreach(array_reverse($cdatas, true) AS $tcid => $contest) {
                if($contest['starttime'] < time() && $contest['endtime'] > time()) {
                        $current_contests[$tcid] = $contest;
                }
                else if($contest['starttime'] > time()) {
                        $future_contests[$tcid] = $contest;
                }
                else {
                        $past_contests[$tcid] = $contest;
                }
        }
        if(count($current_contests) > 0) {
                echo "<li class='header'>Current Contests</li>";
                foreach($current_contests AS $tcid => $contest) {
                        echo "<li".($tcid == $cid ? ' class="active"' : '')."><a href=\"#\" onclick=\"javascript: chooseContest(".$tcid.");\">".$contest['name']."</a></li>";
                }
        }
        if(count($future_contests) > 0) {
                echo "<li class='header'>Future Contests</li>";
                foreach($future_contests AS $tcid => $contest) {
                        echo "<li".($tcid == $cid ? ' class="active"' : '')."><a href=\"#\" onclick=\"javascript: chooseContest(".$tcid.");\">".$contest['name']."</a></li>";
                }
        }
        if(count($past_contests) > 0) {
                echo "<li class='header'>Past Contests</li>";
                foreach($past_contests AS $tcid => $contest) {
                        echo "<li".($tcid == $cid ? ' class="active"' : '')."><a href=\"#\" onclick=\"javascript: chooseContest(".$tcid.");\">".$contest['name']."</a></li>";
                }
        }

	echo "</ul></li>";


	if ( logged_in() ) {
		$team = $DB->q('MAYBETUPLE SELECT a.country FROM team t
	                LEFT JOIN team_affiliation a ON (t.affilid = a.affilid)
	                WHERE teamid = %i', $userdata['teamid']);
		$usericon = "../images/countries/" . urlencode($team['country']) . ".png";

		echo "<li class=\"dropdown\">";
		echo "<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\"><img style=\"position: relative; top: -2px; height: 17px;\" src=\"".$usericon."\" />".$username."<span class=\"caret\"></span></a>";
		echo "<ul class=\"dropdown-menu\" role=\"menu\">";

		if(checkrole('jury') || checkrole('balloon')) {
			echo "<li><a href=\"../public/\">public area</a></li>";
			if(checkrole('team')) {
				echo "<li><a href=\"../team/\">team area</a></li>";
			}
			echo "<li><a href=\"../jury/\">jury area</a></li>";
		}
		if(IS_JURY) {
			$notify_flag  =  isset($_COOKIE["domjudge_notify"])  && (bool)$_COOKIE["domjudge_notify"];
			$refresh_flag = !isset($_COOKIE["domjudge_refresh"]) || (bool)$_COOKIE["domjudge_refresh"];
			echo addForm('toggle_notify.php', 'get', 'notify').addHidden('enable', ($notify_flag ? 0 : 1)).addEndForm();
			if(isset($refresh)) {
                        	echo addForm('toggle_refresh.php', 'get', 'toggles').addHidden('enable', ($refresh_flag ? 0 : 1)).addEndForm();
                        }
			echo "<li id=\"notify_li\" style=\"display: none\">";
                        if($notify_flag) {
                        	echo "<a href=\"#\" onclick=\"toggleNotifications(false); document.getElementById('notify').submit();\">disable notifications</a>";
                        } else {
                        	echo "<a href=\"#\" onclick=\"toggleNotifications(true); document.getElementById('notify').submit();\">enable notifications</a>";
			}
                        echo "</li>";
                        if(isset($refresh)) {
				if($refresh_flag) {
					echo "<li><a href=\"#\" onclick=\"document.getElementById('toggles').submit();\">disable refresh</a></li>";
				}
				else {
					echo "<li><a href=\"#\" onclick=\"document.getElementById('toggles').submit();\">enable refresh</a></li>";
				}
			}
		}
		if(have_logout()) {
			echo "<li><a href=\"../auth/logout.php\">logout</a></li>";
		}
               	echo "</ul></li>";
	}
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
	                AND probid = %i AND cid = %i", $probid, $cdata['cid']);

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
		               FROM problem INNER JOIN contestproblem USING (probid)
		               WHERE cid = %i AND allow_submit = 1 AND
		               problemtext_type IS NOT NULL ORDER BY shortname', $cid);

		if ( $res->count() > 0 ) {
			echo "<ul>\n";
			while($row = $res->next()) {
				print '<li> ' .
				      '<img src="../images/' . urlencode($row['problemtext_type']) .
				      '.png" alt="' . specialchars($row['problemtext_type']) .
				      '" /> <a href="problem.php?id=' . urlencode($row['probid']) . '">' .
				      'Problem ' . specialchars($row['shortname']) . ': ' .
				      specialchars($row['name']) . "</a></li>\n";
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
