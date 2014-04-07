jQuery(document).ready(function($){
	options = {
				dayNamesMin: tao_sc_dp_daynames,//infused by wp_localize script
				monthNames: tao_sc_dp_monthnames,//infused by wp_localize script
				dateFormat: 'dd.mm.yy',
				showOtherMonths: true,
				firstDay: 1
			};

	$('#' + tao_sc_dp_id).datepicker(options);
});
