var uuid = null;
var fuzzingserver = "http://judge:12477";
var maxTextcaseLength = 100;

function sendSubmission() {
	$("#rrcStartButton").prop("disabled", true);
	$("#rrcStartButton").text("Searching...");
	
	if($("#rrcLogContainer").css("display") == "none")  {
		toggleLog();
	}
	
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
			
			resulthtml += "<td class='rrcInput'>" + nl2br(shorten(response.state.cases.rte[key][key+".in"])) + "<br/>"+
			"<a href='javascript:copyToClipboard(" + response.state.cases.rte[key][key+".in"] + ")'>Copy to Clipboard</a></td>";
			resulthtml += "<td class='rrcExpOutput'>" + nl2br(response.state.cases.rte[key][key+".ans"]) + "</td>";
			resulthtml += "<td class='rrcProgOutput'>" + nl2br(response.state.cases.rte[key][key+".out"]) + "</td>";
			resulthtml += "<td class='rrcErrorMessage'></td>";
			resulthtml += "<td class='rrcErrorType'>Run-Error</td>";
			
			resulthtml += "</tr>";
		}
		
		for(var key in response.state.cases.wa) {
			resulthtml += "<tr>";
			
			resulthtml += "<td class='rrcInput'>" + nl2br(shorten(response.state.cases.rte[key][key+".in"])) + "<br/>"+
			"<a href='javascript:copyToClipboard(" + response.state.cases.rte[key][key+".in"] + ")'>Copy to Clipboard</a></td>";
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

function shorten(text) {
	if(text.length > maxTextcaseLength) return text.substring(0,maxTextcaseLength) + " [...]";
	else return text;
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

//https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript
function copyTextToClipboard(text) {
  var textArea = document.createElement("textarea");

  //
  // *** This styling is an extra step which is likely not required. ***
  //
  // Why is it here? To ensure:
  // 1. the element is able to have focus and selection.
  // 2. if element was to flash render it has minimal visual impact.
  // 3. less flakyness with selection and copying which **might** occur if
  //    the textarea element is not visible.
  //
  // The likelihood is the element won't even render, not even a flash,
  // so some of these are just precautions. However in IE the element
  // is visible whilst the popup box asking the user for permission for
  // the web page to copy to the clipboard.
  //

  // Place in top-left corner of screen regardless of scroll position.
  textArea.style.position = 'fixed';
  textArea.style.top = 0;
  textArea.style.left = 0;

  // Ensure it has a small width and height. Setting to 1px / 1em
  // doesn't work as this gives a negative w/h on some browsers.
  textArea.style.width = '2em';
  textArea.style.height = '2em';

  // We don't need padding, reducing the size if it does flash render.
  textArea.style.padding = 0;

  // Clean up any borders.
  textArea.style.border = 'none';
  textArea.style.outline = 'none';
  textArea.style.boxShadow = 'none';

  // Avoid flash of white box if rendered for any reason.
  textArea.style.background = 'transparent';


  textArea.value = text;

  document.body.appendChild(textArea);

  textArea.select();

  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Copying text command was ' + msg);
  } catch (err) {
    console.log('Oops, unable to copy');
  }

  document.body.removeChild(textArea);
}