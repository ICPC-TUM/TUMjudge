<?php
/**
 * Produce a total score. Call with parameter 'static' for
 * output suitable for static HTML pages.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$title="TUMjudge";

$menu = true;
require(LIBWWWDIR . '/header.php');

?>

<?php if(!logged_in()) { ?>
<div style="width: 200px; float: right; margin: 20px;">
<h2>Login</h2>
<form action="login.php" method="post">
<input type="hidden" name="cmd" value="login" />
<table>
<tr><td><label for="login">Login:</label></td><td><input type="text" id="login" name="login" value="" size="15" maxlength="15" accesskey="l" autofocus />
<tr><td><label for="passwd">Password:</label></td><td><input type="password" id="passwd" name="passwd" value="" size="15" maxlength="255" accesskey="p" />
<tr><td></td><td><input type="submit" value="Login" /></td></tr>
</table>
</form>
</div>
<?php } 

global $DB;

//Get judge config
$config = $DB->q("KEYVALUETABLE SELECT `name` AS ARRAYKEY,`value` FROM configuration");

//Get active languages
$data = $DB->q("KEYTABLE SELECT `langid` AS ARRAYKEY,`name`,`compile_script` FROM `language` WHERE `allow_submit` =  1");
$desc = array();
foreach ($data as $language) {
    //Compute compile statement for each active language
    switch($language['name']) {
	case "Java": 
  	  $desc[$language['name']] = "javac '\$MAINCLASS'<br />";
	  $desc[$language['name']] .= sprintf("java -client -Xss8m -Xmx%dk -DONLINE_JUDGE=1 -DDOMJUDGE=1 '\$MAINCLASS'", $config['memory_limit']-350000);
	  break; 
	case "C": 
	  $desc[$language['name']] = 'gcc -x c -Wall -O2 -static -pipe -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@" -lm'; 
	  break;
	case "C#": 
	  $desc[$language['name']] = 'gmcs -o+ -d:ONLINE_JUDGE,DOMJUDGE -out:"$DESTCLI" "$@"<br />';
	  $desc[$language['name']] .=  'mono "$DESTCLI"';
	  break;
	case "C++": 
	  $desc[$language['name']] = 'g++ -Wall -O2 -static -pipe -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 
	  break;
	case "Lua": 
	  $desc[$language['name']] = 'lua "$MAINSOURCE"'; 
	  break;
	case "Isabelle": 
	  //$desc[$language['name']] = ""; 
	  break;
	case "Coq": 
	  //$desc[$language['name']] = ""; 
	  break;
	case "Ada": 
	  $desc[$language['name']] = 'gnatmake -static -o "$DEST" "$@" -bargs -static'; 
	  break;
	case "AWK": 
	  $desc[$language['name']] = 'awk -v ONLINE_JUDGE=1 -v DOMJUDGE=1 -f "$MAINSOURCE"'; 
	  break;
	case "Bash shell": 
	  $desc[$language['name']] = 'bash "$MAINSOURCE"'; 
	  break;
	case "Fortran": 
	  $desc[$language['name']] = 'gfortran -static -Wall -O2 -cpp -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 
	  break;
	case "Haskell": 
	  $desc[$language['name']] = 'ghc -Wall -Wwarn -O -static -optl-static -optl-pthread -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 
	  break;
	case "Pascal": 
	  $desc[$language['name']] = 'fpc -viwn -O2 -Sg -XS -dONLINE_JUDGE -dDOMJUDGE -o"$DEST" "$MAINSOURCE"'; 
	  break;
	case "Perl": 
	  $desc[$language['name']] = 'perl "$MAINSOURCE"'; 
	  break;
	case "Prolog": 
	  $desc[$language['name']] = 'swipl --goal=main,halt --stand_alone=true -o "$DEST" -c "$MAINSOURCE"'; 
	  break;
	case "Python 2": 
	  $desc[$language['name']] = 'python "$MAINSOURCE"'; 
	  break;
	case "Python 3": 
	  $desc[$language['name']] = 'python3 "$MAINSOURCE"'; 
	  break;
	case "Ruby": 
	  $desc[$language['name']] = 'ruby "$MAINSOURCE"'; 
	  break;
	case "Scala": 
	  $desc[$language['name']] = 'MAINCLASS="$(basename "$MAINSOURCE" .scala)"<br />'; 
	  $desc[$language['name']] .= 'scala \'$MAINCLASS\''; 
	  break;
	case "POSIX shell": 
	  $desc[$language['name']] = 'sh "$MAINSOURCE"'; 
	  break;
	default: break;
    }
}

?>

<style>
p {
  text-align:justify;
}

.code {
  font: 13px 'Courier New', Courier, monospace;
}
</style>

<h1>Welcome to TUMjudge!</h1>

<p>This page contains basic information regarding the TUMjudge. If you never worked with the TUMjudge before we advice you to read this manual first. Else just log in using the login-form on the right hand side.</p>

<h2>Login</h2>
The TUMjudge requires you to login prior to submitting work. Your username and password is the same as in the <q>Rechnerhalle</q>, the login works via the LDAP protocol. 

Reminder: The <q>Rechnerhalle</q> username is the part before the @ in your @in.tum.de email address.

<h2>Judge</h2>
We use a fork of the official DOMjudge system that is also used in contests like the GCPC and ICPC. Some impressions of the systems are given in the following images.

<div style="width=100%; text-align:center">
  <img src="img/team-overview-own.png" style=' width:300px; margin:0px 30px 0px 0px;'>
  <img src="img/team-scoreboard-own.png" style=' width:300px;'>
</div>

<h2>Submitting Solutions</h2>

<p>
Solutions can be submitted from your dashboard which is available under <q>home</q> once you are logged in.<br />
In the left column click <b>Select file...</b> to select the file(s) for submission. TUMjudge will try to determine the problem and language from the base and extension of the filename respectively. If the automatic recognition fails select the appropriate values using the dropdown menues below the file selection.<br />
Filenames must start with an alphanumerical character and may contain only alphanumerical characters, - and _.
</p>

<p>
After you hit the submit button and confirm the submission, you will be redirected back to your submission list page. On this page, a message will be displayed that your submission was successful and the submission should be present in the list. An error message will be displayed if something went wrong.
</p>

<p>
Please note that you have to submit the source code of your program, <i>not a compiled program or the output of your program</i>. Your submission will be judged fully automated.
</p>

<h2>Viewing the results of submissions</h2>

<p>
The top of the page shows your current position in the scoreboard as well as which problems you attempted and solved.
</p>

<p>
The left column of your dashboard shows an overview of your submissions. It contains the submission time, programming language, problem and status of the submission. 
<?php if ($config['show_sample_output'] == 1) {echo 'To view the output your submission produced using the sample input from the problem set click on your submission.';} ?>
</p>

<p>
To view the public scoreboard use the link <q>scoreboard</q> in the top menu. It displays the scores of all participants that agreed to have their results publicly available. The scoreboard contains one column for each problem. This column gives the number of submissions for this problem and if the problem was solved, the time of the first correct submission in minutes since the problem set was handed out.<br />
The scoreboard is ordered by the number of problems solved, ties are broken using a score that is computed as follows:<br />
For each solved problem you receive a penalty score. This score is equal to the time of the first correct submission in minutes since the problem set was handed out plus 
<?php echo $config['penalty_time'];?>
for each failed attempt. The total penalty score is the sum of penalty scores of all solved problems.<br />
Both the total number of correct submissions and the current total penalty score can be found in the colume <q>Score</q>.
</p>

<p>
The score board is visible to everyone, if you want your account NOT to be visible 
on the scoreboard, contact us via clarification.
</p>

<h2>Clarifications</h2>

<p>
You can communicate with the jury via clarifications. These can be found in the right column on your dashboard which is available under <q>home</q> in the top menu. Both clarification replies from the jury and requests sent by you are displayed there.
</p>

<p>
In order to submit new clarifications click the button <q>request clarification</q> at the bottom of the dashboard. This request is only readable for the jury which will respond as soon as possible. Answers that are relevant for everyone will be sent to everyone.
</p>

<h2>Submissions</h2>
<h3>Programming Languages</h3>
<p>
You will be allowed to submit solutions in C++ or Java. Solutions have to read all input from <q>standard in</q> and write all output to <q>standard out</q> (also known as console). You will never have to open (other) files.
</p>

<h3>Compiling</h3>

<p>
Your program will be compiled on a computer running Linux. All submitted source files will be passed to the compiler which generates a single program to run out of them; for languages where
this is relevant, the first specified file will be considered the <q>main</q> source file.
</p>

<p>
Using a different compiler or operating system than the judging system should not be a problem. Be careful however, not to use any special compiler and/or system specific things (you are able to check compiler errors on your dashboard). The compile settings can be found below:
</p>

<ul>

<?php 
  foreach($desc as $key=>$value) {
    printf('<li>%s<br /> <span class="code">%s</span></li>', $key, $value);
  }
  //TODO: Versions of languages
?>

<!--<li>C++<br />
<span style="font: 13px 'Courier New', Courier, monospace;">
g++ -Wall -O2 -std=c++11 -static -pipe -DONLINE_JUDGE
	-DDOMJUDGE -o $DEST "$@"
</span></li>

<li>Java<br />
<span style="font: 13px 'Courier New', Courier, monospace;">
java -DONLINE_JUDGE=1 -DDOMJUDGE=1 -Xrs -Xss8m
	-Xmx${MEMLIMITJAVA}k $MAINCLASS
</span></li>
-->
</ul>

<h3>Testing</h3>
<p>
After your program has compiled successfully it will be executed and its output compared to the expected output. Before comparing the output, the exit status of your program is checked: if your program gives the correct answer, but exits with a non-zero exit code, the result will be a run-error! There are some restrictions during execution. If your program violates these, it will also be aborted with a run-error, see section <q>Restrictions</q>. <br />

The output of you problem has to match the specifications given on the problem set. Mostly, it has to match the solution exactly; in case of problem statements which do not have unique output (e.g. with floating point answers), the problem set will contain information about the expected solution.
</p>

<h3>Restrictions</h3>
<p>
In order to keep the judging system stable, prevent abuse and give everyone clear and equal environments, there are some restrictions to which all submissions are subjected:
</p>

<ul>
  <li style="text-indent: -1em; padding-left: 1em;"><b>compile time</b> Compilation of your program may take no longer than 30 seconds. After
that compilation will be aborted and the result will be a compile error. In practice
this should never give rise to problems. Should this happen to a normal program,
please inform the course instructors right away.</li>

  <li style="text-indent: -1em; padding-left: 1em;"><b>source size</b> The total amount of source code in a single submission may not exceed <?php echo $config['sourcesize_limit']?>kilobytes, otherwise your submission will be rejected. Furthermore, only up to <?php $config['sourcefiles_limit']?> files are allowed in a single submission.</li>

  <li style="text-indent: -1em; padding-left: 1em;"><b>memory</b> During execution of your program, there are <?php echo $config['memory_limit']/(1024*1024); ?>GB of memory available. This is the total amount of memory (including program code, statically and dynamically defined variables, stack, Java VM (up to 0.35GB), ...)! If your program tries to use more memory, it will abort, resulting in a run-error.</li>

  <li style="text-indent: -1em; padding-left: 1em;"><b>number of processes</b> You are not supposed to create multiple processes (threads). This would be to no avail anyway, since your program has only 1 processor fully at its disposal. To increase stability of the judging system, there is a maximum of 15 processes that can be run simultaneously (including processes that started your program).<br />
  People who have never programmed with multiple processes (or have never heard of <q>threads</q>) do not have to worry: a normal program runs in one process.</li>
  
</ul>

<p>
Furthermore, any tampering with the system will result in penalties. Please also keep the number of your submissions at an acceptable level as to not unnecessarily slow judging for all participants.
</p>

<h3>Do not fool the Judge</h3>
<p>
Do not fool with the system. Do not try to do anything you are obviously not intended to do, for instance opening files, using network connections, hacking our system etc. Submissions with such bevaviour will be killed by the judge and we may remove points from your score or apply other penalties.<br />
Also, do not share code. You are free to discuss algorithms and problems, but please do not share solutions or code lines.<br />
<b>Have fun!</b>
</p>

<?php
require(LIBWWWDIR . '/footer.php');
