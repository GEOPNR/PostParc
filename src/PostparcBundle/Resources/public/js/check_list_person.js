jQuery(document).ready(function ($) {
    $("#person_name").keyup(function () {
        if ($('#person_name').val().length < 3) {
            $('#suggestions').hide();
        } else {
            $.post("../checkListPerson", {search: "" + $('#person_name').val() + ""}, function (data) {
                if (data.length > 0) {
                    $('#suggestions').show();
                    $('#autoSuggestionsList').html(data);
                } else {
                    $('#suggestions').hide();
                    $('#autoSuggestionsList').html('');
                }

            });
        }
    });
});
