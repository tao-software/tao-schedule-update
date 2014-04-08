jQuery(document).ready(function($){
	options = {
				dayNamesMin: TAO_ScheduledChange.datepicker.daynames,//infused by wp_localize script
				monthNames: TAO_ScheduledChange.datepicker.monthnames,//infused by wp_localize script
				dateFormat: 'dd.mm.yy',
				minDate: new Date(),
				showOtherMonths: true,
				firstDay: 1
			};

	$('#' + TAO_ScheduledChange.datepicker.elementid).datepicker(options);

	$('#publish').val(TAO_ScheduledChange.text.save);
});
