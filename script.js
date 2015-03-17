jQuery( function ( $ ) {
	$(".post_list_widget_form_post_list input[type='checkbox']").change(function(){
		var ch=$(this).is(':checked');
		$("div",$(this).parent()).toggle(ch);
	});

	$(".post_list_widget_form_fields_btns span, .post_list_widget_form_taxonomies_btns span").click(function(){
		var div=$(this).parents().filter(".post_list_widget_form_container");
		var textarea=$(".post_list_widget_template_textarea",div);
		textarea.val(textarea.val()+$(this).html());
	});

});

/*
live("click",function(){
			var txtId=jQuery("#<?php echo $this->get_field_id( 'template' );?>");
			txtId.val(txtId.val()+jQuery(this).html());
			*/

