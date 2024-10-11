jQuery(document).ready(function ($) {
    $("#organization_name").keyup(function () {
        if ($('#organization_name').val().length < 3) {
            $('#suggestions').hide();
        } else {
            $.post("../checkListOrganization", {search: "" + $('#organization_name').val() + ""}, function (data) {
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
