jQuery(document).ready(function($){
	options = {
				dayNamesMin: tao_sc_dp_daynames,//infused by wp_localize script
				monthNames: tao_sc_dp_monthnames,//infused by wp_localize script
				dateFormat: 'dd.mm.yy',
				showOtherMonths: true,
				firstDay: 1
			};

	$('#' + tao_sc_dp_id).datepicker(options);
	
	$('#publish').val(tao_sc_save);
	$('#delete-action a').text(tao_sc_pubnow_text);
	$('#delete-action a').attr('href', tao_sc_pubnow_link);
	$('#delete-action a').attr('title', tao_sc_pubnow_title);
});
