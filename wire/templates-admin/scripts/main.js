$(document).ready(function() {

	$(".Inputfields > li > label.ui-widget-header").addClass("InputfieldStateToggle")
		.prepend("<span class='ui-icon ui-icon-triangle-1-s'></span>")
		.click(function() {
			var $li = $(this).parent('li'); 	
			$li.toggleClass('InputfieldStateCollapsed', 200); 
			$(this).children('span.ui-icon').toggleClass('ui-icon-triangle-1-e ui-icon-triangle-1-s'); 
			return false;
		})

	$(".Inputfields .InputfieldStateCollapsed > label.ui-widget-header span.ui-icon")
		.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e'); 

	/*
	$("label.infield").each(function() {

		var $label = $(this); 
		var labelFor = $label.attr('for'); 
		if(labelFor.length < 1) return; 
		var $field = $('#' + labelFor); 
		if(!$field) return; 

		var id = $field.attr('id'); 

                $field.focus(function() {
                        $label.hide();
                }).blur(function() {
                        if($(this).val().length < 1) $label.show();
                }).change(function() {
                        if($(this).val().length < 1) $label.show();
                                else $label.hide();
                });

		$label.parent().css('position', 'relative'); 
		$label.css({
			position: 'absolute', 
			top: '0.25em',
			left: '0.25em', 
			color: '#999999'
		}); 
	
	}).change();
	*/

}); 
