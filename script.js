jQuery( function ( $ ) {
	$(document).on('change',".post_list_widget_form_post_list input[type='checkbox']",function(){
		$("div",$(this).parent()).toggle($(this).is(':checked'));
	});

	$(document).on('change',".toggle-master",function(){
		$(".toggle-slave",$(this).parent()).toggle($(this).is(':checked'));
	});

	$(document).on('click',".post_list_widget_form_fields_btns span, .post_list_widget_form_taxonomies_btns span",function(){
		var div=$(this).parents().filter(".post_list_widget_form_container");
		var textarea=$(".post_list_widget_template_textarea",div);
		textarea.val(textarea.val()+$(this).html());
	});

	$(document).on('click',".post_list_widget_form_help img",function(){
		$(this).next().toggle();
	});

});

/*
live("click",function(){
			var txtId=jQuery("#<?php echo $this->get_field_id( 'template' );?>");
			txtId.val(txtId.val()+jQuery(this).html());
			*/

