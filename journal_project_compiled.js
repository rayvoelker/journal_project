var json_data;function getJournalData(a){blankOutputs();var b=new XMLHttpRequest;b.open("GET",a,!0);b.setRequestHeader("Content-type","application/json");b.send();b.onreadystatechange=function(){if(4==b.readyState&&200==b.status){var a=b.responseText;document.getElementById("data_output").innerHTML="Processing ...";fillOutput(a)}}}
function getItemData(a,b){var c=new XMLHttpRequest;c.open("GET",a,!0);c.setRequestHeader("Content-type","application/json");c.send();c.onreadystatechange=function(){if(4==c.readyState&&200==c.status){var a=c.responseText,h=document.getElementById(b),a=JSON.parse(a);0==a.data.length&&(h.innerHTML+='<span id="helpBlock" class="help-block">no item info found</span>');var f=document.createElement("table");f.className="table";f.style.width="auto";var d=document.createElement("thead"),g=document.createElement("tr"),
k=document.createElement("th"),l=document.createElement("th"),m=document.createElement("th");k.innerHTML="#";l.innerHTML="volume";m.innerHTML="barcode";g.appendChild(k);g.appendChild(l);g.appendChild(m);d.appendChild(g);f.appendChild(d);d=document.createElement("tbody");for(k=0;k<a.data.length;k++){var g=document.createElement("tr"),l=document.createElement("td"),m=document.createElement("td"),n=document.createElement("td");l.innerHTML=k+1;m.innerHTML=a.data[k].volume;n.innerHTML=a.data[k].barcode;
g.appendChild(l);g.appendChild(m);g.appendChild(n);d.appendChild(g);f.appendChild(d)}h.appendChild(f)}}}function blankOutputs(){var a=document.getElementById("info");document.getElementById("page_links");document.getElementById("page_links2");var b=document.getElementById("pager"),c=document.getElementById("data_output");a.innerHTML="&nbsp;";b.innerHTML="&nbsp;";c.innerHTML="Loading ..."}
function fillOutput(a){var b=document.getElementById("info"),c=document.getElementById("page_links"),e=document.getElementById("page_links2"),h=document.getElementById("pager"),f=document.getElementById("data_output");b.innerHTML="&nbsp;";c.innerHTML="&nbsp;";e.innerHTML="&nbsp;";h.innerHTML="&nbsp;";f.innerHTML="Processing ...";json_data=JSON.parse(a);b.innerHTML=json_data.count+" total (bib) records&nbsp;&nbsp;<b>"+json_data.data[0].best_title+"</b>&nbsp;&nbsp;TO&nbsp;&nbsp;<b>"+json_data.data[json_data.data.length-
1].best_title+"</b>";for(a=0;a<json_data.page_links.length;a++)anchor=document.createElement("a"),anchor.className="btn btn-default",a+1==json_data.current_page&&(anchor.className+=" active"),anchor.role="button",anchor.innerHTML=a+1,anchor1=anchor.cloneNode(!0),anchor2=anchor.cloneNode(!0),anchor1.data=json_data.page_links[a],anchor2.data=json_data.page_links[a],anchor1.onclick=function(){getJournalData(this.data)},anchor2.onclick=function(){getJournalData(this.data)},c.appendChild(anchor1),e.appendChild(anchor2);
f.innerHTML="";json_data.jump_list=[];json_data.jump_list_id=[];h=document.getElementById("pager");h.style.fontFamily="monospace";c=document.createElement("div");c.className="btn-group";c.role="group";for(a=0;a<json_data.data.length;a++){e=document.createElement("pre");e.id=json_data.data[a].bib_num;e.innerHTML+="Title:\t\t"+json_data.data[a].best_title+"\nItem Count:\t"+json_data.data[a].item_count+"\nBib number:\t"+json_data.data[a].bib_num;e.data=json_data.data[a].items_api_link;e.onclick=function(){getItemData(this.data,
this.id);this.onclick=null};b=json_data.data[a].best_title_norm.slice(0,3);if(-1==json_data.jump_list.indexOf(b)){json_data.jump_list.push(b);json_data.jump_list_id.push(e.id);var d=document.createElement("a");d.href="#"+e.id;d.innerHTML=b;d.className="btn btn-default";d.role="button";d.style.whiteSpace="pre-wrap";c.appendChild(d)}f.appendChild(e)}h.appendChild(c)}$(document).ready(function(){getJournalData("http://library2.udayton.edu/api/journal_project/bib_item_links.php")});