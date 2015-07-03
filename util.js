function submitFormAndGoHome(url, form, errorPlaceholder) {
  $.ajax({
    url: url,
    type: "POST",
    dataType: "json",
    data: $(this).serialize(),

    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        var fieldName = response['field_name'];
        var field = fieldName ? form.find('>input[name="' + fieldName + '"]+span') : null;

        if (field) {
          field.text(errorMessage)
        } else {
          errorPlaceholder.text(errorMessage);
        }
      }
      else {
        window.location.href = '/';
      }
    },

    error: function (response) {
      console.error(response);
    }
  });
}