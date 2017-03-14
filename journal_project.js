var json_data;

function getJournalData(page) {
	blankOutputs();

	var request = new XMLHttpRequest();
	request.open('GET', page, true);
	request.setRequestHeader('Content-type', 'application/json');
	request.send();

	request.onreadystatechange = function() {
		if (request.readyState == 4 && request.status == 200) {
			var json_string_output = request.responseText;

			var output = document.getElementById('data_output');
			output.innerHTML = 'Processing ...';

			// debugging
			// create our json data variable with our output
			//json_data = JSON.parse(json_string_output);
			fillOutput(json_string_output);

		}
	};
	//end onreadystatechange
}
//end function getJournalData()


function getItemData(page, target_element_id) {
	var request = new XMLHttpRequest();
	request.open('GET', page, true);
	request.setRequestHeader('Content-type', 'application/json');
	request.send();

	request.onreadystatechange = function() {
		if (request.readyState == 4 && request.status == 200) {
			var json_string_output = request.responseText;
			var element = document.getElementById(target_element_id);

			var json_data = JSON.parse(json_string_output);

			if (json_data.data.length == 0) {
				element.innerHTML += '<span id=\"helpBlock\" class=\"help-block\">no item info found</span>';
			}

			//element.innerHTML += "<div class=\"row\">" +
			//	"<div class=\"col-md-4\">#</div>" +
			//	"<div class=\"col-md-4\">volume</div>" +
			//	"<div class=\"col-md-4\">barcode</div>" +
			//	"</div>";

			//element.innerHTML += "<table class=\"table table-condensed\">" +
			//	"<thead><tr><th>#</th><th>volume</th><th>barcode</th></thead><tbody>";
			var table = document.createElement('table');
			table.className = 'table';
			table.style.width = 'auto';
			var thead = document.createElement('thead');
			var tr = document.createElement('tr');
			var th1 = document.createElement('th');
			var th2 = document.createElement('th');
			var th3 = document.createElement('th');
			th1.innerHTML = '#';
			th2.innerHTML = 'volume';
			th3.innerHTML = 'barcode';

			tr.appendChild(th1);
			tr.appendChild(th2);
			tr.appendChild(th3);

			thead.appendChild(tr);

			table.appendChild(thead);

			var tbody = document.createElement('tbody');

			for (var i = 0; i < json_data.data.length; i++) {
				var tr = document.createElement('tr');
				var td1 = document.createElement('td');
				var td2 = document.createElement('td');
				var td3 = document.createElement('td');
				td1.innerHTML = (i + 1);
				td2.innerHTML = json_data.data[i].volume;
				td3.innerHTML = json_data.data[i].barcode;

				tr.appendChild(td1);
				tr.appendChild(td2);
				tr.appendChild(td3);

				tbody.appendChild(tr);

				table.appendChild(tbody);

				//element.innerHTML += "<tr>" +
				//	"<td>" + (i+1) + "</td>" +
				//	"<td>" + json_data.data[i].volume + "</td>" +
				//	"<td>" + json_data.data[i].barcode + "</td>" +
				//	"</tr>";
			}

			element.appendChild(table);

		}
		//end if

	};
	//end onreadystatechange

}
//end getItemData

function blankOutputs() {
	var info = document.getElementById('info');
	var page_links = document.getElementById('page_links');
	var page_links2 = document.getElementById('page_links2');
	var pager = document.getElementById('pager');
	var data_output = document.getElementById('data_output');

	//blank the outputs
	// consider this a shortcut for now, we really should be looping through and removing all elements
	// so that their event listeners are removed as well
	// http://stackoverflow.com/questions/12528049/if-a-dom-element-is-removed-are-its-listeners-also-removed-from-memory
	info.innerHTML = '&nbsp;';
	//page_links.innerHTML = '&nbsp;';
	pager.innerHTML = '&nbsp;';
	data_output.innerHTML = 'Loading ...';

} //end function blankOutputs();


