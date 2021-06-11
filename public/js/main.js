(function ($) {

	"use strict";

	$(window).on('load', function () {

		/* Page Loader active
		========================================================*/
		$('#preloader').fadeOut();

		$('[data-toggle="tooltip"]').tooltip()

		$('[data-toggle="popover"]').popover()

	});

}(jQuery));

var oldExportAction = function (self, e, dt, button, config) {
	if (button[0].className.indexOf('buttons-excel') >= 0) {
		if ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)) {
			$.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
		}
		else {
			$.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
		}
	} else if (button[0].className.indexOf('buttons-print') >= 0) {
		$.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
	}
};


var newExportAction = function (e, dt, button, config) {
	var self = this;
	var oldStart = dt.settings()[0]._iDisplayStart;

	dt.one('preXhr', function (e, s, data) {
		// Just this once, load all data from the server...
		data.start = 0;
		data.length = 2147483647;

		dt.one('preDraw', function (e, settings) {
			// Call the original action function 
			oldExportAction(self, e, dt, button, config);

			dt.one('preXhr', function (e, s, data) {
				// DataTables thinks the first item displayed is index 0, but we're not drawing that.
				// Set the property to what it was before exporting.
				settings._iDisplayStart = oldStart;
				data.start = oldStart;
			});

			// Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
			setTimeout(dt.ajax.reload, 0);

			// Prevent rendering of the full data to the DOM
			return false;
		});
	});

	// Requery the server with the new one-time export settings
	dt.ajax.reload();
};


function getBranchCode(bank_id) {
	$.ajax({
		type: 'get',
		url: getbankUrl + '?bank_id=' + bank_id,
		success: function (res) {
			if (res == 0) {
				$('.bank_error').html('Selected bank is invalid');
				$('#branch_code').val('');
			} else {
				$('#branch_code').val(res.branch_code);
			}
		}
	});
}

function filterNonWorkingDays(date) {
	// Is it a weekend?
	//console.log(date.getDay());
	if (date.getDay() == 0) {
		//console.log(date.getDay() ,date);
		return [false, "weekend"];
	}

	// Is it a holiday?
	show = true;
	for (var i = 0; i < holidays.length; i++) {
		console.log(new Date(holidays[i]).toString(), date.toString());
		if (new Date(holidays[i]).toString() == date.toString()) {

			show = false;
		}//No Holidays
	}

	// It's a regular work day.
	return [show, ""];
}

function getSastTime() {
	var date = new Date();
	var offsetHrs = sastTimeZone / 60;
	var offsetMin = sastTimeZone % 60;

	let hours = date.getUTCHours() + offsetHrs;
	let mins = date.getUTCMinutes() + offsetMin;
	if (hours > 23) hours = 24 - hours
	if (hours < 0) hours = 24 + hours

	if (mins > 59) mins = 60 - mins
	if (mins < 0) mins = 60 + mins
	//return `${hours}:${mins}:${date.getUTCSeconds()}`
	return parseInt(hours) * 100 + parseInt(mins);
}

function businessDayOffset(businessDay) {
	var offsetDay = 0;
	var workingDays = 0;
	var currentDay = new Date();
	currentDay.setHours(0, 0, 0, 0)
	while (workingDays !== businessDay) {

		var dayOfTheWeek = currentDay.getDay();
		//if it is not a sunday
		if (dayOfTheWeek !== 0) {
			var isHoliday = false;
			for (var i = 0; i < holidays.length; i++) {
				if (new Date(holidays[i]).toString() == currentDay.toString()) {
					//it is a holiday
					isHoliday = true;
				}
			}
			if (isHoliday === false) {

				workingDays++;
			}
		}
		offsetDay++;
		currentDay.setTime(currentDay.getTime() + 1 * 24 * 60 * 60 * 1000);
	}
	return offsetDay;
}
function getCollectionDate(service_type, checkTime, collectionType) {
	var cuttOffMissed = false;
	if (checkTime == true && getSastTime() >= parseInt(bankingCutoffTime)) {
		cuttOffMissed = true;
	}

	$('.recurringEntity').attr("readonly", false);

	if (service_type == "Same Day") {
		var minDayOffset = normalSameDayCalOffset;
		if (cuttOffMissed) {
			minDayOffset++;
		}
		minDayOffset = '+' + businessDayOffset(minDayOffset) + 'd';
		$('#collection_date').datepicker({
			minDate: minDayOffset,
			//maxDate: minDayOffset,
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			//daysOfWeekDisabled: [0,6],
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});
		$('#collection_date').datepicker("setDate", new Date());
		$('.recurringEntity').val('');
		$('.recurringEntity').attr("readonly", true);
	}


	if (service_type == "1 Day" || service_type == "2 Day") {
		if (service_type == "2 Day" && collectionType == "reoccur") {
			var minDayOffset = reocurTwoDayCalOffset;
			if (cuttOffMissed) {
				minDayOffset++;
			}
		}

		if (service_type == "2 Day" && collectionType == "normal") {
			var minDayOffset = normalTwoDayCalOffset;
			if (cuttOffMissed) {
				minDayOffset++;
			}
		}

		if (service_type == "1 Day" && collectionType == "normal") {
			var minDayOffset = normalOneDayCalOffset;
			if (cuttOffMissed) {
				minDayOffset++;
			}
		}
		//alert(minDayOffset);
		minDayOffset = '+' + businessDayOffset(minDayOffset) + 'd';
		if ($('#collection_date').length > 0) {
			$('#collection_date').datepicker({
				minDate: minDayOffset,
				dateFormat: 'yy-mm-dd',
				changeYear: true,
				beforeShowDay: filterNonWorkingDays,
				changeMonth: true
			});
		}

		if ($('#recurring_start_date').length > 0) {
			$('#recurring_start_date').datepicker({
				minDate: minDayOffset,
				dateFormat: 'yy-mm-dd',
				changeYear: true,
				//beforeShowDay: filterNonWorkingDays,
				changeMonth: true
			});
		}
	}
}

