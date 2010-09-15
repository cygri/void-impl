/******************************************************/
/* vox data-controller and service conmmunicate code  */
/******************************************************/

function renderVoiD(){
	var voidURI = $("#voidURI").val();
	var params = "uri=" + escape(voidURI);
	
	if ($("#forceOverwriteContent").is(':checked')) {
		params += "&overwrite";
	}
	isBusy();
	
	$.ajax({
		type: "GET",
		url: voxServiceURI,
		data: params,
		success: function(data){
			if(data) {
				$("#out").html(data);
				$("#out").show("slow");
			}
			notBusy();
		},
		error:  function(msg){
			alert(msg);
		} 
	});
}

function executeQuery(outcanvas, endpointURI, queryStr) {

	var data =  {
		endpointURI : escape(endpointURI),
		queryStr : queryStr
	}
	
	outcanvas.html("<img src='img/ajax-loader.gif' width='16px' alt='busy />");
	outcanvas.slideDown("slow");

	$.ajax({
		type: "POST",
		url: voxServiceURI,
		data: "qParams="+ $.toJSON(data),
		dataType : "json",
		success: function(result){
			outcanvas.html(renderSPARQLResult(result));
		},
		error:  function(msg){
			alert(msg);
		} 
	});	
}

function renderSPARQLResult(data){
		var vars =  Array();
		var buffer = "<p>Query results:</p>";
				
		// SELECT query result
		if(data.head.vars) {
			buffer += "<table><tr>";
			for(rvar in data.head.vars) { // table head with vars
				buffer += "<th>" + data.head.vars[rvar] + "</th>";
			}
			buffer += "</tr>";
			for(entry in data.results.bindings) { // iterate over rows
				if(entry%2) buffer += "<tr>";
				else buffer += "<tr class='invrow'>";

				for(rvar in data.head.vars) { // iterate over columns per row
					var col = data.head.vars[rvar];
					buffer += "<td>" + data.results.bindings[entry][col].value + "</td>";
				}
				buffer += "</tr>";
			}
			buffer += "</table>";
		}
		// ASK query result
		if(data.boolean) {
			buffer += "<p style='font-size:140%'>The query yields <strong>" + data.boolean + "</strong>.</p>";
		}
		
		return buffer;
}


function executeLookup(outcanvas, endpointURI, queryStr) {

	var data =  {
		endpointURI : escape(endpointURI),
		queryStr : queryStr
	}
	
	//outcanvas.html("<img src='img/ajax-loader.gif' width='16px' alt='busy />");
	//outcanvas.slideDown("slow");

	$.ajax({
		type: "POST",
		url: voxServiceURI,
		data: "lParams="+ $.toJSON(data),
		success: function(result){
				window.open(
					result,
					'lookupResultWindow',
					'left=100,top=100,width=800,height=600,toolbar=0,resizable=1'
				);
		},
		error:  function(msg){
			alert(msg);
		} 
	});	
}



function isBusy(){
	$("#status").show();
	$("#status").html("<img src='img/ajax-loader.gif' id='busy' width='32px' alt='busy />");	
}

function notBusy(){
	$("#status").hide();
	$("#status").html("");	
}