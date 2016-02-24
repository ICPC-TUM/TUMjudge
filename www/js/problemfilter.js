var topics_regex = new Array();
topics_regex['BFS/DFS'] = /[A-Za-z0-9,\s]*(((Depth|Breadth)(\s|-)First(\s|-)Search)|((B|D)FS))[A-Za-z0-9,\s]*/i;
topics_regex['Shortest Path'] = /[A-Za-z0-9,\s]*((Shortest(\s|-)Path)|Dijkstra|Bellman(\s|-)Ford)[A-Za-z0-9,\s]*/i;
topics_regex['Minimum Spanning Tree'] = /[A-Za-z0-9,\s]*((Minimum(\s|-)Spanning(\s|-)Tree)|Prim|Kruskal)[A-Za-z0-9,\s]*/i;
topics_regex['Number Theory'] = /[A-Za-z0-9,\s]*((Chinese(\s|-)Remainder(\s|-)Theorem)|Number(\s|-)Theory|gcd|lcm|Euclidean)[A-Za-z0-9,\s]*/i;
topics_regex['Geometry'] = /^((?!.*projective(\s|-)).*(geometry|convex(\s|-)hull).*)*$/i;
topics_regex['Projective Geometry'] = /[A-Za-z0-9,\s]*(projective(\s|-)geometry)[A-Za-z0-9,\s]*/i;
topics_regex['Dynamic Programming'] = /[A-Za-z0-9,\s]*(dynamic(\s|-)programming)[A-Za-z0-9,\s]*/i;
topics_regex['Union Find'] = /[A-Za-z0-9,\s]*(union(\s|-)find)[A-Za-z0-9,\s]*/i;
topics_regex['Flow'] = /max(imum)?(\s|-)flow|min(imum)?(\s|-)cut|push(\s|-)relabel|goldberg(\s|-)tarjan|dinic/i;
topics_regex['Brute Force'] = /[A-Za-z0-9,\s]*(brute(\s|-)force|backtracking)[A-Za-z0-9,\s]*/i;
topics_regex['Greedy'] = /[A-Za-z0-9,\s]*(greedy)[A-Za-z0-9,\s]*/i;

var topics = [
	{name: "BFS/DFS"},
	{name: "Shortest Path"},
	{name: "Minimum Spanning Tree"},
	{name: "Number Theory"},
	{name: "Geometry"},
	{name: "Projective Geometry"},
	{name: "Dynamic Programming"},
	{name: "Union Find"},
	{name: "Flow"},
	{name: "Brute Force"},
	{name: "Greedy"}
	];
	
var difficulties = new Set();

$(function() {
	$(".list tbody tr td:nth-child(6)").each(function(){
		var items = $(this).text().split(",");
		
		for (var i = 0; i < items.length; i++) {
			var found = false;
			var topic = items[i].trim();
			if(topic == "" || topic == ".") {
				continue;
			}
			for(var t in topics) { 
				if(topics_regex[topics[t].name] != undefined) {
					if(topics_regex[topics[t].name].exec(topic) !== null) {
						found = true;
						break;
					}
				} else {
					if(topics[t].name == topic) {
						found = true;
						break;
					}
				}
			}
			if(!found) topics[topics.length] = {name:topic};
		}
		
	});
	
	$("#topics_filter").tokenInput(topics, {
	  allowFreeTagging:true, 
	  tokenValue: 'name',
	  preventDuplicates: true,
	  minChars: 0,
          hintText: 'Add topic to search for',
          searchingText: 'Searching topics...',
          onAdd: filterProblems,
          onDelete: filterProblems
	});
	$("#problem_filter_container").css("display","none");
	
	$(".list tbody tr td:nth-child(3)").each(function(){
		var difficulty = $(this).text();
		var found = false;
		for(var d of difficulties.values()) {
			if(d.name == difficulty || difficulty == "" || difficulty == ".") {
				found = true;
				break;
			}
		}
		if(!found) difficulties.add({name:difficulty});
	});
	
	$("#difficulty_filter").tokenInput(Array.from(difficulties), {
	  allowFreeTagging:false, 
	  tokenValue: 'name',
	  preventDuplicates: true,
	  minChars: 0,
          hintText: '',
          searchingText: 'Searching difficulties...',
          onAdd: filterProblems,
          onDelete: filterProblems
	});
});

function resetFilter() {
	//Reset table visibility
	$(".list tbody tr").each(function(){$(this).css("display","table-row")});
}

function resetAll() {
	$("#topics_filter").tokenInput("clear");
	resetFilter();
}

function filterProblems() {
	resetFilter()
	filterTopics();
	filterDifficulty();
}

function filterDifficulty() {
	var selected_difficulties = $("#difficulty_filter").tokenInput("get");
	if(selected_difficulties.length === 0) {
		return;
	}
	
	$(".list tbody tr").each(function() {
	   var found = false;
	   for (var i=0;i<selected_difficulties.length;i++) {
		if($(this).find("td:nth-child(3)").text() == selected_difficulties[i].name) {
			found = true;
			break;
		} 
	   }
	   
	   if(!found) {
		 $(this).css("display","none");
	   }
	});
}

function filterTopics() {
	var selected_topics = $("#topics_filter").tokenInput("get");
	if(selected_topics.length === 0) {
		return;
	}
	
	$(".list tbody tr").each(function() {
	   var found = (getFilterMode() == "all");
	   for (var i=0;i<selected_topics.length;i++) {
		var regex = topics_regex[selected_topics[i].name];;
		if(regex === undefined) {
		    regex = new RegExp(selected_topics[i].name);
		}
		
		if(regex.exec($(this).find("td:nth-child(6)").text()) !== null || regex.exec($(this).find("td:nth-child(2)").text()) !== null) {
		    if(getFilterMode() == "one") {
			  found = true;
			  break;
		    }
		} else {
		    if(getFilterMode() == "all") {
			  found = false;
			  break;
		    }
		}
	   }
	   
	   if(!found) {
		 $(this).css("display","none");
	   }
	});
}

function getFilterMode() {
	return $("#filterMode option:selected" ).val();
}

function toggleFilter() {
      if($('#problem_filter_container').css('display') != 'none') {
	  $('#problem_filter_container').css('display','none');
      } else {
	  $('#problem_filter_container').css('display','block');
      }
}
