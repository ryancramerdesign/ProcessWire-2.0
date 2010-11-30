$(document).ready(function() {
	$fields = $(".InputfieldPageListSelect"); 
	var options = {
		mode: 'select'
	}; 
	$fields.find("input").ProcessPageList(options).hide();
}); 