function fillOutput(json_string) {
	var info = document.getElementById('info');
	var page_links = document.getElementById('page_links');
	var page_links2 = document.getElementById('page_links2');
	var pager = document.getElementById('pager');
	var data_output = document.getElementById('data_output');

	//blank the outputs
	// consider this a shortcut for now, we really should be looping through and removing all elements
	// so that their event listeners are removed as well
	// http://stackoverflow.com/questions/12528049/if-a-dom-element-is-removed-are-its-listeners-also-removed-from-memory
	info.innerHTML = '&nbsp;';
	page_links.innerHTML = '&nbsp;';
	page_links2.innerHTML = '&nbsp;';
	pager.innerHTML = '&nbsp;';
	data_output.innerHTML = 'Processing ...';

	//parse our json string
	json_data = JSON.parse(json_string);

	var results_length = json_data.data.length - 1;

	info.innerHTML = json_data.count + ' total (bib) records&nbsp;&nbsp;<b>' +
		json_data.data[0].best_title + '</b>&nbsp;&nbsp;TO&nbsp;&nbsp;<b>' +
		json_data.data[results_length].best_title + '</b>';

	for (var i = 0; i < json_data.page_links.length; i++) {
		anchor = document.createElement('a');
		anchor.className = 'btn btn-default';
		if ((i + 1) == json_data.current_page) {
			anchor.className += ' active';
		}
		anchor.role = 'button';

		anchor.innerHTML = i + 1;

		//we have to create two clones of the node, for the top and bottom
		// mostly to make the onclick function work
		anchor1 = anchor.cloneNode(true);
		anchor2 = anchor.cloneNode(true);

		//use the data attribute, so we can pass it later for the event listener
		anchor1.data = json_data.page_links[i];
		anchor2.data = json_data.page_links[i];

		anchor1.onclick = function() {
			getJournalData(this.data);
			//alert(this.data);
		};
		anchor2.onclick = function() {
			getJournalData(this.data);
			//alert(this.data);
		};

		page_links.appendChild(anchor1);
		page_links2.appendChild(anchor2);

	} //end for


	//display the actual data
	data_output.innerHTML = '';
	
	//create the jumplist array and the select for it
	json_data.jump_list = [];
	json_data.jump_list_id = [];
	var pager = document.getElementById('pager');
	pager.style.fontFamily = 'monospace';
	var button_group = document.createElement('div');
	button_group.className = 'btn-group';
	button_group.role = 'group';
	
	for (var i = 0; i < json_data.data.length; i++) {
		var pre = document.createElement('pre');
		pre.id = json_data.data[i].bib_num;

		pre.innerHTML += 'Title:\t\t' + json_data.data[i].best_title + '\n' +
			'Item Count:\t' + json_data.data[i].item_count + '\n' +
			'Bib number:\t' + json_data.data[i].bib_num;

		pre.data = json_data.data[i].items_api_link;

		//getItemData(page, target_element_id)
		pre.onclick = function() {
			getItemData(this.data, this.id);
			this.onclick = null;
		};
		
		//fill the jumplist as we encounter new characters, 
		// and create the name 
		var jump = json_data.data[i].best_title_norm.slice(0,3);
		if ( json_data.jump_list.indexOf(jump) == -1){
			json_data.jump_list.push(jump);
			json_data.jump_list_id.push(pre.id);
			var pager_button = document.createElement('a');
			pager_button.href = '#' + pre.id;
			pager_button.innerHTML = jump;
			pager_button.className = 'btn btn-default';
			pager_button.role = 'button';
			pager_button.style.whiteSpace = 'pre-wrap';
			
			button_group.appendChild(pager_button);
		}
		
		//put the data on the page
		data_output.appendChild(pre);

	} //end for loop

	pager.appendChild(button_group);
} //end function fillOutput()

//once the page is fully loaded, run our script
$(document).ready(function() {
	var page = 'http://library2.udayton.edu/api/journal_project/bib_item_links.php';
	getJournalData(page);
});