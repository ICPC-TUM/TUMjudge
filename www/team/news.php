<?php

/*Remember the last time we watched the news, used to disply "new" label if  there are new news. */
setcookie("lastNewsVisit", time(), time()+1000*24*60*60);

require('init.php');
require(LIBWWWDIR . '/header.php');

?>

<script>
window.fbAsyncInit = function() {
	FB.init({
		xfbml: true,
		version: 'v2.5'
		});
	};
	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s);
		js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
</script>                                        

<?php

foreach(json_decode(file_get_contents('https://icpc.tum.de/news/latest')) AS $news) {
?>
<div class="img-rounded" style="height: 300px; width: 100%; background-color: #002143; background-image: url(<?php echo $news->image_url; ?>); background-position: center; margin: 2em 0; position: relative;">
  <div class="container">
    <div class="carousel-caption">
      <h1 style="margin-bottom: 30px; text-shadow: 0 0 4px black;"><?php echo $news->title; ?></h1>
      <p style="text-shadow: 0 0 2px black;"><?php echo $news->description; ?></p>
    </div>
  </div>
</div>
<?php echo $news->content; ?>
<?php
}

require(LIBWWWDIR . '/footer.php');

