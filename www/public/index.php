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

if(DOMSERVER_REPLICATION === 'master') {
  header('Location: register.php');
  exit;
}

$menu = true;
require(LIBWWWDIR . '/header.php');

?>

<?php if(!logged_in()) { ?>
<div id='login-container' class='navbar'>
<h2>Login</h2>
<form action="login.php" method="post" class="form-vertical">
<input type="hidden" name="cmd" value="login" />
<div class="form-group">
    <label for="login">User</label>
    <input type="text" class="form-control" id="login" name="login" value="" placeholder="User" autofocus>
</div>

<div class="form-group">
    <label for="passwd">Password</label>
    <input type="password" class="form-control" id="passwd" name="passwd" value="" placeholder="Password">
</div>

<div class="form-group" style="float:right; padding-top:0.5em;">
    <label style="font-size:11px;"><a href='/main/' style='color:#ffffff;'>Register new Account</a></label>
</div>

<button type="submit" class="btn btn-default">Login</button>
    
</form>
</div>
<?php } 

global $DB;

//Get judge config
$config = $DB->q('KEYVALUETABLE SELECT `name` AS ARRAYKEY,`value` FROM configuration');

//Get active languages
$data_lang = $DB->q('KEYTABLE SELECT `langid` AS ARRAYKEY,`name`,`compile_script` FROM `language` WHERE `allow_submit` =  1');

$compile_command = array();
$version = array();
$sample_code = array();

// JAVA 
$compile_command['Java'] = 'javac -encoding UTF-8 -d . "$@" 2> "$TMPFILE" <br />';
$compile_command['Java'] .= sprintf("java -client -Xss8m -Xmx%dk -DONLINE_JUDGE=1 -DDOMJUDGE=1 '\$MAINCLASS'", $config['memory_limit']-350000);
$version['Java'] = 'java version "1.8.0_121""<br />Java(TM) SE Runtime Environment (build 1.8.0_91-b14)<br />Java HotSpot(TM) 64-Bit Server VM (build 25.121-b13, mixed mode)';
$sample_code['Java'] = 'import java.util.Scanner;

public class HelloWorld {
	public static void main(String[] args) {
		// create scanner object
		Scanner s = new Scanner(System.in);
		
		// read several types of input
		int i = s.nextInt();
		boolean b = s.nextBoolean();
		String st = s.next();
		
		// output: use the possibility you like more
		System.out.println("Case #"+i+": "+st);
		System.out.format("Case %d#: %s\n", i, s);
	}
}';

// C
$compile_command['C'] = 'gcc -x c -Wall -O2 -static -pipe -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@" -lm'; 
  
// C#
$compile_command['C#'] = 'gmcs -o+ -d:ONLINE_JUDGE,DOMJUDGE -out:"$DESTCLI" "$@"
mono "$DESTCLI"';
  
// C++
$compile_command['C++'] = 'g++ -Wall -std=c++11 -O2 -static -pipe -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 
$version['C++'] = 'Target: x86_64-linux-gnu<br />gcc version 4.9.2 (Debian 4.9.2-10)';
$sample_code['C++'] = '#include <iostream>
#include <stdio.h>

int main() {
	// read several types of input
	int i, j;
	std::string s1;
	char s2[101];

	// use the possibility you like more
	std::cin >> i >> s1;
	scanf("%d %100s", &j, s2);

	// output: use the possibility you like more
	std::cout << "Case #" << i << ": " << s1 << std::endl;
	printf("Case #%d: %s", j, s2);

	return 0;
}';

// Lua
$compile_command['Lua'] = 'lua "$MAINSOURCE"'; 
  
// Octave
$compile_command['Octave'] = 'octave --silent --no-gui --no-history --no-init-file --no-init-path --no-line-editing --no-site-file --no-window-system --norc "$MAINSOURCE"'; 
$version['Octave'] = 'GNU Octave, version 3.8.2';
$sample_code['Octave'] = '#read input with scanf or input
i = scanf("%d", 1);
j = input("");

#print using printf or disp
printf("Case #%d: %d\n", i, j+2);
disp(i*j);';

