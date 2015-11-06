<?php

require('init.php');
require(LIBWWWDIR . '/header.php');


foreach(json_decode(file_get_contents('https://judge.in.tum.de/news/latest')) AS $news) {
  /*Remember the last time we watched the news, used to disply "new" label if 
  there are new news. */
  setcookie("lastNewsVisit", time(), time()+1000*24*60*60);
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

