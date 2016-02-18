$(function() {
	
	
});

var uuid = null;
var fuzzingserver = "http://judge:12477";

function sendSubmission() {
	console.log("Trying to send submission to fuzzing server");
	
	var main = "";
	var source = {};
	//Get sourcecode from tabs
	$(".tabberlive:eq(0)>.tabbertab").each(
		function() {
			
			var filename = $(this).find(".filename").text();
			
			//TODO: Figure out a better way to do this
			if(main == "") 
				main = filename;
			
			if(filename != "Run Random Case") {
				var code = ace.edit($(this).find(".ace_editor")[0].id).getValue();
				source[filename] =  code;
			}
		});
	
	var problemName = $("#rrcProblemName").val();
	var lang = $("#rrcSubmissionLanguage").val();
	
	//TODO: Move to backend
	if(lang == "java") lang = "Java";
	if(lang == "cpp") lang = "C++";
	
	var request = {
	"problem": $("#rccProblemName").val(),
	"lang":lang,
	"main": main,
	"sources": source
	};
	
	console.log(request);
	
	$.ajax({
		type: 'POST',
		url: fuzzingserver + "/submission", 
		contentType: 'application/json',
		data: JSON.stringify(request), 
		success: function(response) {
			if (response.success) {
				$("#uuid").val(response.id);
				uuid = response.id;
				update();
			} else {
				warn("Could not start fuzzing " + response.errors);
				console.log("Could not start fuzzing " + JSON.stringify(response, null, 2));
			}
		},
		error: function(response) {
			warn("Error in fuzzing request.");
		}
	});
}

function update() {
console.log("Trying to update...");
	
$.ajax({
      type: 'GET',
      url: fuzzingserver + "/submission/" + uuid, 
      success: function(response) {
        if (response.success) {
          if (!response.state.finished) {
		$("#rrcLog").append(response.state.log + "\n");  
		  
            setTimeout(update, 2000);
          } else {
		reportResult(response);
          }
        } else {
          console.log("Unsuccessful update poll " + JSON.stringify(response, null, 2));
        }
      },
      error: function(response) {
        warn("Error in update request.");
      }
    });
}

function reportResult(response) {
	
	if(Object.keys(response.state.cases.rte).length + Object.keys(response.state.cases.wa).length > 0) {
		resulthtml = "<table>";
		resulthtml += "<thead><tr><th>Input</th><th>Expected Output</th><th>Program Output</th><th>Error Type</th></tr></thead>";
		
		for(var key in response.state.cases.rte) {
			resulthtml += "<tr>";
			
			resulthtml += "<td class='rrcInput'>" + nl2br(response.state.cases.rte[key][key+".in"]) + "</td>>";
			resulthtml += "<td class='rrcExpOutput'>" + nl2br(response.state.cases.rte[key][key+".ans"]) + "</td>";
			resulthtml += "<td class='rrcProgOutput'>" + nl2br(response.state.cases.rte[key][key+".out"]) + "</td>";
			resulthtml += "<td class='rrcErrorType'>Run-Error</td>";
			
			resulthtml += "</tr>";
		}
		
		for(var key in response.state.cases.wa) {
			resulthtml += "<tr>";
			
			resulthtml += "<td class='rrcInput'>" + nl2br(response.state.cases.rte[key][key+".in"]) + "</td>>";
			resulthtml += "<td class='rrcExpOutput'>" + nl2br(response.state.cases.rte[key][key+".ans"]) + "</td>";
			resulthtml += "<td class='rrcProgOutput'>" + nl2br(response.state.cases.rte[key][key+".out"]) + "</td>";
			resulthtml += "<td class='rrcErrorType'>Wrong Answer</td>";
			
			resulthtml += "</tr>";
		}
		
		resulthtml += "</table>";
		
		$("#rrcResult").append(resulthtml);
	} else {
		$("#rrcResult").append("<p>Sorry, no errors found!</p>");
	}
	
	console.log(response);
}

function nl2br(text) {
	return text.replace(/\n/g,"<br />");
}

function updateSubmission(id) {
	
}

function cancelSubmission(id) {
	
}