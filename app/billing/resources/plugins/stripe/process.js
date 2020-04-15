function reportError(msg) {
	// Show the error in the form:
	var errordiv = document.getElementById('payment-errors');
	errordiv.innerHTML = msg;

	alert (msg);
	var btnSubmit = document.getElementById('submitBtn');
	btnSubmit.disabled = false;
	return false;
}


// Function handles the Stripe response:
function stripeResponseHandler(status, response) {
	
	// Check for an error:
	if (response.error) {
		reportError(response.error.message);
	} 
	else { // No errors, submit the form:
		var form$ = $("#payment-form");
		// token contains id, last4, and card type
		var token = response['id'];
		// insert the token into the form so it gets submitted to the server
		form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
 		// and submit
		form$.get(0).submit();
	}
} 


$(document).ready(function() {
		$("#payment-form").submit(function(event) {
			// disable the submit button to prevent repeated clicks
			$('.submit-button').attr("disabled", "disabled");
			var chargeAmount = 100 * document.getElementById('amount').value;
			// createToken returns immediately - the supplied callback submits the form if there are no errors
			Stripe.createToken({
					number: $('.card-number').val(),
					cvc: $('.card-cvc').val(),
					exp_month: $('.card-expiry-month').val(),
					exp_year: $('.card-expiry-year').val()
				}, chargeAmount, stripeResponseHandler);
			return false; // submit from callback
		});
});



function formSubmit(){
	var error = false;
	var btnSubmit = document.getElementById('submitBtn');
	btnSubmit.disabled = true;

	var ccNum = document.getElementsByClassName('card-number');
	var cvcNum = document.getElementsByClassName('card-cvc');
	var expMonth = document.getElementsByClassName('card-expiry-month');
	var expYear = document.getElementsByClassName('card-expiry-year');

	// Validate the number:
//	if (!Stripe.validateCardNumber(ccNum[0])) {
//		error = true;
//		reportError('The credit card number appears to be invalid.');
//	}

	// Validate the CVC:
//	if (!Stripe.validateCVC(cvcNum[0])) {
//		error = true;
//		reportError('The CVC number appears to be invalid.');
//	}
		
	// Validate the expiration:
//	if (!Stripe.validateExpiry(expMonth[0], expYear[0])) {
//		error = true;
//		reportError('The expiration date appears to be invalid.');
//	}

	if (!error) {
		var chargeAmount = 100 * document.getElementById('amount').value;
		// Get the Stripe token:
		Stripe.createToken({
			number: ccNum,
			cvc: cvcNum,
			exp_month: expMonth,
			exp_year: expYear
		}, chargeAmount, stripeResponseHandler);
	}
	// Prevent the form from submitting:
	return false;
}