function getCollectionDate_old(service_type, checkTime) {

	if (checkTime == true && service_type == "Same Day" && getSastTime() >= parseInt(bankingCutoffTime)) {
		alert("Sorry , Cut-off time to send Same Day transmission is over. Please try other service");
		$('#service_type').val("");
		return false;
	}

	$('.recurringEntity').attr("readonly", false);

	if (service_type == "Same Day") {

		$('#collection_date').datepicker({
			minDate: '+0d',
			maxDate: '+0d',
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			//daysOfWeekDisabled: [0,6],
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});
		$('#collection_date').datepicker("setDate", new Date());
		$('.recurringEntity').val('');
		$('.recurringEntity').attr("readonly", true);
	}


	if (service_type == "1 Day") {
		$('#collection_date').datepicker({
			minDate: '+1d',
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});

		$('#recurring_start_date').datepicker({
			minDate: '+1d',
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});
	}

	if (service_type == "2 Day") {
		$('#collection_date').datepicker({
			minDate: '+2d',
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});
		$('#recurring_start_date').datepicker({
			minDate: '+2d',
			dateFormat: 'yy-mm-dd',
			changeYear: true,
			beforeShowDay: filterNonWorkingDays,
			changeMonth: true
		});
	}
}

function dateToGMT(date = new Date(), offsetHrs = 0, offsetMin) {
	let hours = date.getUTCHours() + offsetHrs;
	let mins = date.getUTCMinutes() + offsetMin;
	if (hours > 23) hours = 24 - hours
	if (hours < 0) hours = 24 + hours

	if (mins > 59) mins = 60 - mins
	if (mins < 0) mins = 60 + mins
	return `${hours}:${mins}:${date.getUTCSeconds()}`
}

function verifySubproductVisiblity() {
	var isPaymentValue = $("input[name='is_payment']:checked").val();
	var isCollectionValue = $("input[name='is_collection']:checked").val();
	var isAVSValue = $("input[name='is_avs']:checked").val();

	if (isPaymentValue == 1 || isPaymentValue == true) {
		$("#paymentProducts").removeClass('d-none');
	} else {
		$("#paymentProducts").addClass('d-none');
	}
	if (isCollectionValue == 1 || isCollectionValue == true) {
		$("#collectionProducts").removeClass('d-none');
	} else {
		$("#collectionProducts").addClass('d-none');
	}
	if (isAVSValue == 1 || isAVSValue == true) {
		$("#avsProducts").removeClass('d-none');
	} else {
		$("#avsProducts").addClass('d-none');
	}
}

function confirmDialog(message, handler) {
	$(`<div class="modal fade" id="customConfirmModal" role="dialog"> 
     <div class="modal-dialog modal-dialog-centered"> 
       <!-- Modal content--> 
        <div class="modal-content"> 
           <div class="modal-body" style="padding:10px;"> 
             <h4 class="text-center">${message}</h4> 
             <div class="text-center"> 
               <a class="btn btn-success btn-yes">yes</a> 
               <a class="btn btn-danger btn-no">no</a> 
             </div> 
           </div> 
       </div> 
    </div> 
  </div>`).appendTo('body');

	//Trigger the modal
	$("#customConfirmModal").modal({
		backdrop: 'static',
		keyboard: false
	});

	//Pass true to a callback function
	$(".btn-yes").click(function () {
		handler(true);
		$("#customConfirmModal").modal("hide");
	});

	//Pass false to callback function
	$(".btn-no").click(function () {
		handler(false);
		$("#customConfirmModal").modal("hide");
	});

	//Remove the modal once it is closed.
	$("#customConfirmModal").on('hidden.bs.modal', function () {
		$("#customConfirmModal").remove();
	});
}

