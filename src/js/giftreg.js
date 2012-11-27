// based on http://alittlecode.com/files/jQuery-Validate-Demo/

function validate_highlight(label) {
	$(label).closest('.control-group').removeClass('success').addClass('error');
}

function validate_success(label) {
	$(label).addClass('valid').closest('.control-group').removeClass('error').addClass('success');
}
