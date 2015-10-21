<?php

//include header
require('init.php');
require(LIBWWWDIR . '/header.php');
?>
<script language="text/javascript">
  var lastNewsVisit;
  $(function() {
    lastNewsVisit = getLastVisit();
    loadNews();
    setLastVisit();
  });
  
  function setCookie(name, value) {
	var expire = new Date();
	//var expire = new Date(2147483647);
	expire.setDate(expire.getDate() + 10000); // valid for a long time :)
	document.cookie = name + "=" + escape(value) + "; expires=" + expire.toUTCString()+";";
  }

  function getCookie(name) {
	var cookies = document.cookie.split(";");
	for (var i = 0; i < cookies.length; i++) {
		var idx = cookies[i].indexOf("=");
		var key = cookies[i].substr(0, idx);
		var value = cookies[i].substr(idx+1);
		key = key.replace(/^\s+|\s+$/g,""); // trim
		if (key == name) {
			return unescape(value);
		}
	}
	return "";
  }
  
  function getLastVisit() {
    return getCookie("lastNewsVisit");
  }
  
  function setLastVisit() {
    if(!Date.now) {
      Date.now = function() { return new Date().getTime(); }
    }
  
    setCookie("lastNewsVisit" , Math.floor(Date.now() / 1000) );
  }
  
  function loadNews() {
    //Testdata
    /*
    var data = $.parseJSON('[{"title": "abc","content": "<p>dasd sds adsd asd asd sad sad as das</p>","timestamp": 1234},{"title": "abc","content": "<p>dasd sds adsd asd asd sad sad as das</p>","timestamp": 1234}]');
    
    for (index = 0; index < data.length; index++) {
	renderNewsItem(data[index]);
    }
  */
    //TODO: Fix url
    var url = "icpc/news/latest.json";
    $.getJSON(url, function(data) {
      for (index = 0; index < data.length; index++) {
	renderNewsItem(data[index]);
      }
    });
  }
  
  function renderNewsItem(data) {
    if(data['timestamp'] > getLastVisit()) {
      var html = "<h3>" + data['title'] + "</h3>";
      html += data['content'];
      $(html).insertBefore("#news-container>small");
    }
  }
  
</script>

<div id="news-container" class='container'>
  
<small class="text-muted" style="float: right">
	TUMjudge version 4.1.0.4, a fork of DOMjudge version 5.0.1	<a href="../public/changelog.php">Imprint / Changelog</a>
</small>
</div>

<?
require(LIBWWWDIR . '/footer.php');
?>
