var uuid = null;
var fuzzingserver = "http://judge:12477";

function sendSubmission() {
	$("#rrcStartButton").prop("disabled", true);
	$("#rrcStartButton").text("Searching...");
	
	if($("#rrcLogContainer").css("display") == "none")  {
		toggleLog();
	}
	
	var main = "";
	var source = {};
	//Get sourcecode from tabs
	$(".tabberlive:eq(0)>.tabbertab").each(
		function() {			
			var filename = $(this).find(".filename").text();
			
			if(filename != "Run Random Case") {
				var code = ace.edit($(this).find(".ace_editor")[0].id).getValue();
				source[filename] =  code;
			}
		});
	
	var problemName = $("#rrcProblemName").val();
	var lang = $("#rrcSubmissionLanguage").val();
	
	var request = {
	"problem": problemName,
	"lang":lang,
	"main": main,
	"sources": source
	};
	
	$.ajax({
		type: 'POST',
		url: fuzzingserver + "/submission", 
		contentType: 'application/json',
		data: JSON.stringify(request), 
		success: function(response) {
			if (response.success) {
				$("#uuid").val(response.id);
				uuid = response.id;
				$("#rrcLog").append("Waiting for server response... (this might take some seconds)<br />");
				update();
			} else {
				warn("Could not start fuzzing " + response.errors);
				console.log("Could not start fuzzing " + JSON.stringify(response, null, 2));
			}
		},
		error: function(response) {
			warn("Error in fuzzing request.");
			$("#rrcStartButton").prop("disabled", false);
			$("#rrcStartButton").text("Search for a failing testcase");
		}
	});
}

function update() {
$.ajax({
      type: 'GET',
      url: fuzzingserver + "/submission/" + uuid, 
      success: function(response) {
        if (response.success) {
          if (!response.state.finished) {
		$("#rrcLog").html(nl2br(response.state.log));  
		$("#rrcLog").animate({ 
			scrollTop: $("#rrcLog").prop("scrollHeight")
		});  
		
		setTimeout(update, 2000);
          } else {
		$("#rrcLog").html(nl2br(response.state.log));  
		$("#rrcLog").animate({ 
			scrollTop: $("#rrcLog").prop("scrollHeight")
		});  
		  
		reportResult(response);
		
		$("#rrcStartButton").prop("disabled", false);
		$("#rrcStartButton").text("Search for a failing testcase");
		
          }
        } else {
          console.log("Unsuccessful update poll " + JSON.stringify(response, null, 2));
        }
      },
      error: function(response) {
        warn("Error in update request.");
	$("#rrcStartButton").prop("disabled", false);
	$("#rrcStartButton").text("Search for a failing testcase");
      }
    });
}

//TODO: Better warnings
function warn (message) {
	alert(message);
}

function reportResult(response) {
	
	if(response.state.cases == undefined) {
		var html = "<p>An unknown error occurred (most likely a compile-error, see log file for details).</p>";
		$("#rrcResult").html(html);
		return;
	}
	
	if(Object.keys(response.state.cases.rte).length + Object.keys(response.state.cases.wa).length > 0) {
		resulthtml = "<table class='table-hover rrcResults'>";
		resulthtml += "<thead><tr><th>Input</th><th>Expected Output</th><th>Program Output</th><th>Message</th><th>Error Type</th></tr></thead>";
		
		for(var key in response.state.cases.rte) {
			resulthtml += "<tr>";
			
			resulthtml += "<td class='rrcInput'>" + nl2br(response.state.cases.rte[key][key+".in"]) + "</td>";
			resulthtml += "<td class='rrcExpOutput'>" + nl2br(response.state.cases.rte[key][key+".ans"]) + "</td>";
			resulthtml += "<td class='rrcProgOutput'>" + nl2br(response.state.cases.rte[key][key+".out"]) + "</td>";
			resulthtml += "<td class='rrcErrorMessage'></td>";
			resulthtml += "<td class='rrcErrorType'>Run-Error</td>";
			
			resulthtml += "</tr>";
		}
		
		for(var key in response.state.cases.wa) {
			resulthtml += "<tr>";
			
			resulthtml += "<td class='rrcInput'>" + nl2br(response.state.cases.wa[key][key+".in"]) + "</td>";
			resulthtml += "<td class='rrcExpOutput'>" + nl2br(response.state.cases.wa[key][key+".ans"]) + "</td>";
			
			if(response.state.cases.wa[key][key+".diffposition"] != undefined) {
				var split = response.state.cases.wa[key][key+".diffposition"].split(" ");
				var pos1 = split[0];
				var pos2 = split[1];
				
				var output = response.state.cases.wa[key][key+".out"];
				
				resulthtml += "<td class='rrcProgOutput'>" + nl2br(output.substring(0,pos1) + "<b>" + output.substring(pos1, pos2+1) + "</b>" + output.substring(pos2+1,output.length)) + "</td>";
				
			} else {
				resulthtml += "<td class='rrcProgOutput'>" + nl2br(response.state.cases.wa[key][key+".out"]) + "</td>";
			
			}
			
			resulthtml += "<td class='rrcErrorMessage'>" + nl2br(response.state.cases.wa[key][key+".judgemessage"]) + "</td>";
			resulthtml += "<td class='rrcErrorType'>Wrong Answer</td>";
			
			resulthtml += "</tr>";
		}
		
		resulthtml += "</table>";
		
		$("#rrcResult").html(resulthtml);
	} else {
		$("#rrcResult").html("<p>Sorry, no errors found!</p>");
	}
	
	//Hide log when done
	if($("#rrcLogContainer").css("display") != "none") {
		toggleLog();
	}
}

function nl2br(text) {
	return text.replace(/\n/g,"<br />");
}

function toggleLog() {
	if($("#rrcLogButton").text() == "Hide Log") {
		$("#rrcLogContainer").css("display","none");
		$("#rrcLogButton").text("Show Log");
	} else {
		$("#rrcLogContainer").css("display","block");
		$("#rrcLogButton").text("Hide Log");
	}
}