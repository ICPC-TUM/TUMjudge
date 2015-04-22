<?php


//include header
require('init.php');
require(LIBWWWDIR . '/header.php');

echo '<h1>Welcome to TUMjudge!</h1>';
echo 'If you already have an account please choose a contest to participate.';
echo '<div class="row" style="margin-top: 2em;">';
$servers = array(
	array('title' => 'ConPra', 'description' => 'Algorithmen f&uuml;r Programmierwettbewerbe', 'url' => 'conpra'),
        array('title' => 'GAD', 'description' => 'Grundlagen: Algorithmen und Datenstrukturen', 'url' => 'gad'),
);
foreach($servers AS $server) {
echo '<div class="col-xs-6" id="'.$server['title'].'"><div class="jumbotron" style="padding: 20px; color: #ffffff; background-color: #002143; height: 17em;">';
        echo '<a href="/'.$server['url'].'/public/login.php" class="btn btn-default" style="float: right"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> Login</a></p>';
        echo '<h2>'.$server['title'].'</h2>';
        echo '<p class="lead">'.$server['description'].'</p>';
        echo '<div><small class="stats"></small></div>';
        echo '<div><small class="contests">Current Contests: </small></div>';
        echo '</div></div>';
        echo '<script>
            var runningcontest = false;
            $.ajax({url: "/'.$server['url'].'/api/contests"})     
            .done(function(data) {
              runningcontest = false;
              $.each(data, function(id, contest) {
                if(contest.end > new Date().getTime()/1000) {
                  $("#'.$server['title'].' .contests").append("<a href=\"/'.$server['url'].'/\" style=\"color: #ffffff;\">" + contest.name + "</a>");
                  runningcontest = true;
                }
              });
              if(!runningcontest) {
                $("#'.$server['title'].' .contests").append("none");
              }
            });
            $.ajax({url: "/'.$server['url'].'/api/statistics"})
            .done(function(data) {
              $("#'.$server['title'].' .stats").html(data.submissions + " submissions in " + data.contests + " contests by " + data.teams + " teams");
            });
          </script>';
echo '</div>';
}

if(DOMSERVER_REPLICATION != 'master') {
  require(LIBWWWDIR . '/footer.php');
  exit;
}

echo '<h1>Registration</h1>';

//globals
$categories = array();
$res_cat = $DB->q('SELECT categoryid, name FROM team_category WHERE categoryid IN (2,5)');
while(($cat = $res_cat->next()) != null) {
  $categories[$cat['categoryid']] = $cat['name'];
}

$affiliations = array();
$res_aff = $DB->q('SELECT affilid, name FROM team_affiliation WHERE comments = "registration enabled"');
while(($aff = $res_aff->next()) != null) {
  $affiliations[$aff['affilid']] = $aff['name'];
}

function showForm($message = '', $exit = true) {
  global $categories, $affiliations;
  echo '<form method="post" action="register.php">';
  echo '<p>Please fill in the following form to create an account for you. If you are a student at TUM your login should be the login you use in the computer labs. For instance, use "test" if you have the mail address test@in.tum.de. The password to login is the same as in the computer labs.</p>';
  if(!empty($message)) echo '<div class="alert alert-info" role="alert">'.$message.'</div>';
  echo '<p style="font-weight: bold; margin-top: 2em;">Affiliation</p>';
  echo '<select name="affilid" class="form-control">';
  foreach($affiliations as $id => $value) {
    echo '<option value="'.$id.'">'.htmlentities($value).'</option>';
  }
  echo '</select>';
  echo '<p style="font-weight: bold; margin-top: 2em;">Category</p>';
  echo '<select name="categoryid" class="form-control">';
  foreach($categories as $id => $value) {
    echo '<option value="'.$id.'">'.htmlentities($value).'</option>';
  }
  echo '</select>';
  echo '<p style="font-weight: bold; margin-top: 2em;">Login</p>';
  echo '<p><input type="text" name="login" class="form-control" /></p>';
  echo '<p><input type="submit" value="Create account" class="form-control btn btn-success" /></p>';
  echo '</form>';

  require(LIBWWWDIR . '/footer.php');
  if($exit) {
    exit;
  }
}
//show form
if(empty($_POST['login'])) showForm();

//find variables
$login = mysql_escape_string($_POST['login']);
$categoryid = mysql_escape_string($_POST['categoryid']);
if(!in_array($categoryid, array_keys($categories))) showForm('The categroy you selected is invalid.');
$affilid = mysql_escape_string($_POST['affilid']);
if(!in_array($affilid, array_keys($affiliations))) showForm('The affiliation you selected is invalid.');

//connect
$conn = false;
foreach(explode(' ', LDAP_SERVERS) as $server) {
  if($conn == false) {
    $conn = ldap_connect($server);
  }
}
if(!$conn) showForm('The authentification server is not online.');
if(!ldap_bind($conn)) showForm ('The authentification server does not accept connections.');

//find user
switch($affilid) {
  case 1:
    $entry = ldap_get_entries($conn,
      ldap_search($conn, 'ou=Personen,ou=IN,o=TUM,c=DE', '(mail='.$login.'@in.tum.de)')
    );
    //extract user data
    $name = mysql_escape_string($entry[0]['cn'][0]);
    $authtoken = mysql_escape_string($entry[0]['dn']);
    break;
  //ADD MORE AFFILIATIONS HERE
  default:
    showForm('The affiliation you selected is not supported.');
}
if($entry['count'] == 0) showForm('The login you supplied was not found. Please make sure you spelled it correctly.');

//close connection
ldap_close($conn);

//check for user account
$count = array_pop($DB->q(sprintf('SELECT COUNT(*) FROM user WHERE username="%s"', $login))->next());
if($count > 0) showForm('This account is already existing. You can login to it now.');

//create new user account
$teamid = $DB->q(sprintf('RETURNID INSERT INTO team(name, categoryid, affilid, members) VALUES ("%s", "%s", "%s", "%s")', $name, $categoryid, $affilid, $name));
$DB->q(sprintf('INSERT INTO user(username, name, email, teamid) VALUES ("%s", "%s", "%s", %d)', $login, $name, $login.'@in.tum.de', $teamid));

showForm('Your account has been created.');

?>
