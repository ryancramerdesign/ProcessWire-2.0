$(document).ready(function() {

	var $nameField = $("#Inputfield_name"); 
	if($nameField.val().length) return; // no need to continue since it already has a value

	var $titleField = $(".InputfieldPageTitle input[type=text]"); 
	var active = true; 

	var titleKeyup = function() {
		if(!active) return; 
		var val = $titleField.val().substring(0, 70); 
		var re = new RegExp(/([^_a-z0-9]+)/ig); 
		$nameField.val(val.replace(re, '-')).trigger('keyup'); 
	}

	$titleField.keyup(titleKeyup); 

	$nameField.focus(function() {
		// if they happen to change the name field on their own, then disable 
		if($(this).val().length) active = false;
	}); 
	
}); 