// Ada
$compile_command['Ada'] = 'gnatmake -static -o "$DEST" "$@" -bargs -static'; 
  
// AWK
$compile_command['AWK'] = 'awk -v ONLINE_JUDGE=1 -v DOMJUDGE=1 -f "$MAINSOURCE"'; 

// Bash Shell
$compile_command['Bash shell'] = 'bash "$MAINSOURCE"'; 

//Fortran
$compile_command['Fortran'] = 'gfortran -static -Wall -O2 -cpp -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 

// Haskell
$compile_command['Haskell'] = 'ghc -Wall -Wwarn -O -static -optl-static -optl-pthread -DONLINE_JUDGE -DDOMJUDGE -o "$DEST" "$@"'; 

// Pascal
$compile_command['Pascal'] = 'fpc -viwn -O2 -Sg -XS -dONLINE_JUDGE -dDOMJUDGE -o"$DEST" "$MAINSOURCE"'; 
  
// Perl
$compile_command['Perl'] = 'perl "$MAINSOURCE"'; 
  
// Prolog
$compile_command['Prolog'] = 'swipl --goal=main,halt --stand_alone=true -o "$DEST" -c "$MAINSOURCE"'; 

//Python 2
$compile_command['Python 2'] = 'pypy "$MAINSOURCE"'; 
$version['Python 2'] = 'Python 2.7.8 (2.4.0+dfsg-3, Dec 20 2014, 13:30:46)<br />[PyPy 2.4.0 with GCC 4.9.2]';
$sample_code['Python 2'] = '#read input
i,j=map(int, raw_input().split())
string1=raw_input()

#output
print "Case %d: %s"%(i,string1)';

// Python 3
$compile_command['Python 3'] = 'python3 "$MAINSOURCE"'; 

// Ruby
$compile_command['Ruby'] = 'ruby "$MAINSOURCE"'; 

// Scala
$compile_command['Scala'] = 'MAINCLASS="$(basename "$MAINSOURCE" .scala)
scala \'$MAINCLASS\''; 
  
// POSIX Shell
$compile_command['POSIX shell'] = 'sh "$MAINSOURCE"'; 

?>

<h1>Welcome to TUMjudge!</h1>

<p>This page contains basic information regarding this TUMjudge. If you never worked with the TUMjudge before we recommend you to read this manual first.</p>

<h2>Login</h2>
The TUMjudge requires you to login prior to submitting work. Your username and password is the same as in the <q>Rechnerhalle</q>, the login works via the LDAP protocol. 

Reminder: The <q>Rechnerhalle</q> username is the part before the @ in your @in.tum.de email address.This also means that we are not responsible for your password. If you lose it, please contact the <a href="http://www.in.tum.de/rbg.html" target="_blank">RBG</a>, not us. If you have several @in.tum.de addresses any of them will work. However, make sure that you use the same address for registration and login.

<h2>Judge</h2>
We use a fork of the official DOMjudge system that is also used in contests like the ICPC. Some impressions of the systems are given in the following images.

<div style="width=100%; text-align:center; padding-top:2em;">
  <a href='../images/team-overview-own.png'><img src="../images/team-overview-own.png" style=' width:40%; margin:0px 30px 0px 0px;'></a>
  <a href='../images/team-scoreboard-own.png'><img src="../images/team-scoreboard-own.png" style=' width:40%;'></a>
</div>

<h2>Problem Statements</h2>

<p>Most problems that you will find here follow an overall structure. They contain:</p>

<ul>
  <li>A Name,
  <li>a story that describes the problem in informal terms,</li>
  <li>a more precise description of the input format,</li>
  <li>a more precise description of the output format,</li>
  <li>constraints on the variables that appear in the input/output,</li>
  <li>some sample cases with solutions.</li>
</ul>

<h2>Submitting Solutions</h2>

<p>
Solutions can be submitted from your dashboard which is available under <q>home</q> once you are logged in. In the left column click <b>Select file...</b> to select the file(s) for submission. TUMjudge will try to determine the problem and language from the base and extension of the filename respectively. If the automatic recognition fails select the appropriate values using the dropdown menues below the file selection. Filenames must start with an alphanumerical character and may contain only alphanumerical characters, - and _.
</p>

