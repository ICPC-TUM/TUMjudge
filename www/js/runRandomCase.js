$(function() {
	
	
});

var uuid = null;
var fuzzingserver = "http://judge:12477";

function sendSubmission() {
	console.log("Trying to send submission to fuzzing server");
	
	var main = "";
	var source = {};
	//Get sourcecode from tabs
	$(".tabberlive:eq(0) .tabbertab").each(
		function() {
			console.log($(this).find(".ace_editor"));
			
			console.log($(this).find(".filename").text());
			var filename = $(this).find(".filename").text();
			
			//TODO: Figure out a better way to do this
			if(main == "") 
				main = filename;
			
			var code = ace.edit($(this).find(".ace_editor")[0].id).getValue();
			source[filename] =  code;
		});
	
	var problemName = $("#rrcProblemName").val();
	
	var request = {
	"problem": problemname,
	"main": sourcename,
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
	console.log(response);
}

function updateSubmission(id) {
	
}

function cancelSubmission(id) {
	
}