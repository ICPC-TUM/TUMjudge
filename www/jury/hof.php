<?php

require('init.php');

// Include flot javascript library
//$extrahead = '';
$extrahead .= '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="../js/flot/excanvas.min.js"></script><![endif]-->';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.stack.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.time.js"></script>';

$title = 'Hall of Fame';
require(LIBWWWDIR . '/header.php');
echo '<h1>'.$title.'</h1>';

$chartcount = 0;
function printHoF($selector, $heading) {
	global $DB, $sortOrder, $chartcount; 
	echo '<h2>'.$heading.'</h2>';
	$total = $DB->q('SELECT SUM(points) AS points FROM
		contestproblem WHERE cid IN (SELECT cid FROM contest WHERE
		SUBSTRING_INDEX(name, \' - \', 1) LIKE \''.$selector.'\' AND starttime < UNIX_TIMESTAMP() AND public = 1)')->next()['points'];
	echo '<p>total possible points: '.$total.'</p>';
	
	$teams = $DB->q(
	        "SELECT team.teamid, team.name, IFNULL(submissions.points, 0) + IFNULL(bonus.points, 0) AS points, team_category.color, team_affiliation.country FROM team"
        	." LEFT JOIN (SELECT teamid, SUM(points) AS points FROM rankcache_jury WHERE cid IN (SELECT cid FROM contest WHERE SUBSTRING_INDEX(name, ' - ', 1) LIKE '".$selector."' AND starttime < UNIX_TIMESTAMP() AND public = 1) GROUP BY teamid) AS submissions ON submissions.teamid = team.teamid"
        	." LEFT JOIN (SELECT teamid, SUM(points) AS points FROM bonus_points WHERE cid IN (SELECT cid FROM contest WHERE SUBSTRING_INDEX(name, ' - ', 1) LIKE '".$selector."' AND starttime < UNIX_TIMESTAMP() AND public = 1) GROUP BY teamid) AS bonus ON bonus.teamid = team.teamid"
        	." LEFT JOIN team_affiliation ON team.affilid = team_affiliation.affilid"
        	." LEFT JOIN team_category ON team.categoryid = team_category.categoryid"
        	." WHERE IFNULL(submissions.points, 0) + IFNULL(bonus.points, 0) > 0 ORDER BY points DESC"
        );
        echo '<div style="-webkit-column-count: 2; -moz-column-count: 2; columncount: 2;"><ol style="margin: 0;">';
        $counts = array_fill(0,21,0);
        while($team = $teams->next()) {
                echo '<li><img src="../images/countries/'.$team['country'].'.png" alt="'.$team['country'].'" /> '
                .'<a style="background-color: '.$team['color'].';" href="team.php?id='.$team['teamid'].'">'
                .$team['name'].' ('.$team['points'].' points, '.round(100.0*$team['points']/$total).'%)'
                .'</a></li>';
                $counts[round(100.0*$team['points']/$total/5)]++;
        }
        echo '</ol></div>';
        
        $chart = array();
        foreach($counts as $percentage => $count) {
                  $chart[] = [(5*$percentage), $count];
        }
        
        ?>

        <div id="placeholder<?php echo ++$chartcount; ?>" style="width:100%;height:300px; margin-bottom: 5em;"></div>
        
        <script>
        var data<?php echo $chartcount; ?> = <?php echo json_encode($chart) ?>;
        
        $.plot("#placeholder<?php echo $chartcount; ?>", [ data<?php echo $chartcount; ?> ], {
                series: {
                        bars: {
                                show: true,
                                barWidth: 4,
                                align: "center"
                        }
                },
                xaxis: {
                        mode: "categories",
                        tickLength: 0
                }
        });
        </script>
        
        <?php
}

$contests = $DB->q('SELECT DISTINCT SUBSTRING_INDEX(name, \' - \', 1) AS name FROM contest WHERE starttime < UNIX_TIMESTAMP() AND public = 1 ORDER BY cid DESC;');
printHoF('%%', 'All Contests');
while ($contest = $contests->next()) {
	printHoF($contest['name'], $contest['name']);
}

require(LIBWWWDIR . '/footer.php');
?>
