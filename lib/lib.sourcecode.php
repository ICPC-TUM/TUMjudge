<?php
/**
 * Show source code from the database.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

function parseSourceDiff($difftext){
	$line = strtok($difftext,"\n"); //first line
	$return = '';
	while ( strlen($line) != 0 ) {
		// Strip any additional DOS/MAC newline characters:
		$line = trim($line, "\r\n");
		switch ( substr($line,0,1) ) {
		case '-':
			$formdiffline = "<span class='diff-del'>".specialchars($line)."</span>";
			break;
		case '+':
			$formdiffline = "<span class='diff-add'>".specialchars($line)."</span>";
			break;
		default:
			$formdiffline = specialchars($line);
		}
		$return .= $formdiffline . "\n";
		$line = strtok("\n");
	}
	return $return;
}

/* FIXME: this assumes GNU diff. */
function systemDiff($oldfile, $newfile)
{
	$oldname = basename($oldfile);
	$newname = basename($newfile);
	return `diff -Bdt --strip-trailing-cr -U2 \
	             --label $oldname --label $newname $oldfile $newfile 2>&1`;
}

function createDiff($source, $newfile, $id, $oldsource, $oldfile, $oldid) {

	// Try different ways of diffing, in order of preference.
	if ( function_exists('xdiff_string_diff') ) {
		// The PECL xdiff PHP-extension.

		$difftext = xdiff_string_diff($oldsource['sourcecode'],
		                              $source['sourcecode'],2,TRUE);

	} elseif ( !(bool) ini_get('safe_mode') ||
		       strtolower(ini_get('safe_mode'))=='off' ) {
		// Only try executing diff when safe_mode is off, otherwise
		// the shell exec will fail.

		if ( is_readable($oldfile) && is_readable($newfile) ) {
			// A direct diff on the sources in the SUBMITDIR.

			$difftext = systemDiff($oldfile, $newfile);

		} else {
			// Try generating temporary files for executing diff.

			$oldfile = tempnam(TMPDIR, "source-old-s$oldid-");
			$newfile = tempnam(TMPDIR, "source-new-s$id-");

			if( ! $oldfile || ! $newfile ) {
				$difftext = "DOMjudge: error generating temporary files for diff.";
			} else {
				$oldhandle = fopen($oldfile,'w');
				$newhandle = fopen($newfile,'w');

				if( ! $oldhandle || ! $newhandle ) {
					$difftext = "DOMjudge: error opening temporary files for diff.";
				} else {
					if ( (fwrite($oldhandle,$oldsource['sourcecode'])===FALSE) ||
					     (fwrite($newhandle,   $source['sourcecode'])===FALSE) ) {
						$difftext = "DOMjudge: error writing temporary files for diff.";
					} else {
						$difftext = systemDiff($oldfile, $newfile);
					}
				}
				if ( $oldhandle ) fclose($oldhandle);
				if ( $newhandle ) fclose($newhandle);
			}

			if ( $oldfile ) unlink($oldfile);
			if ( $newfile ) unlink($newfile);
		}
	} else {
		$difftext = "DOMjudge: diff functionality not available in PHP or via shell exec.";
	}

	return $difftext;
}

function presentSource ($sourcedata, $langid)
{
	$head = '<div class="tabbertab">' .
		'<h2 class="filename"><a id="source' . specialchars($sourcedata['rank']) .
		'"></a>' .
		specialchars($sourcedata['filename']) . "</h2> <a " .
		"href=\"show_source.php?id=" . urlencode($sourcedata['submitid']) .
		"&amp;fetch=" . urlencode($sourcedata['rank']) .
		"\"><img class=\"picto\" src=\"../images/b_save.png\" " .
	    "alt=\"download\" title=\"download\" /></a> " .
		"<a href=\"edit_source.php?id=" . urlencode($sourcedata['submitid']) .
		"&amp;rank=" . urlencode($sourcedata['rank']) . "\">" .
		"<img class=\"picto\" src=\"../images/edit.png\" alt=\"edit\" title=\"edit\" />" .
		"</a>\n\n";

	$langid = langidToAce($langid);

	$ace = '<div class="editor" id="editor' . specialchars($sourcedata['rank']) . '">'
		. specialchars($sourcedata['sourcecode']) . '</div>' .
		'<script>' .
		'var editor = ace.edit("editor' . specialchars($sourcedata['rank']) . '");' .
		'editor.setTheme("ace/theme/eclipse");' .
		'editor.setOptions({ maxLines: Infinity });' .
		'editor.setReadOnly(true);' .
		'editor.getSession().setMode("ace/mode/' . $langid . '");' .
        'document.getElementById("editor' . specialchars($sourcedata['rank']) . '").editor = editor;' .
		'</script>';

	return $head . $ace . '</div>';
}

