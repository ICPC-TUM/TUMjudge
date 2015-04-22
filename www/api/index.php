<?php
/**
 * DOMjudge public REST API
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');



function infreeze($cdata, $time)
{

	if ( ( ! empty($cdata['freezetime']) &&
		difftime($time, $cdata['freezetime'])>0 ) &&
		( empty($cdata['unfreezetime']) ||
		difftime($time, $cdata['unfreezetime'])<=0 ) ) return TRUE;
	return FALSE;
}

function checkargs($args, $mandatory)
{
	global $api;

	foreach ( $mandatory as $arg ) {
		if ( !isset($args[$arg]) ) {
			$api->createError("argument '$arg' is mandatory");
		}
	}
}

$api = new RestApi();

/**
 * API information
 */
function info()
{
	return array('api_version' => DOMJUDGE_API_VERSION,
	             'domjudge_version' => DOMJUDGE_VERSION);
}
$doc = "Get general API information.";
$api->provideFunction('GET', 'info', $doc);


/**
 * Statistics
 */
function statistics()
{
        global $DB;
        return array(
                 'submissions' => array_pop($DB->q('SELECT COUNT(*) FROM submission WHERE valid = 1')->next()),
                 'contests' => array_pop($DB->q('SELECT COUNT(*) FROM contest WHERE starttime < UNIX_TIMESTAMP(NOW())')->next()),
                 'teams' => array_pop($DB->q('SELECT COUNT(DISTINCT teamid) FROM submission WHERE valid = 1')->next()),
                 'participations' => array_pop($DB->q('SELECT COUNT(DISTINCT teamid, cid) FROM submission WHERE valid = 1')->next())
        );
}
$doc = "get some statistics on the number of contests, submissions and participants.";
$api->provideFunction('GET', 'statistics', $doc);

/**
 * Contest information
 */
function contest()
{
	global $cids, $cdatas, $userdata;

	if ( checkrole('jury') ) {
		$cdatas = getCurContests(TRUE);
	} elseif ( isset($userdata['teamid']) ) {
		$cdatas = getCurContests(TRUE, $userdata['teamid']);
	}

	if (empty($cdatas)) {
		return null;
	}

	$cid = $cids[0];
	$cdata = $cdatas[$cid];
	return array(
		'id'        => $cid,
		'shortname' => $cdata['shortname'],
		'name'      => $cdata['contestname'],
		'start'     => $cdata['starttime'],
		'freeze'    => $cdata['freezetime'],
		'end'       => $cdata['endtime'],
		'length'    => $cdata['endtime'] - $cdata['starttime'],
		'unfreeze'  => $cdata['unfreezetime'],
		'penalty'   => 60*dbconfig_get('penalty_time', 20),
		);
}
$doc = "Get information about the current contest: id, shortname, name, start, freeze, unfreeze, length, penalty and end.";
$doc .= "If more than one contest is active, return information about the first one";
$api->provideFunction('GET', 'contest', $doc);


/**
 * Contests information
 */
function contests()
{
	global $cdatas, $userdata;

	if ( checkrole('jury') ) {
		$cdatas = getCurContests(TRUE);
	} elseif ( isset($userdata['teamid']) ) {
		$cdatas = getCurContests(TRUE, $userdata['teamid']);
	}

	return array_map(function($cdata) {
		return array(
			'id' => $cdata['cid'],
			'shortname' => $cdata['shortname'],
			'name' => $cdata['contestname'],
			'start' => $cdata['starttime'],
			'freeze' => $cdata['freezetime'],
			'end' => $cdata['endtime'],
			'length' => $cdata['endtime'] - $cdata['starttime'],
			'unfreeze' => $cdata['unfreezetime'],
			'penalty' => 60 * dbconfig_get('penalty_time', 20),
		);
	}, $cdatas);
}
$doc = "Get information about all the current contests: id, shortname, name, start, freeze, unfreeze, length, penalty and end.";
$api->provideFunction('GET', 'contests', $doc);

/**
 * Get information about the current user
 */
function user()
{
	global $userdata;

	$return = array(
		'id'       => $userdata['userid'],
		'teamid'   => $userdata['teamid'],
		'email'    => $userdata['email'],
		'ip'       => $userdata['ip_address'],
		'lastip'   => $userdata['last_ip_address'],
		'name'     => $userdata['name'],
		'username' => $userdata['username'],
		'roles'    => $userdata['roles'],
	);
	return $return;
}
$doc = "Get information about the currently logged in user. If no user is logged in, will return null for all values.";
$api->provideFunction('GET', 'user', $doc);

/**
 * Problems information
 */