<p>
After you hit the submit button and confirm the submission, you will be redirected back to your submission list page. On this page, a message will be displayed indicating that your submission was successful and is now present in the list on the left hand side. An error message will be displayed if something went wrong.
</p>

<p>
Please note that you have to submit the source code of your program, <i>not a compiled program or the output of your program</i>. Your submission will be judged fully automated. Keep in mind that the sample data you receive in the problem statement is merely a fraction of the complete set of sample cases which is kept secret. This means that you have to think about special/corner cases that could be contained in the secret inputs, for example a graph without any edges or containing multiedges.
</p>

<h2>Viewing the results of submissions</h2>

<p>
The top of the page shows your current position in the scoreboard as well as which problems you attempted and solved.
</p>

<p>
The left column of your dashboard shows an overview of your submissions. It contains the submission time, programming language, problem and status of the submission. 
<?php if ($config['show_sample_output'] == 1) {echo 'To view the output your submission on the sample input from the problem set click on your submission.';} ?>
</p>

<p>
To view the public scoreboard use the link <q>scoreboard</q> in the top menu. It displays the scores of all participants that agreed to have their results publicly available. The scoreboard contains one column for each problem. The colors of the cells indicate the following:
</p>

<ul>
  <li>A <b>green</b> field means that he or she solved the problem.</li>
  <li>A <b>dark green</b> field means that he or she solved the problem <b>first</b>.</li>
  <li>A <b>red</b> field means that he or she tried the problem, but could not solve it (yet).</li>
  <li>A <b>white</b> field means that he or she did not try to solve the problem (yet).</li>
</ul>

<p>
Each cell contains the number of submissions for this problem and if the problem was solved, the time of the first correct submission in minutes since the problem set was handed out. The scoreboard is ordered by the number of problems solved, ties are broken using a score that is computed as follows: For each solved problem, you receive a penalty score. This score is equal to the time of the first correct submission in minutes since the problem set was handed out plus <?php echo $config['penalty_time'];?> for each failed attempt. The total penalty score is the sum of penalty scores for all solved problems. Both the total number of correct submissions and the current total penalty score can be found in the colume <q>Score</q>.
</p>

<p>
The score board is visible to everyone, if you want your account NOT to be visible on the scoreboard, please pick <q>invisible</q> during the registration.
</p>

<h2>Clarifications</h2>

<p>
All questions regarding the problem sets have to be sent through the clarification system, <i>not via email</i>. The clarification overview can be found in the right column on your dashboard which is available under <q>home</q> in the top menu. Both clarification replies from the jury and requests sent by you are displayed there.
</p>

<p>
In order to submit new clarifications click the button <q>request clarification</q> at the bottom of the dashboard. This request is only readable for the jury which will respond as soon as possible. Answers that are relevant for everyone will be sent to everyone.
</p>

<h2>Submissions</h2>
<h3>Programming Languages</h3>
<p>
You will be allowed to submit solutions in <?php 

$i = 1;
foreach($data_lang as $lang) {
  if($i == 1) {
    echo $lang['name'];
  }elseif($i > 1 && $i < sizeof($data_lang)) {
    echo ', ' . $lang['name'];
  } else {
    echo ' or ' . $lang['name'];
  }
  $i++;
}

?>. Solutions have to read all input from <q>standard in</q> and write all output to <q>standard out</q> (also known as console). You will never have to open (other) files.
</p>

<h3>Compiling</h3>

<p>
Your program will be compiled on a computer running Linux. All submitted source files will be passed to the compiler which generates a single program to run out of them; for languages where this is relevant, the first specified file will be considered the <q>main</q> source file.
</p>

<p>
Using a different compiler or operating system than the judging system should not be a problem. Be careful however, not to use any special compiler and/or system specific things (you are able to check compiler errors on your dashboard). The compile settings can be found below:
</p>

<ul>
<?php 
  foreach($data_lang as $lang) {
    printf('<li>%s<br /> <pre>%s: </pre></li>', $lang['name'], htmlentities($compile_command[$lang['name']]));
  } 
