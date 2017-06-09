<?php
require('init.php');
$title = 'Changelog';
require(LIBWWWDIR . '/header.php');
?>

<h2>Impressum</h2>
 
Institut für Informatik<br />
TU München<br />
Boltzmannstr. 3<br />
D-85748 Garching

<h2>Kontakt</h2>

<a href="mailto:icpc@in.tum.de">icpc@in.tum.de</a>

<h2>Changelog</h2>

<h3>v5.1.2.2</h3>
<ul>
<li>update compiler versions</li>
<li>move GCPC instance to new installation</li>
</ul>

<h3>v5.1.2.1</h3>
<ul>
<li>add Christmas layout for ConPra contests</li>
<li>add affiliation icon for IOI DE: <img src="../images/countries/IOI.png" alt="IOI"/>
<li>internal change of submissions that qualify for judging</li>
<li>fix: html entitites in sample codes
</ul>

<h3>v5.1.2.0</h3>
<ul>
<li>update to DOMjudge 5.1.2</li>
<li>add hall of fame for jury users</li>
<li>add more links for jury users</li>
<li>add more affiliation icons</li>
</ul>

<h3>v5.1.1.0</h3>
<ul>
<li>update to DOMjudge 5.1.1</li>
<li>add Octave as supported language</li>
<li>add SSO login for <a href="https://judge.in.tum.de/discuss/">ICPC@TUM Discuss</a>
<li>adjust URLs of some pages</li>
<li>add TUMjudge MLR and ICPC@TUM Discuss to menus</li>
</ul>

<h3>v5.1.0.1</h3>
<ul>
<li>add interface to download and edit submissions for teams</li>
<li>add more trophies for 7 to 12 wins: <img src="../images/countries/BAG.png" alt="7 wins"/> <img src="../images/countries/BAH.png" alt="8 wins"/> <img src="../images/countries/BAI.png" alt="9 wins"/> <img src="../images/countries/BAJ.png" alt="10 wins"/> <img src="../images/countries/BAK.png" alt="11 wins"/> <img src="../images/countries/BAL.png" alt="12 wins"/></li>
<li>add Facebook SDK to news page</li>
</ul>

<h3>v5.1.0.0</h3>
<ul>
<li>update to DOMjudge 5.1.0</li>
<li>add a news page that pulls news from <a href="http://icpc.tum.de">http://icpc.tum.de</a></li>
<li>add four more judge instances</li>
<li>add Halloween and Christmas themes</li>
</ul>

<h3>v5.0.1.0</h3>
<ul>
<li>update to DOMjudge 5.0.1</li>
<li>add links to the team menu, configuration in the admin area</li>
<li>add more premade answers to clarification forms</li>
</ul>

<h3>v5.0.0.0</h3>
<ul>
<li>update to DOMjudge 5.0.0 (points per problem customizable, rejudging, memory/output limits, judgehosts for multiple instances)</li>
<li>completely redesign the website and menu structure</li>
<li>add a help page for contestants with live data</li>
</ul>

<h3>v4.1.0.4</h3>
<ul>
<li>divide contests into categories (future, running, past)</li>
<li>backend UI improvements</li>
<li>fix: points overview</li>
</ul>

<h3>v4.1.0.3</h3>
<ul>
<li>bonus points can be added by the jury</li>
<li>scoreboards may be shuffled for contests</li>
<li>add more servers to TUMjudge</li>
<li>minor layout changes of the front page</li>
</ul>

<h3>v4.1.0.2</h3>
<ul>
<li>categorize problems with topics, authors, difficulty and source</li>
<li>statistics on registration page</li>
<li>changelog/imprint</li>
<li>improved error messages for the registration script</li>
<li>fix: login for registered accounts only</li>
<li>fix: header link after logout</li>
<li>fix: size of login input</li>
</ul>

<h3>v4.1.0.1</h3>
<ul>
<li>fix: action of login forms</li>
<li>fix: javascript errors removed</li>
<li>fix: tooltips on all pages</li>
</ul>

<h3>v4.1.0.0</h3>
<ul>
<li>new layout</li>
<li>multi server setup with replicated user and team tables</li>
<li>registration script and overview of server instances</li>
<li>LDAP login for in.tum.de accounts</li>
<li>points overview for teams</li>
<li>improved statistics with TeX support</li>
</ul>

<?php
require(LIBWWWDIR . '/footer.php');