function problems($args)
{
	global $DB;

	checkargs($args, array('cid'));

	$q = $DB->q('SELECT probid AS id, shortname, name, color FROM problem
		     INNER JOIN contestproblem USING (probid)
		     WHERE cid = %i AND allow_submit = 1 ORDER BY probid', $args['cid']);
	return $q->gettable();
}
$doc = "Get a list of problems in a contest, with for each problem: id, shortname, name and color.";
$args = array('cid' => 'Contest ID.');
$exArgs = array(array('cid' => 2));
$api->provideFunction('GET', 'problems', $doc, $args, $exArgs);

/**
 * Judgings information
 */
function judgings($args)
{
	global $DB;

	$query = 'SELECT submitid, judgingid, eventtime FROM event WHERE description = "problem judged"';

	$hasCid = array_key_exists('cid', $args);
	$query .= ($hasCid ? ' AND cid = %i' : ' AND TRUE %_');
	$cid = ($hasCid ? $args['cid'] : 0);

	$hasFromid = array_key_exists('fromid', $args);
	$query .= ($hasFromid ? ' AND judgingid >= %i' : ' AND TRUE %_');
	$fromId = ($hasFromid ? $args['fromid'] : 0);

	$hasJudgingid = array_key_exists('judgingid', $args);
	$query .= ($hasJudgingid ? ' AND judgingid = %i' : ' AND TRUE %_');
	$judgingid = ($hasJudgingid ? $args['judgingid'] : 0);

	$query .= ' ORDER BY eventid';

	$hasLimit = array_key_exists('limit', $args);
	$query .= ($hasLimit ? ' LIMIT %i' : ' %_');
	$limit = ($hasLimit ? $args['limit'] : -1);
	// TODO: validate limit

	$q = $DB->q($query, $cid, $fromId, $judgingid, $limit);
	$res = array();
	while ( $row = $q->next() ) {
		$data = $DB->q('MAYBETUPLE SELECT s.submittime, j.result FROM judging j
		                LEFT JOIN submission s USING (submitid)
		                WHERE j.judgingid = %i', $row['judgingid']);
		if ($data == NULL) continue;

		// This should be encoded directly in the query
		if ( array_key_exists('result', $args) &&
		     $args['result'] != $data['result'] ) continue;

		$res[] = array('id'         => $row['judgingid'],
		               'submission' => $row['submitid'],
		               'outcome'    => $data['result'],
		               'time'       => $row['eventtime']);
	}
	return $res;
}
$doc = 'Get all judgings (including those post-freeze, so currently limited to jury).';
$args = array('cid' => 'Contest ID. If not provided, get judgings of all active contests',
              'result' => 'Search only for judgings with a certain result.',
              'fromid' => 'Search from a certain ID',
              'judgingid' => 'Search only for a certain ID',
              'limit' => 'Get only the first N judgings');
$exArgs = array(array('cid' => 2), array('result' => 'correct'), array('fromid' => 800, 'limit' => 10));
$roles = array('jury');
$api->provideFunction('GET', 'judgings', $doc, $args, $exArgs, $roles);

function judgings_POST($args)
{
	global $DB, $api;

	checkargs($args, array('judgehost'));

	$host = $args['judgehost'];
	$DB->q('UPDATE judgehost SET polltime = %s WHERE hostname = %s', now(), $host);

	// If this judgehost is not active, there's nothing to do
	$active = $DB->q('MAYBEVALUE SELECT active FROM judgehost WHERE hostname = %s', $host);
	if ( !$active ) return '';

	$cdatas = getCurContests(TRUE);
	$cids = array_keys($cdatas);

	// Get judgehost restrictions
	$contests = array();
	$problems = array();
	$languages = array();
	$restrictions = $DB->q('MAYBEVALUE SELECT restrictions FROM judgehost
	                        INNER JOIN judgehost_restriction USING (restrictionid)
	                        WHERE hostname = %s', $host);
	if ( $restrictions ) {
		$restrictions = json_decode($restrictions, true);
		$contests = $restrictions['contest'];
		$problems = $restrictions['problem'];
		$languages = $restrictions['language'];
	}

	$extra = '';
	if ( empty($contests) ) {
		$extra .= '%_';
	} else {
		$extra .= 'AND s.cid IN %Ai ';
	}

	if ( empty($problems) ) {
		$extra .= '%_';
	} else {
		$extra .= 'AND s.probid IN %Ai ';
	}

	if ( empty($languages) ) {
		$extra .= '%_';
	} else {
		$extra .= 'AND s.langid IN %As ';
	}

	// Prioritize teams according to last judging time
	$submitid = $DB->q('MAYBEVALUE SELECT submitid
	                    FROM submission s
	                    LEFT JOIN team t USING (teamid)
	                    LEFT JOIN problem p USING (probid)
	                    LEFT JOIN language l USING (langid)
	                    LEFT JOIN contestproblem cp USING (probid, cid)
	                    WHERE judgehost IS NULL AND s.cid IN %Ai ' . $extra . '
	                    AND l.allow_judge = 1 AND cp.allow_judge = 1 AND valid = 1
	                    ORDER BY judging_last_started ASC, submittime ASC, submitid ASC
	                    LIMIT 1',
	                   $cids, $contests, $problems, $languages);

	if ( $submitid ) {
		// update exactly one submission with our judgehost name
		// Note: this might still return 0 if another judgehost beat
		// us to it
		$numupd = $DB->q('RETURNAFFECTED UPDATE submission
		                  SET judgehost = %s
		                  WHERE submitid = %i AND judgehost IS NULL',
		                  $host, $submitid);

		// TODO: a small optimisation could be made: if numupd=0 but
		// numopen > 1; not return but retry procudure again immediately
	}

	if ( empty($submitid) || $numupd == 0 ) return '';

	$row = $DB->q('TUPLE SELECT s.submitid, s.cid, s.teamid, s.probid, s.langid,
	               CEILING(time_factor*timelimit) AS maxruntime,
	               p.memlimit, p.outputlimit,
	               special_run AS run, special_compare AS compare,
	               compile_script
	               FROM submission s
	               LEFT JOIN problem p USING (probid)
	               LEFT JOIN language l USING (langid)
	               WHERE submitid = %i', $submitid);

	if(DOMSERVER_REPLICATION != 'slave') {
		$DB->q('UPDATE team SET judging_last_started = %s WHERE teamid = %i',
	       		now(), $row['teamid']);
	}

	if ( empty($row['memlimit']) ) {
		$row['memlimit'] = dbconfig_get('memory_limit');
	}
	if ( empty($row['outputlimit']) ) {
		$row['outputlimit'] = dbconfig_get('output_limit');
	}
	if ( empty($row['compare']) ) {
		$row['compare'] = dbconfig_get('default_compare');
	}
	if ( empty($row['run']) ) {
		$row['run'] = dbconfig_get('default_run');
	}
	// TODO: refactor + integrate in query above?
	$compare_md5sum = $DB->q('MAYBEVALUE SELECT md5sum FROM executable
	                          WHERE execid = %s', $row['compare']);
	$row['compare_md5sum'] = $compare_md5sum;
	$run_md5sum = $DB->q('MAYBEVALUE SELECT md5sum FROM executable
	                      WHERE execid = %s', $row['run']);
	$row['run_md5sum'] = $run_md5sum;
	if ( !empty($row['compile_script']) ) {
		$compile_script_md5sum = $DB->q('MAYBEVALUE SELECT md5sum FROM executable
		                                 WHERE execid = %s', $row['compile_script']);
		$row['compile_script_md5sum'] = $compile_script_md5sum;
	}

	$jid = $DB->q('RETURNID INSERT INTO judging (submitid,cid,starttime,judgehost)
	               VALUES(%i,%i,%s,%s)', $row['submitid'], $row['cid'], now(), $host);

	$row['judgingid'] = $jid;

	return $row;
}
$doc = 'Request a new judging to be judged.';
$args = array('judgehost' => 'Judging is to be judged by this specific judgehost.');
$exArgs = array();
$roles = array('judgehost');
$api->provideFunction('POST', 'judgings', $doc, $args, $exArgs, $roles);

function judgings_PUT($args)
{
	global $DB, $api;

	if ( !isset($args['__primary_key']) ) {
		$api->createError("judgingid is mandatory");
	}
	$judgingid = $args['__primary_key'];
	if ( !isset($args['judgehost']) ) {
		$api->createError("judgehost is mandatory");
	}

	if ( isset($args['output_compile']) ) {
		if ( $args['compile_success'] ) {
			$DB->q('UPDATE judging SET output_compile = %s ' .
				'WHERE judgingid = %i AND judgehost = %s',
				base64_decode($args['output_compile']),
				$judgingid, $args['judgehost']);
		} else {
			$DB->q('UPDATE judging SET output_compile = %s, ' .
				'result = "compiler-error", endtime=%s ' .
				'WHERE judgingid = %i AND judgehost = %s',
				base64_decode($args['output_compile']), now(),
				$judgingid, $args['judgehost']);
			$cid = $DB->q('VALUE SELECT s.cid FROM judging
			               LEFT JOIN submission s USING(submitid)
			               WHERE judgingid = %i', $judgingid);
			auditlog('judging', $judgingid, 'judged', 'compiler-error',
			         $args['judgehost'], $cid);

			$row = $DB->q('TUPLE SELECT s.cid, s.teamid, s.probid, s.langid, s.submitid
			               FROM judging
			               LEFT JOIN submission s USING(submitid)
			               WHERE judgingid = %i',$judgingid);
			calcScoreRow($row['cid'], $row['teamid'], $row['probid']);

			// log to event table if no verification required
			// (case of verification required is handled in www/jury/verify.php)
			if ( ! dbconfig_get('verification_required', 0) ) {
				$DB->q('INSERT INTO event (eventtime, cid, teamid, langid, probid,
				        submitid, judgingid, description)
				        VALUES(%s, %i, %i, %s, %i, %i, %i, "problem judged")',
				       now(), $row['cid'], $row['teamid'], $row['langid'],
				       $row['probid'], $row['submitid'], $judgingid);
			}
		}
	}

	$DB->q('UPDATE judgehost SET polltime = %s WHERE hostname = %s',
	       now(), $args['judgehost']);

	return '';
}
$doc = 'Update a judging.';
$args = array('judgingid' => 'Judging corresponds to this specific judgingid.',
	'judgehost' => 'Judging is judged by this specific judgehost.',
	'compile_success' => 'Did the compilation succeed?',
	'output_compile' => 'Ouput of compilation phase (base64 encoded).');
$exArgs = array();
$roles = array('judgehost');
$api->provideFunction('PUT', 'judgings', $doc, $args, $exArgs, $roles);

/**
 * Judging_Runs
 */
function judging_runs_POST($args)
{
	global $DB, $api;

	checkargs($args, array('judgingid', 'testcaseid', 'runresult', 'runtime',
	                       'output_run', 'output_diff', 'output_error', 'output_system', 'judgehost'));

	$results_remap = dbconfig_get('results_remap');
	$results_prio = dbconfig_get('results_prio');

	if ( array_key_exists($args['runresult'], $results_remap) ) {
		logmsg(LOG_INFO, "Testcase $args[testcaseid] remapping result " . $args['runresult'] .
		                 " -> " . $results_remap[$args['runresult']]);
		$args['runresult'] = $results_remap[$args['runresult']];
	}

	$DB->q('INSERT INTO judging_run (judgingid, testcaseid, runresult,
	        runtime, output_run, output_diff, output_error, output_system)
	        VALUES (%i, %i, %s, %f, %s, %s, %s, %s)',
	       $args['judgingid'], $args['testcaseid'], $args['runresult'], $args['runtime'],
	       base64_decode($args['output_run']),
	       base64_decode($args['output_diff']),
	       base64_decode($args['output_error']),
	       base64_decode($args['output_system']));

	// result of this judging_run has been stored. now check whether
	// we're done or if more testcases need to be judged.

	$probid = $DB->q('VALUE SELECT probid FROM testcase
	                  WHERE testcaseid = %i', $args['testcaseid']);

	$runresults = $DB->q('COLUMN SELECT runresult
	                      FROM judging_run LEFT JOIN testcase USING(testcaseid)
	                      WHERE judgingid = %i ORDER BY rank', $args['judgingid']);
	$numtestcases = $DB->q('VALUE SELECT count(*) FROM testcase WHERE probid = %i', $probid);

	$allresults = array_pad($runresults, $numtestcases, null);

	$before = $DB->q('VALUE SELECT result FROM judging WHERE judgingid = %i', $args['judgingid']);

	if ( ($result = getFinalResult($allresults, $results_prio))!==NULL ) {
		if ( count($runresults) == $numtestcases || dbconfig_get('lazy_eval_results', true) ) {
			// NOTE: setting endtime here determines in testcases_GET
			// whether a next testcase will be handed out.
			$DB->q('UPDATE judging SET result = %s, endtime = %s
			        WHERE judgingid = %i', $result, now(), $args['judgingid']);
		} else {
			$DB->q('UPDATE judging SET result = %s
			        WHERE judgingid = %i', $result, $args['judgingid']);
		}

		if ( $before !== $result ) {

			$row = $DB->q('TUPLE SELECT s.cid, s.teamid, s.probid, s.langid, s.submitid
			               FROM judging
			               LEFT JOIN submission s USING(submitid)
			               WHERE judgingid = %i',$args['judgingid']);
			calcScoreRow($row['cid'], $row['teamid'], $row['probid']);

			// log to event table if no verification required
			// (case of verification required is handled in www/jury/verify.php)
			if ( ! dbconfig_get('verification_required', 0) ) {
				$DB->q('INSERT INTO event (eventtime, cid, teamid, langid, probid,
				        submitid, judgingid, description)
				        VALUES(%s, %i, %i, %s, %i, %i, %i, "problem judged")',
				       now(), $row['cid'], $row['teamid'], $row['langid'],
				       $row['probid'], $row['submitid'], $args['judgingid']);
				if ( $result == 'correct' ) {
					// prevent duplicate balloons in case of multiple correct submissions
					$numcorrect = $DB->q('VALUE SELECT count(submitid)
					                      FROM balloon
					                      LEFT JOIN submission USING(submitid)
					                      WHERE valid = 1 AND probid = %i
					                      AND teamid = %i AND cid = %i',
					                     $row['probid'], $row['teamid'], $row['cid']);
					if ( $numcorrect == 0 ) {
						$balloons_enabled = (bool)$DB->q("VALUE SELECT process_balloons
						                                  FROM contest WHERE cid = %i",
						                                 $row['cid']);
						if ( $balloons_enabled ) {
							$DB->q('INSERT INTO balloon (submitid) VALUES(%i)',
							       $row['submitid']);
						}
					}
				}
			}

			auditlog('judging', $args['judgingid'], 'judged', $result, $args['judgehost']);
		}
	}

	$DB->q('UPDATE judgehost SET polltime = %s WHERE hostname = %s',
	       now(), $args['judgehost']);

	return '';
}
$doc = 'Add a new judging_run to the list of judging_runs. When relevant, finalize the judging.';
$args = array('judgingid' => 'Judging_run corresponds to this specific judgingid.',
	'testcaseid' => 'Judging_run corresponding to this specific testcaseid.',
	'runresult' => 'Result of this run.',
	'runtime' => 'Runtime of this run.',
	'output_run' => 'Program output of this run.',
	'output_diff' => 'Program diff of this run.',
	'output_error' => 'Program error output of this run.',
	'output_run' => 'Program output of this run (base64 encoded).',
	'output_diff' => 'Program diff of this run (base64 encoded).',
	'output_error' => 'Program error output of this run (base64 encoded).',
	'output_system' => 'Judging system output of this run (base64 encoded).',
	'judgehost' => 'Judgehost performing this judging');
$exArgs = array();
$roles = array('judgehost');
$api->provideFunction('POST', 'judging_runs', $doc, $args, $exArgs, $roles);

/**
 * DB configuration
 */
function config($args)
{
	if ( isset($args['name']) ) {
		return array($args['name'] => dbconfig_get($args['name'], null, false));
	}

	return dbconfig_get(null, null, false);
}
$doc = 'Get configuration variables.';
$args = array('name' => 'Search only a single config variable.');
$exArgs = array(array('name' => 'sourcesize_limit'));
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'config', $doc, $args, $exArgs);

/**
 * Submissions information
 */
function submissions($args)
{
	global $DB, $cdatas, $api;

	$query = 'SELECT submitid, teamid, probid, langid, submittime, valid
	          FROM submission WHERE TRUE';

	$hasCid = array_key_exists('cid', $args);
	$query .= ($hasCid ? ' AND cid = %i' : ' AND TRUE %_');
	$cid = ($hasCid ? $args['cid'] : 0);

	$hasLanguage = array_key_exists('language', $args);
	$query .= ($hasLanguage ? ' AND langid = %s' : ' AND TRUE %_');
	$language = ($hasLanguage ? $args['language'] : 0);

	$hasFromid = array_key_exists('fromid', $args);
	$query .= ($hasFromid ? ' AND submitid >= %i' : ' AND TRUE %_');
	$fromId = ($hasFromid ? $args['fromid'] : 0);

	$hasSubmitid = array_key_exists('id', $args);
	$query .= ($hasSubmitid ? ' AND submitid = %i' : ' AND TRUE %_');
	$submitid = ($hasSubmitid ? $args['id'] : 0);

	if ( $cid == 0 && !checkrole('jury') ) {
		$api->createError("argument 'cid' is mandatory for non-jury users");
	}

	if ( $cid != 0 && infreeze($cdatas[$cid], now()) && !checkrole('jury') ) {
		$query .= ' AND submittime <= %i';
		$freezetime = $cdatas[$cid]['freezetime'];
	} else {
		$query .= ' AND TRUE %_';
		$freezetime = 0;
	}

	$query .= ' ORDER BY submitid';

	$hasLimit = array_key_exists('limit', $args);
	$query .= ($hasLimit ? ' LIMIT %i' : ' %_');
	$limit = ($hasLimit ? $args['limit'] : -1);
	// TODO: validate limit

	$q = $DB->q($query, $cid, $language, $fromId, $submitid, $freezetime, $limit);
	$res = array();
	while ( $row = $q->next() ) {
		$res[] = array(
			'id'        => $row['submitid'],
			'team'      => $row['teamid'],
			'problem'   => $row['probid'],
			'language'  => $row['langid'],
			'time'      => $row['submittime'],
			);
	}
	return $res;
}
$args = array('cid' => 'Contest ID. If not provided, get submissions of all active contests',
              'language' => 'Search only for submissions in a certain language.',
              'id' => 'Search only a certain ID',
              'fromid' => 'Search from a certain ID',
              'limit' => 'Get only the first N submissions');
$doc = 'Get a list of all valid submissions.';
$exArgs = array(array('fromid' => 100, 'limit' => 10), array('language' => 'cpp'));
$api->provideFunction('GET', 'submissions', $doc, $args, $exArgs);

/**
 * POST a new submission
 */
function submissions_POST($args)
{
	global $userdata, $DB, $api;
	checkargs($args, array('shortname','langid'));
	checkargs($userdata, array('teamid'));
	$contests = getCurContests(TRUE, $userdata['teamid'], false, 'shortname');
	$contest_shortname = null;

	if ( !isset($args['contest']) && count($contests) == 1 ) {
		$contest_shortname = key($contests);
	} elseif ( isset($args['contest']) && isset($contests[$args['contest']]) ) {
		$contest_shortname = $args['contest'];
	} else {
		$api->createError("Can not find that contest, or you are not part of it, or multiple active contests found");
	}
	$cid = $contests[$contest_shortname]['cid'];

	$probid = $DB->q('MAYBEVALUE SELECT probid FROM problem
	                  INNER JOIN contestproblem USING (probid)
	                  WHERE shortname = %s AND cid = %i AND allow_submit = 1',
	                 $args['shortname'], $cid);
	if ( empty($probid ) ) {
		error("Problem " . $args['shortname'] . " not found or or not submittable");
	}

	// rebuild array of filenames, paths to get rid of empty upload fields
	$FILEPATHS = $FILENAMES = array();
	foreach($_FILES['code']['tmp_name'] as $fileid => $tmpname ) {
		if ( !empty($tmpname) ) {
			checkFileUpload($_FILES['code']['error'][$fileid]);
			$FILEPATHS[] = $_FILES['code']['tmp_name'][$fileid];
			$FILENAMES[] = $_FILES['code']['name'][$fileid];
		}
	}

	$sid = submit_solution($userdata['teamid'], $probid, $cid, $args['langid'], $FILEPATHS, $FILENAMES);

	auditlog('submission', $sid, 'added', 'via api', null, $cid);

	return $sid;
}

$args = array('code[]' => 'Array of source files to submit',
              'shortname' => 'Problem shortname',
              'langid' => 'Language ID',
              'contest' => 'Contest short name. Required if more than one contest is active');
$doc = 'Post a new submission. You need to be authenticated with a team role. Returns the submission id. This is used by the submit client.

A trivial command line submisson using the curl binary could look like this:

curl -n -F "shortname=hello" -F "langid=c" -F "cid=2" -F "code[]=@test1.c" -F "code[]=@test2.c"  http://localhost/domjudge/api/submissions';
$exArgs = array();
$roles = array('team');
$api->provideFunction('POST', 'submissions', $doc, $args, $exArgs, $roles);

/**
 * Submission Files
 */
function submission_files($args)
{
	global $DB, $api;

	checkargs($args, array('id'));

	$sources = $DB->q('SELECT filename, sourcecode FROM submission_file
	                   WHERE submitid = %i ORDER BY rank', $args['id']);

	$ret = array();
	while($src = $sources->next()) {
		$ret[] = array(
		         'filename' => $src['filename'],
		         'content'  => base64_encode($src['sourcecode']),
		         );
	}

	return $ret;
}
$args = array('id' => 'Get only the corresponding submission files.');
$doc = 'Get a list of all submission files. The file contents will be base64 encoded.';
$exArgs = array(array('id' => 3));
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'submission_files', $doc, $args, $exArgs, $roles);

/**
 * Testcases
 */
function testcases($args)
{
	global $DB, $api;

	checkargs($args, array('judgingid'));

	// endtime is set: judging is fully done; return empty
	$row = $DB->q('TUPLE SELECT endtime,probid
	               FROM judging LEFT JOIN submission USING(submitid)
	               WHERE judgingid = %i', $args['judgingid']);
	if ( !empty($row['endtime']) ) return '';

	$judging_runs = $DB->q("COLUMN SELECT testcaseid FROM judging_run
	                        WHERE judgingid = %i", $args['judgingid']);
	$sqlextra = count($judging_runs) ? "AND testcaseid NOT IN %Ai" : "%_";
	$testcase = $DB->q("MAYBETUPLE SELECT testcaseid, rank, probid, md5sum_input, md5sum_output
	                    FROM testcase WHERE probid = %i $sqlextra ORDER BY rank LIMIT 1",
	                   $row['probid'], $judging_runs);

	// would probably never be empty, because then endtime would also
	// have been set. we cope with it anyway for now.
	return is_null($testcase) ? '' : $testcase;
}
$args = array('judgingid' => 'Get the next-to-judge testcase for this judging.');
$doc = 'Get a testcase.';
$exArgs = array();
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'testcases', $doc, $args, $exArgs, $roles);

function testcase_files($args)
{
	global $DB, $api;

	checkargs($args, array('testcaseid'));

	if ( !isset($args['input']) && !isset($args['output']) ) {
		$api->createError("either input or output is mandatory");
	}
	if ( isset($args['input']) && isset($args['output']) ) {
		$api->createError("cannot select both input and output");
	}
	$inout = 'output';
	if ( isset($args['input']) ) {
		$inout = 'input';
	}

	$content = $DB->q("VALUE SELECT SQL_NO_CACHE $inout FROM testcase
	                   WHERE testcaseid = %i", $args['testcaseid']);

	return base64_encode($content);
}
$args = array('testcaseid' => 'Get only the corresponding testcase.',
	'input' => 'Get the input file.',
	'output' => 'Get the output file.');
$doc = 'Get a testcase file, base64 encoded.';
$exArgs = array(array('testcaseid' => '3', 'input' => TRUE));
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'testcase_files', $doc, $args, $exArgs, $roles);

// executable zip, e.g. for compare scripts
function executable($args)
{
	global $DB, $api;

	checkargs($args, array('execid'));

	$content = $DB->q("VALUE SELECT SQL_NO_CACHE zipfile FROM executable
	                   WHERE execid = %s", $args['execid']);

	return base64_encode($content);
}
$args = array('execid' => 'Get only the corresponding executable.');
$doc = 'Get an executable zip file, base64 encoded.';
$exArgs = array(array('execid' => 'ignorews'));
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'executable', $doc, $args, $exArgs, $roles);

/**
 * Judging Queue
 *
 * FIXME: duplicates code with judgings_post
 * not used in judgedaemon
 */
function queue($args)
{
	global $DB;

	// TODO: make this configurable
	$cdatas = getCurContests(TRUE);
	$cids = array_keys($cdatas);

	$hasLimit = array_key_exists('limit', $args);
	// TODO: validate limit

	$submitids = $DB->q('SELECT submitid
	                     FROM submission s
	                     LEFT JOIN team t USING (teamid)
	                     LEFT JOIN problem p USING (probid)
	                     LEFT JOIN language l USING (langid)
	                     LEFT JOIN contestproblem cp USING (probid, cid)
	                     WHERE judgehost IS NULL AND s.cid IN %Ai
	                     AND l.allow_judge = 1 AND cp.allow_judge = 1 AND valid = 1
	                     ORDER BY judging_last_started ASC, submittime ASC, submitid ASC' .
	                    ($hasLimit ? ' LIMIT %i' : ' %_'),
	                    $cids, ($hasLimit ? $args['limit'] : -1));

	return $submitids->getTable();
}
$args = array('limit' => 'Get only the first N queued submissions');
$doc = 'Get a list of all queued submission ids.';
$exArgs = array(array('limit' => 10));
$roles = array('jury','judgehost');
$api->provideFunction('GET', 'queue', $doc, $args, $exArgs, $roles);

/**
 * Affiliation information
 */
function affiliations($args)
{
	global $DB;

	// Construct query
	$query = 'SELECT affilid, shortname, name, country FROM team_affiliation WHERE';

	$byCountry = array_key_exists('country', $args);
	$query .= ($byCountry ? ' country = %s' : ' TRUE %_');
	$country = ($byCountry ? $args['country'] : '');

	$query .= ' ORDER BY name';

	// Run query and return result
	$q = $DB->q($query, $country);
	return $q->gettable();
}
$doc = 'Get a list of affiliations, with for each affiliation: affilid, shortname, name and country.';
$optArgs = array('country' => 'ISO 3166-1 alpha-3 country code to search for.');
$exArgs = array(array('country' => 'NLD'));
$api->provideFunction('GET', 'affiliations', $doc, $optArgs, $exArgs);

/**
 * Team information
 */
function teams($args)
{
	global $DB;

	// Construct query
	$query = 'SELECT teamid AS id, t.name, a.country AS nationality,
	          t.categoryid AS category, a.affilid, a.name AS affiliation
	          FROM team t
	          LEFT JOIN team_affiliation a USING(affilid)
	          LEFT JOIN team_category c USING (categoryid)
	          WHERE t.enabled = 1 AND';

	$byCategory = array_key_exists('category', $args);
	$query .= ($byCategory ? ' categoryid = %i' : ' TRUE %_');
	$category = ($byCategory ? $args['category'] : 0);

	$query .= ' AND';

	$byAffil = array_key_exists('affiliation', $args);
	$query .= ($byAffil ? ' affilid = %s' : ' TRUE %_');
	$affiliation = ($byAffil ? $args['affiliation'] : 0);

	$byTeamid = array_key_exists('teamid', $args);
	$query .= ($byTeamid ? ' AND teamid = %i' : ' AND TRUE %_');
	$teamid = ($byTeamid ? $args['teamid'] : 0);

	$query .= ($args['public'] ? ' AND visible = 1' : '');

	// Run query and return result
	$q = $DB->q($query, $category, $affiliation, $teamid);
	return $q->gettable();
}
$args = array('category' => 'ID of a single category to search for.',
              'affiliation' => 'ID of an affiliation to search for.',
              'teamid' => 'Search for a specific team.');
$doc = 'Get a list of teams containing teamid, name, category and affiliation.';
$exArgs = array(array('category' => 1, 'affiliation' => 'UU'));
$api->provideFunction('GET', 'teams', $doc, $args, $exArgs, null, true);

/**
 * Category information
 */
function categories($args)
{
	global $DB;
	$extra = ($args['public'] ? 'WHERE visible = 1' : '');
	$q = $DB->q('SELECT categoryid, name, color, visible, sortorder
	             FROM team_category ' . $extra . ' ORDER BY sortorder');
	$res = array();
	while ( $row = $q->next() ) {
		$res[] = array('categoryid' => $row['categoryid'],
			'name' => $row['name'],
			'color' => $row['color'],
			'sortorder' => $row['sortorder']);
	}
	return $res;
}
$doc = 'Get a list of all categories.';
$api->provideFunction('GET', 'categories', $doc, array(), array(), null, true);

/**
 * Language information
 */
function languages()
{
	global $DB;

	$q = $DB->q('SELECT langid, name, extensions, allow_judge, time_factor
	             FROM language WHERE allow_submit = 1');
	$res = array();
	while ( $row = $q->next() ) {
		$res[] = array(
			'id'           => $row['langid'],
			'name'         => $row['name'],
			'extensions'   => json_decode($row['extensions']),
			'allow_judge'  => (bool)$row['allow_judge'],
			'time_factor'  => (float)$row['time_factor'],
			);
	}
	return $res;
}
$doc = 'Get a list of all suported programming languages.';
$api->provideFunction('GET', 'languages', $doc);

/**
 * Clarification information
 */
function clarifications($args)
{
	global $cids, $DB;

	// Find public clarifications, maybe later also provide more info for jury
	$query = 'SELECT clarid, submittime, probid, body FROM clarification
	          WHERE cid IN %Ai AND sender IS NULL AND recipient IS NULL';

	$byProblem = array_key_exists('problem', $args);
	$query .= ($byProblem ? ' AND probid = %i' : ' AND TRUE %_');
	$problem = ($byProblem ? $args['problem'] : null);

	$q = $DB->q($query, $cids, $problem);
	return $q->getTable();
}
$doc = 'Get a list of all public clarifications.';
$args = array('problem' => 'Search for clarifications about a specific problem.');
$exArgs = array(array('problem' => 'H'));
$api->provideFunction('GET', 'clarifications', $doc, $args, $exArgs);

/**
 * Judgehosts
 */
function judgehosts($args)
{
	global $DB;

	$query = 'SELECT hostname, active, polltime FROM judgehost';

	$byHostname = array_key_exists('hostname', $args);
	$query .= ($byHostname ? ' WHERE hostname = %s' : '%_');
	$hostname = ($byHostname ? $args['hostname'] : null);

	$q = $DB->q($query, $hostname);
	return $q->getTable();
}
$doc = 'Get a list of judgehosts.';
$args = array('hostname' => 'Search only for judgehosts with given hostname.');
$exArgs = array(array('hostname' => 'sparehost'));
$roles = array('jury');
$api->provideFunction('GET', 'judgehosts', $doc, $args, $exArgs, $roles);

function judgehosts_POST($args)
{
	global $DB, $api;

	checkargs($args, array('hostname'));

	$DB->q('INSERT IGNORE INTO judgehost (hostname) VALUES(%s)',
	       $args['hostname']);

	// If there are any unfinished judgings in the queue in my name,
	// they will not be finished. Give them back.
	$res = $DB->q('SELECT judgingid, submitid, cid FROM judging
	               WHERE judgehost = %s AND endtime IS NULL AND valid = 1',
	              $args['hostname']);
	$ret = $res->getTable();
	$res = $DB->q('SELECT judgingid, submitid, cid FROM judging
	               WHERE judgehost = %s AND endtime IS NULL AND valid = 1',
	              $args['hostname']);
	while ( $jud = $res->next() ) {
		$DB->q('UPDATE judging SET valid = 0 WHERE judgingid = %i',
		       $jud['judgingid']);
		$DB->q('UPDATE submission SET judgehost = NULL
		        WHERE submitid = %i', $jud['submitid']);
		auditlog('judging', $jud['judgingid'], 'given back', null, $args['hostname'], $jud['cid']);
	}

	return $ret;
}
$doc = 'Add a new judgehost to the list of judgehosts. Also restarts (and returns) unfinished judgings.';
$args = array('hostname' => 'Add this specific judgehost and activate it.');
$exArgs = array(array('hostname' => 'judge007'));
$roles = array('judgehost');
$api->provideFunction('POST', 'judgehosts', $doc, $args, $exArgs, $roles);

function judgehosts_PUT($args)
{
	global $DB, $api;

	if ( !isset($args['__primary_key']) ) {
		$api->createError("hostname is mandatory");
	}
	$hostname = $args['__primary_key'];
	if ( !isset($args['active']) ) {
		$api->createError("active is mandatory");
	}
	$active = $args['active'];
	$DB->q('UPDATE judgehost SET active=%i WHERE hostname=%s', $active, $hostname);

	return judgehosts(array('hostname' => $hostname));
}
$doc = 'Update the configuration of a judgehost.';
$args = array('active' => 'Activate judgehost?');
$exArgs = array();
$roles = array('judgehost');
$api->provideFunction('PUT', 'judgehosts', $doc, $args, $exArgs, $roles);

/**
 * Scoreboard (not finished yet)
 */
function scoreboard($args)
{
	checkargs($args, array('cid'));

	global $cdatas;

	$filter = array();
	if ( array_key_exists('category', $args) ) {
		$filter['categoryid'] = array($args['category']);
	}
	if ( array_key_exists('country', $args) ) {
		$filter['country'] = array($args['country']);
	}
	if ( array_key_exists('affiliation', $args) ) {
		$filter['affilid'] = array($args['affiliation']);
	}
	// TODO: refine this output, maybe add separate function to get summary
	$scores = genScoreBoard($cdatas[$args['cid']], !$args['public'], $filter);
	return $scores['matrix'];
}
$doc = 'Get the scoreboard. Returns scoreboard for jury members if authenticated as a jury member (and public is not 1).';
$args = array('cid' => 'ID of the contest to get the scoreboard for',
              'category' => 'ID of a single category to search for.',
              'affiliation' => 'ID of an affiliation to search for.',
              'country' => 'ISO 3166-1 alpha-3 country code to search for.');
$exArgs = array(array('cid' => 2, 'category' => 1, 'affiliation' => 'UU'), array('cid' => 2, 'country' => 'NLD'));
$api->provideFunction('GET', 'scoreboard', $doc, $args, $exArgs, null, true);

// Now provide the api, which will handle the request
$api->provideApi();
