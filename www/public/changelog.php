<?php
require('init.php');
$title = 'Changelog;
require(LIBWWWDIR . '/header.php');
?>

<h2>Impressum</h2>

Unsere Adresse:
 
Institut für Informatik
TU München
Boltzmannstr. 3
D-85748 Garching

<h2>Kontakt</h2>

<a href="mailto:icpc@in.tum.de">icpc@in.tum.de</a>

<h2>Changelog</h2>

<h3>v.4.1.0.2</h3>
<ul>
<li>categrorize problems with topics, authors, difficulty and source</li>
<li>statistics on registration page</li>
<li>changelog/imprint</li>
<li>improved error messages for the registration script</li>
<li>fix: login for registered accounts only</li>
<li>fix: header link after logout</li>
<li>fix: size of login input</li>
</ul>

<h3>v.4.1.0.1</h3>
<ul>
<li>fix: action of login forms</li>
<li>fix: javascript errors removed</li>
<li>fix: tooltips on all pages</li>
</ul>

<h3>v.4.1.0.0</h3>
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
