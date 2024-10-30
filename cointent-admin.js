jQuery(function ($) {
	$('.toggle_tray').bind('click', function (e) {
		var $table =  $(this).parent().siblings('.ct_postbox_body');
		$table.slideToggle(500);
		$(this).toggleClass('ct_flipped')

	});
	$('.ct_close').bind('click', function (e) {
		$(this).parent().slideUp();
		//$('#cointent-conf').append('<input type="hidden" id="ct_intro_dismissed" name="Cointent[intro_dismissed]" value="1"/>');
		$.post(ajaxurl, {'action': 'save_dismiss_header','intro_dismissed': 1}, function(response) {
			$('<p>General Settings Saved</p>').appendTo('#cointent-admin').hide(2000);
		});
	})
});