function presentDiff ($old, $new)
{
	$oldsourcefile = getSourceFilename($old);
	$newsourcefile = getSourceFilename($new);

	$difftext = createDiff($new, SUBMITDIR.'/'.$newsourcefile, $new['submitid'],
	                       $old, SUBMITDIR.'/'.$oldsourcefile, $old['submitid']);

	return '<div class="tabbertab">' .
		'<h2 class="filename">' .
		specialchars($old['filename']) . "</h2>\n\n" .

		'<pre class="output_text">' . parseSourceDiff($difftext) . "</pre>\n\n" .
		'</div>';
}

function multifilediff ($sources, $oldsources, $olddata)
{
	$diffhtml = $html = '';
	// if both current and previous submission have just one file, diff them directly
	if (count($sources) == 1 && count($oldsources) == 1 ) {
		$html .= '<div class="tabber">' .
			presentDiff ( array_merge($oldsources[0],$olddata), $sources[0] ) .
			'</div>';
	} else {
		$newfilenames = $fileschanged = $filesunchanged = array();
		foreach($sources as $newsource) {
			$oldfilenames = array();
			foreach($oldsources as $oldsource) {
				if($newsource['filename'] == $oldsource['filename']) {
					if ( $oldsource['sourcecode'] == $newsource['sourcecode'] ) {
						$filesunchanged[] = $newsource['filename'];
					} else {
						$fileschanged[] = $newsource['filename'];
						$diffhtml .= presentDiff ( array_merge($oldsource,$olddata), $newsource );
					}
				}
				$oldfilenames[] = $oldsource['filename'];
			}
			$newfilenames[] = $newsource['filename'];
		}
		$filesadded   = array_diff($newfilenames,$oldfilenames);
		$filesremoved = array_diff($oldfilenames,$newfilenames);

		$html .= "<table>\n";
		if ( count($filesadded)>0 ) {
			$html .= "<tr><td class=\"diff-add\">Files added:</td><td class=\"filename\">" .
				implode(' ', $filesadded) . "</td></tr>\n";
		}
		if ( count($filesremoved)>0 ) {
			$html .= "<tr><td class=\"diff-del\">Files removed:</td>" .
				"<td class=\"filename\">" . implode(' ', $filesremoved) . "</td></tr>\n";
		}
		if ( count($fileschanged)>0 ) {
			$html .= "<tr><td class=\"diff-changed\">Files changed:</td>" .
			    "<td class=\"filename\">" . implode(' ', $fileschanged) . "</td></tr>\n";
		}
		if ( count($filesunchanged)>0 ) {
			$html .= "<tr><td>Files unchanged:</td><td class=\"filename\">" .
				implode(' ', $filesunchanged) . "</td></tr>\n";
		}
		$html .= "</table>\n\n";
		$html .= "<div class=\"tabber\">\n" . $diffhtml . "</div>\n";
	}

	return $html;
}