function alertDialog(message, handler) {
	$(`<div class="modal fade" id="customAlertModal" role="dialog"> 
     <div class="modal-dialog modal-dialog-centered"> 
       <!-- Modal content--> 
        <div class="modal-content"> 
           <div class="modal-body" style="padding:10px;"> 
             <h4 class="text-center">${message}</h4> 
             <div class="text-center"> 
               <a class="btn btn-success btn-yes">Ok</a> 
             </div> 
           </div> 
       </div> 
    </div> 
  </div>`).appendTo('body');

	//Trigger the modal
	$("#customAlertModal").modal({
		backdrop: 'static',
		keyboard: false
	});

	//Pass true to a callback function
	$(".btn-yes").click(function () {
		handler(true);
		$("#customAlertModal").modal("hide");
	});



	//Remove the modal once it is closed.
	$("#customAlertModal").on('hidden.bs.modal', function () {
		$("#customAlertModal").remove();
	});
}

function setFeeCollectionDatepickers(checkTime) {
	var cuttOffMissed = false;
	if (checkTime == true && getSastTime() >= parseInt(bankingCutoffTime)) {
		cuttOffMissed = true;
	}


	var minDayOffset = reocurTwoDayCalOffset;
	if (cuttOffMissed) {
		minDayOffset++;
	}



	//alert(minDayOffset);
	minDayOffset = '+' + businessDayOffset(minDayOffset) + 'd';

	$('#setup_collection_date').datepicker({
		minDate: minDayOffset,
		dateFormat: 'yy-mm-dd',
		changeYear: true,
		beforeShowDay: filterNonWorkingDays,
		changeMonth: true
	});

	$('#monthly_collection_date').datepicker({
		minDate: minDayOffset,
		dateFormat: 'yy-mm-dd',
		changeYear: true,
		beforeShowDay: filterNonWorkingDays,
		changeMonth: true
	});

}

function setPaymentDatepickers(checkTime) {
	var cuttOffMissed = false;
	var serviceTypeValue = $("input[name='service_type']:checked").val();
	var minDayOffset = 0;
	if (serviceTypeValue == 'dated') {
		var paymentCutOffTime = oneDayPaymentCutoff;
		minDayOffset = oneDayPaymentOffsetDay;
	} else if (serviceTypeValue == 'sameday') {
		var paymentCutOffTime = sameDayPaymentCutoff;
		minDayOffset = sameDayPaymentOffsetDay;
		//$('#collection_date').datepicker("setDate", new Date());
	}

	if (checkTime == true && getSastTime() >= parseInt(paymentCutOffTime)) {
		cuttOffMissed = true;
	}
	if (cuttOffMissed) {
		minDayOffset++;
	}
	//alert(minDayOffset);
	minDayOffset = '+' + businessDayOffset(minDayOffset) + 'd';

	$('#payment_batch_date').datepicker({
		minDate: minDayOffset,
		dateFormat: 'yy-mm-dd',
		changeYear: true,
		beforeShowDay: filterNonWorkingDays,
		changeMonth: true
	});
}

function setCollectionDatepickers(checkTime) {
	var cuttOffMissed = false;
	var serviceTypeValue = $("input[name='service_type']:checked").val();
	var minDayOffset = 0;
	var collectionCutOffTime = bankingCutoffTime;
	if (serviceTypeValue == '1 Day') {
		minDayOffset = normalOneDayCalOffset;
	} else if (serviceTypeValue == '2 Day') {
		minDayOffset = normalTwoDayCalOffset;
		//$('#collection_date').datepicker("setDate", new Date());
	}

	if (checkTime == true && getSastTime() >= parseInt(collectionCutOffTime)) {
		cuttOffMissed = true;
	}
	if (cuttOffMissed) {
		minDayOffset++;
	}
	//alert(minDayOffset);
	minDayOffset = '+' + businessDayOffset(minDayOffset) + 'd';

	$('#collection_batch_date').datepicker({
		minDate: minDayOffset,
		dateFormat: 'yy-mm-dd',
		changeYear: true,
		beforeShowDay: filterNonWorkingDays,
		changeMonth: true
	});
}