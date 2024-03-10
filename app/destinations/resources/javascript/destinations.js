
function get_destinations(id, destination_type, action, search) {
	//alert(action);
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			document.getElementById(id).innerHTML = this.responseText;
		}
	};
	if (action) {
		xhttp.open("GET", "/app/destinations/resources/destinations.php?destination_type="+destination_type+"&action="+action, true);
	}
	else {
		xhttp.open("GET", "/app/destinations/resources/destinations.php?destination_type="+destination_type, true);
	}
	xhttp.send();
}

