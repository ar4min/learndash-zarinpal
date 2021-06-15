function toggle_test_mode_child_fields() {
	if (jQuery('#sfwd-stripe_test_mode input[name="learndash_stripe_settings[test_mode]"]').is(':checked')) {

		jQuery('div#sfwd-stripe_secret_key_live').hide();
		jQuery('div#sfwd-stripe_publishable_key_live').hide();

		jQuery('div#sfwd-stripe_secret_key_test').show();
		jQuery('div#sfwd-stripe_publishable_key_test').show();
	} else {
		jQuery('div#sfwd-stripe_secret_key_test').hide();
		jQuery('div#sfwd-stripe_publishable_key_test').hide();

		jQuery('div#sfwd-stripe_secret_key_live').show();
		jQuery('div#sfwd-stripe_publishable_key_live').show();
	}
}

function toggle_integration_type_child_fields() {
	if (jQuery('#sfwd-stripe_integration_type select[name="learndash_stripe_settings[integration_type]"]').val() === 'checkout' ) {
		jQuery('div#sfwd-stripe_webhook_url').show();
		jQuery('div#sfwd-stripe_endpoint_secret').show();
	} else {
		jQuery('div#sfwd-stripe_webhook_url').hide();
		jQuery('div#sfwd-stripe_endpoint_secret').hide();
	}
}

jQuery(document).ready(function($) {
	if (jQuery('#sfwd-stripe_test_mode input[name="learndash_stripe_settings[test_mode]"]').length) {
		toggle_test_mode_child_fields();
		
		jQuery('#sfwd-stripe_test_mode input[name="learndash_stripe_settings[test_mode]"]').change(toggle_test_mode_child_fields);
	}

	if (jQuery('#sfwd-stripe_integration_type select[name="learndash_stripe_settings[integration_type]"]').length) {
		toggle_integration_type_child_fields();
		
		jQuery('#sfwd-stripe_integration_type select[name="learndash_stripe_settings[integration_type]"]').change(toggle_integration_type_child_fields);
	}
});