?>
</ul>

<p>We use the following versions:</p>
<ul>
<?php
  foreach($data_lang as $lang) {
    printf('<li>%s: <pre>%s</pre></li>', $lang['name'], htmlentities($version[$lang['name']]));
  }
?>
</ul>

<p>Sample Code:</p>

<ul>
<?php
foreach($data_lang as $lang) {
  printf('<li>%s: <pre>%s</pre></li>', $lang['name'], htmlentities($sample_code[$lang['name']]));
}
?>
</ul>

<h3>Testing</h3>
<p>
After your program has compiled successfully it will be executed and its output compared to the expected output. Before comparing the output, the exit status of your program is checked: if your program gives the correct answer, but exits with a non-zero exit code, the result will be a run-error! There are some restrictions during execution. If your program violates these, it will also be aborted with a run-error, see section <q>Restrictions</q>. The output of you problem has to match the specifications given on the problem set. Mostly, it has to match the solution exactly; in case of problem statements which do not have unique output (e.g. with floating point answers), the problem set will contain information about the expected solution.
</p>

<h3>Possible Results</h3>
<p>
Once you upload your code to our server, there are several possible outcomes that TUMjudge could tell you.
</p>

<ul class="indent_list">
  <li><b>CORRECT</b> This is the best case. You have solved the problem correctly.</li>
  <li><b>TIMELIMIT</b> There is at least one of our testcases where your program takes too long to produce an answer.</li>
  <li><b>WRONG ANSWER</b> There is at least one of our testcases where your program yields a wrong solution.</li>
  <li><b>COMPILER-ERROR</b> TUMjudge could not compile your program. Make sure you chose the right language and all your includes are there. Here you can also see the exact error message.</li>
  <li><b>NO-OUTPUT</b> Your program compiles, but yields no output. Make sure you write the solution to <q>standard out</q>.</li>
  <li><b>TOO-LATE</b> You submitted your program after the contest was already over. Bummer.</li>
</ul>


<h3>Restrictions</h3>
<p>
In order to keep the judging system stable, prevent abuse and give everyone clear and equal environments, there are some restrictions to which all submissions are subjected:
</p>

<ul class="indent_list">

  <li><b>Compile time</b>: Compilation of your program may take no longer than <?php echo $config['script_timelimit'];?> seconds. After that compilation will be aborted and the result will be a compile error. In practice this should never give rise to problems. Should this happen to a normal program, please inform the course instructors right away.</li>

  <li><b>Source size</b>: The total amount of source code in a single submission may not exceed <?php echo $config['sourcesize_limit']?>KB, otherwise your submission will be rejected. Furthermore, only up to <?php $config['sourcefiles_limit']?> files are allowed in a single submission.</li>

  <li><b>Memory</b>: During execution of your program, there are <?php echo $config['memory_limit']/(1024*1024); ?>GB of memory available. This is the total amount of memory (including program code, statically and dynamically defined variables, stack, Java VM (up to 0.35GB), ...)! If your program tries to use more memory, it will abort, resulting in a run-error.</li>

  <li><b>Number of processes</b>: You are not supposed to create multiple processes (threads). This would be to no avail anyway, since your program has only 1 processor fully at its disposal. To increase stability of the judging system, there is a maximum of <?php echo $config['process_limit'];?> processes that can be run simultaneously (including processes that started your program). People who have never programmed with multiple processes (or have never heard of <q>threads</q>) do not have to worry: a normal program runs in one process.</li>
  
</ul>

<p>
Furthermore, any tampering with the system will result in penalties. Please also keep the number of your submissions at an acceptable level as to not unnecessarily slow judging for all participants.
</p>

<h3>Do not fool the Judge</h3>
<p>
Do not fool with the system. Do not try to do anything you are obviously not intended to do, for instance opening files, using network connections, hacking our system etc. Submissions with such bevaviour will be killed by the judge and we may remove points from your score or apply other penalties. Also, do not share code. You are free to discuss algorithms and problems, but please do not share solutions or code lines.<br />
<b>Have fun!</b>
</p>

<?php
require(LIBWWWDIR . '/footer.php');
