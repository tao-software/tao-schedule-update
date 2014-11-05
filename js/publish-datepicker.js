jQuery(document).ready(function($){
	options = {
				dayNamesMin: TAO_ScheduleUpdate.datepicker.daynames,//infused by wp_localize script
				monthNames: TAO_ScheduleUpdate.datepicker.monthnames,//infused by wp_localize script
				dateFormat: 'dd.mm.yy',
				minDate: new Date(),
				showOtherMonths: true,
				firstDay: 1
			};

	$('#' + TAO_ScheduleUpdate.datepicker.elementid).datepicker(options);

	$('#publish').val(TAO_ScheduleUpdate.text.save);

	$('#' + TAO_ScheduleUpdate.datepicker.elementid).on('change', function(evt) { TAO_ScheduleUpdate.checkTime(); });
	$('#tao_sc_publish_pubdate_time').on('change', function(evt) { TAO_ScheduleUpdate.checkTime(); });
	$('select[name=tao_sc_publish_pubdate_time_mins]').on('change', function(evt) { TAO_ScheduleUpdate.checkTime(); });
});

TAO_ScheduleUpdate = TAO_ScheduleUpdate || {};

TAO_ScheduleUpdate.checkTime = function() {
	$ = jQuery;

	var now = new Date();
	var st = $('#' + TAO_ScheduleUpdate.datepicker.elementid).val();
	var time = $('#tao_sc_publish_pubdate_time').find(':selected').val() + ':' + $('select[name=tao_sc_publish_pubdate_time_mins]').find(':selected').val();
	st += ' ' + time;
	var pattern = /(\d{2})\.(\d{2})\.(\d{4}) (\d{2})\:(\d{2})/;
	var dt = new Date(st.replace(pattern,'$3-$2-$1T$4:$5:00'));

	dt.setTime(dt.getTime() + dt.getTimezoneOffset()*60000);

	if (dt < now) {
		$('#pastmsg').show();
	} else {
		$('#pastmsg').hide();
	}
};
