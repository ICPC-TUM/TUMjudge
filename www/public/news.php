<?php

//include header
require('init.php');
require(LIBWWWDIR . '/header.php');
?>
<script language="javascript">
  var lastNewsVisit;
  $(function() {
    lastNewsVisit = getLastVisit();
    loadNews();
    
  });
  
  function setCookie(name, value) {
	//var expire = new Date();
	var expire = new Date(2147483647);
	//expire.setDate(expire.getDate() + 3); // three days valid
	document.cookie = name + "=" + escape(value) + "; expires=" + expire.toUTCString();
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
  
  function loadNews() {
    var url = "icpc/news/latest.json";
    $.getJSON(url, function(data) {
      for (index = 0; index < data.length; index++) {
	renderNewsItem(data[index]);
      }
    });
  
  }
  
  function renderNewsItem(data) {
    if(data['timestamp'] > getLastVisit()) {
    
    }
    var html = "<h3>" + data['title'] + "</h3>";
    html += data['content'];
    $(".news-container").append(html);
  }
  
</script>

<div id="news-container" style="width:100%;height:100%;">
</div>

<?
require(LIBWWWDIR . '/footer.php');
?>
