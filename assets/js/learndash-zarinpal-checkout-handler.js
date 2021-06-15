jQuery(document).ready(function($) {
	
	var Stripe_Handler = {

		init: function( form_ref ) {
			var handler = StripeCheckout.configure({
				key         : LD_Stripe_Handler.publishable_key,
				amount      : parseInt( $( 'input[name="stripe_price"]', form_ref ).val() ),
				currency    : $( 'input[name="stripe_currency"]', form_ref ).val(),
				description : $( 'input[name="stripe_name"]', form_ref ).val(),
				email       : $( 'input[name="stripe_email"]', form_ref ).val(),
				locale      : 'auto',
				name        : LD_Stripe_Handler.name,
				token: function(token) {
					// Use the token to create the charge with a server-side script.
					// You can access the token ID with `token.id`		
					var stripe_token_id = $( '<input type="hidden" name="stripe_token_id" />', form_ref ).val( token.id );
					var stripe_token_email = $( '<input type="hidden" name="stripe_token_email" />', form_ref ).val( token.email );
					$( form_ref ).append( stripe_token_id );
					$( form_ref ).append( stripe_token_email );
					$( form_ref ).submit();
				}
			});

			$('input.learndash-stripe-checkout-button', form_ref).on('click', function(e) {
				// Open Checkout with further options
				handler.open({

				});
				e.preventDefault();
			});

			// Close Checkout on page navigation
			$(window).on('popstate', function() {
				handler.close();
			});
		}
	};

	$('.learndash_stripe_button form.learndash-stripe-checkout input.learndash-stripe-checkout-button').each(function() {
		var parent_form = $(this).parent('form.learndash-stripe-checkout');
		Stripe_Handler.init( parent_form );
	});
		
});