function presentSourceCode($id, $teamid=-1) {
	
	global $DB;
	
	$add = "";
	if($teamid != -1)
		$add = "AND teamid = ".$teamid;
		
	$submission = $DB->q("MAYBETUPLE SELECT * FROM submission s
			      WHERE submitid = %i $add",$id);
	
	if ( empty($submission) ) error ("Submission $id not found");

	// Download was requested
	if ( isset($_GET['fetch']) ) {

		$row = $DB->q("TUPLE SELECT filename, sourcecode FROM submission_file
					  LEFT JOIN submission USING(submitid)
			      WHERE submitid = %i AND rank = %i $add", $id, $_GET['fetch']);
		header("Content-Type: text/plain; name=\"" . $row['filename'] .
		      "\"; charset=" . DJ_CHARACTER_SET);
		header("Content-Disposition: attachment; filename=\"" . $row['filename'] . "\"");
		header("Content-Length: " . strlen($row['sourcecode']));

		echo $row['sourcecode'];
		exit;
	}

	$title = "Source: s$id";
	require(LIBWWWDIR . '/header.php');

	// display highlighted content of the source files
	$sources = $DB->q("TABLE SELECT *
			  FROM submission_file LEFT JOIN submission USING(submitid)
			  WHERE submitid = %i $add ORDER BY rank", $id);

	$html = '<script type="text/javascript">' .
		// Resize ACE editor after the corresponding tab is clicked
		'tabberOptions = { "onTabDisplay" : function(d) {' .
		'  var ed = d.tabber.tabs[d.index].div.getElementsByClassName("ace_editor")[0];' .
		'  if(ed && ed.editor) ed.editor.resize();' .
		'} };' .
		'</script>' .
		'<script type="text/javascript" src="../js/tabber.js"></script>' .
			'<script type="text/javascript" src="../js/ace/ace.js" charset="utf-8"></script>' .
		'<div class="tabber">';
	foreach($sources as $sourcedata)
	{
		$html .= presentSource($sourcedata, $submission['langid']);
	}
	$html .= "</div>";

	// display diff between previous and/or original submission

	if ($submission['origsubmitid']) {
		$origdata    = $DB->q('TUPLE SELECT * FROM submission
				      WHERE submitid = %i', $submission['origsubmitid']);
		$origsources = $DB->q('TABLE SELECT * FROM submission_file
				      WHERE submitid = %i', $submission['origsubmitid']);
		$olddata     = $DB->q('MAYBETUPLE SELECT * FROM submission
				      WHERE probid = %i AND langid = %s AND submittime < %s
				      AND origsubmitid = %i ORDER BY submittime DESC LIMIT 1',
				      $submission['probid'],$submission['langid'],
				      $submission['submittime'], $submission['origsubmitid']);
		$oldsources  = $DB->q('TABLE SELECT * FROM submission_file
				      WHERE submitid = %i', $olddata['submitid']);
	} else {
		$olddata     = $DB->q('MAYBETUPLE SELECT * FROM submission
				      WHERE teamid = %i AND probid = %i AND langid = %s AND submittime < %s
				      ORDER BY submittime DESC LIMIT 1',
				      $submission['teamid'],$submission['probid'],$submission['langid'],
				      $submission['submittime']);
		$oldsources  = $DB->q('TABLE SELECT * FROM submission_file
				      WHERE submitid = %i', $olddata['submitid']);
	}

	if ($olddata !== NULL) {
		$oldid = $olddata['submitid'];
		$html .= "<h2 id=\"diff\">Diff to submission " .
			"<a href=\"submission.php?id=$oldid\">s$oldid</a></h2>\n";

		$html .= multifilediff($sources, $oldsources, $olddata);
	}

	if ( !empty($origsources) ) {
		$origid = $submission['origsubmitid'];
		$html .= "<h2 id=\"origdiff\">Diff to original submission " .
			"<a href=\"submission.php?id=$origid\">s$origid</a></h2>\n\n";

		$html .= multifilediff($sources, $origsources, $origdata);
	}

	echo "<h2>Source code for submission <a href=\"submission.php?id=" .
		urlencode($id) . "\">s" .specialchars($id) . "</a>";
	if ( !empty($submission['origsubmitid']) ) {
		$origid = $submission['origsubmitid'];
		echo  " (resubmit of <a href=\"submission.php?id=" . urlencode($origid) . "\">s$origid</a>)";
	}
	echo "</h2>\n\n";
	if ( $olddata !== NULL ) {
		echo "<p><a href=\"#diff\">Go to diff to previous submission</a></p>\n\n";
	}
	if ( $submission['origsubmitid'] ) {
		echo "<p><a href=\"#origdiff\">Go to diff to original submission</a></p>\n\n";
	}

	echo $html;

	require(LIBWWWDIR . '/footer.php');

}


function editSourceCode($id, $teamid) {
	
	global $DB;

	if ( empty($teamid) || !checkrole('team') ) {
		error("You cannot re-submit code without being a team.");
	}

	// submit code
	if ( isset($_POST['origsubmitid']) ) {
		$sources = $DB->q('TABLE SELECT *
				  FROM submission_file
				  LEFT JOIN submission USING(submitid)
				  WHERE submitid = %i ORDER BY rank', $_POST['origsubmitid']);

		$files = array();
		$filenames = array();
		foreach($sources as $sourcedata)
		{
			if ( !($tmpfname = tempnam(TMPDIR, "edit_source-")) ) {
				error("Could not create temporary file.");
			}
			file_put_contents($tmpfname, $_POST['source' . $sourcedata['rank']]);

			$files[] = $tmpfname;
			$filenames[] = $sourcedata['filename'];
		}

		$cid = $DB->q('VALUE SELECT cid FROM submission
			      WHERE submitid = %i', $_POST['origsubmitid']);

		$newid = submit_solution($teamid, $_POST['probid'], $cid, $_POST['langid'],
				$files, $filenames, $_POST['origsubmitid']);

		foreach($files as $file)
		{
			unlink($file);
		}
		
		if(checkrole('jury')) {
			header('Location: ../jury/submission.php?id=' . $newid);
		} else {
			$time = $DB->q('VALUE SELECT endtime FROM contest
			      WHERE cid = %i', $cid);
			
			echo $time ." !! ";
			
			if(time() > $time) {
				header('Location: ../team/index.php');
			} else {
				header('Location: ../team/submission_details.php?id=' . $newid);
			}
			
		}

		exit;
	}

	$submission = $DB->q('MAYBETUPLE SELECT * FROM submission s
			      WHERE submitid = %i', $id);

	if ( empty($submission) ) error ("Submission $id not found");

	$title = 'Edit Source: s' . $id;
	require(LIBWWWDIR . '/header.php');


	echo '<h2><a id="source"></a>Edit submission ' .
		"<a href=\"submission.php?id=$id\">s$id</a> source files</h2>\n\n";

	echo addForm($pagename, 'post', null, 'multipart/form-data');


	$sources = $DB->q('TABLE SELECT *
			  FROM submission_file
			  LEFT JOIN submission USING(submitid)
			  WHERE submitid = %i ORDER BY rank', $id);

	echo '<script type="text/javascript" src="../js/tabber.js"></script>' .
		'<script src="../js/ace/ace.js" type="text/javascript" charset="utf-8"></script>' .
		'<div class="tabber">';
	foreach($sources as $sourcedata)
	{
		echo '<div class="tabbertab' . ($_GET['rank'] === $sourcedata['rank'] ? ' tabbertabdefault' : '') .'">';
		echo '<h2 class="filename">' . specialchars($sourcedata['filename']) . '</h2>';
		echo addTextArea('source' . $sourcedata['rank'], $sourcedata['sourcecode'], 120, 40) . "<br />\n";
		$editor = 'editor' . specialchars($sourcedata['rank']);
		$langid = langidToAce($submission['langid']);
		echo '<div class="editor" id="' . $editor . '"></div>';
		echo '<script>' .
			'var textarea = document.getElementById("source' . specialchars($sourcedata['rank']) . '");' .
			'textarea.style.display = \'none\';' .
			'var ' . $editor . ' = ace.edit("' . $editor . '");' .
			$editor . '.setTheme("ace/theme/eclipse");' .
			$editor . '.getSession().setValue(textarea.value);' .
			$editor . '.getSession().on(\'change\', function(){' .
				'var textarea = document.getElementById("source' . specialchars($sourcedata['rank']) . '");' .
				'textarea.value = ' . $editor . '.getSession().getValue();' .
			'});' .
			$editor . '.setOptions({ maxLines: Infinity });' .
			$editor . '.setReadOnly(false);' .
			$editor . '.getSession().setMode("ace/mode/' . $langid . '");' .
			'</script>';
		echo "</div>\n";
	}
	echo "</div>\n";

	$probs = $DB->q('KEYVALUETABLE SELECT probid, name FROM problem
			INNER JOIN contestproblem USING (probid)
			WHERE allow_submit = 1 AND cid = %i ORDER BY name', $submission['cid']);
	$langs = $DB->q('KEYVALUETABLE SELECT langid, name FROM language
			WHERE allow_submit = 1 ORDER BY name');

	echo addSelect('probid', $probs, $submission['probid'], true);
	echo addSelect('langid', $langs, $submission['langid'], true);

	echo addHidden('teamid', $submission['teamid']);
	echo addHidden('origsubmitid', $submission['origsubmitid'] === NULL ? $id : $submission['origsubmitid']);
	echo addSubmit('submit');

	echo addEndForm();

	require(LIBWWWDIR . '/footer.php');


}


?>