var TAOScheduleUpdate = TAOScheduleUpdate || {};

jQuery( document ).ready( function( $ ) {
	var options = {
		dayNamesMin: TAOScheduleUpdate.datepicker.daynames, // Infused by wp_localize script
		monthNames: TAOScheduleUpdate.datepicker.monthnames, // Infused by wp_localize script
		dateFormat: TAOScheduleUpdate.datepicker.dateformat, // Infused by wp_localize script
		minDate: new Date(),
		showOtherMonths: true,
		firstDay: 1,
		altField: '#' + TAOScheduleUpdate.datepicker.elementid,
		altFormat: 'dd.mm.yy'
	};

	$( '#' + TAOScheduleUpdate.datepicker.displayid ).datepicker( options );

	$( '#publish' ).val( TAOScheduleUpdate.text.save );

	$( '#' + TAOScheduleUpdate.datepicker.elementid ).on( 'change', function( evt ) {
		TAOScheduleUpdate.checkTime();
	} );

	$( '#tao_sc_publish_pubdate_time' ).on( 'change', function( evt ) {
		TAOScheduleUpdate.checkTime();
	} );

	$( 'select[name=tao_sc_publish_pubdate_time_mins]' ).on( 'change', function( evt ) {
		TAOScheduleUpdate.checkTime();
	} );
} );

TAOScheduleUpdate.checkTime = function() {
	var $ = jQuery;
	var dt, datestring, currentGmt;

	var pattern = /(\d{2})\.(\d{2})\.(\d{4}) (\d{2})\:(\d{2})/;
	var now = new Date();
	var st = $( '#' + TAOScheduleUpdate.datepicker.elementid ).val();
	var time = $( '#tao_sc_publish_pubdate_time' ).find( ':selected' ).val() + ':' + $( 'select[name=tao_sc_publish_pubdate_time_mins]' ).find( ':selected' ).val();
	st += ' ' + time;

	currentGmt = $( '#tao_used_gmt' ).val();
	datestring = st.replace( pattern, '$3-$2-$1T$4:$5:00' );
	dt = new Date( datestring + currentGmt );

	if ( now.getTime() > dt.getTime() ) {
		$( '#pastmsg' ).show();
	} else {
		$( '#pastmsg' ).hide();
	}
};
