/* custom ui for postparc */
jQuery(document).ready(function ($) {
    if (!event) {
        var event = window.event;
    }
   // ajout bouton pour ajouter une nouvelle fonction depuis le formulaire pfo
    if ($('.ajax-add-new-function').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-function-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-function').closest('form').after('<div id="add-new-function-div"></div>');

        $('label[for="' + $('.ajax-add-new-function').attr('id') + '"]').append(dom);
        $("#add-new-function-button").on("click", function (event) {
            if (!event) {
                var event = window.event;
            }
            $("#add-new-function-div").load(Routing.generate('ajax_get_personFunction_form'), function () {
                $("#add-new-PersonFunction-Modal").modal();
            });
        });
    }

   // ajout bouton pour ajouter une nouvelle service depuis le formulaire pfo
    if ($('.ajax-add-new-service').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-service-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-service').closest('form').after('<div id="add-new-service-div"></div>');

        $('label[for="' + $('.ajax-add-new-service').attr('id') + '"]').append(dom);
        $("#add-new-service-button").on("click", function (event) {
            $("#add-new-service-div").load(Routing.generate('ajax_get_service_form'), function () {
                $("#add-new-Service-Modal").modal();
            });
        });
    }
   // ajout bouton pour vider le champ city
    if ($('.city-autocomplete').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" alt="' + Translator.trans('actions.clear') + '" title="' + Translator.trans('actions.clear') + '" id="clear-city-autocomplete-field"><i class="fa fa-eraser" aria-hidden="true"></i></a>';
        $('label[for="' + $('.city-autocomplete').attr('id') + '"]').append(dom);
        $("#clear-city-autocomplete-field").on("click", function (event) {
            $('.city-autocomplete').val('');
            $('.city-value').attr("value", '');
        });
    }

   // ajout bouton pour ajouter une nouvelle additionalFunction depuis le formulaire pfo
    if ($('.ajax-add-new-additionalFunction').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-additionalFunction-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-additionalFunction').closest('form').after('<div id="add-new-additionalFunction-div"></div>');

        $('label[for="' + $('.ajax-add-new-additionalFunction').attr('id') + '"]').append(dom);
        $("#add-new-additionalFunction-button").on("click", function (event) {
            $("#add-new-additionalFunction-div").load(Routing.generate('ajax_get_additionalFunction_form'), function () {
                $("#add-new-AdditionalFunction-Modal").modal();
            });
        });
    }

   // ajout bouton pour ajouter une nouvelle profession depuis le formulaire person
    if ($('.ajax-add-new-profession').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-profession-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-profession').closest('form').after('<div id="add-new-profession-div"></div>');

        $('label[for="' + $('.ajax-add-new-profession').attr('id') + '"]').append(dom);
        $("#add-new-profession-button").on("click", function (event) {
            $("#add-new-profession-div").load(Routing.generate('ajax_get_profession_form'), function () {
                $("#add-new-Profession-Modal").modal();
            });
        });
    }

   // ajout bouton pour ajouter un nouveau tag depuis les formulaires
    if ($('.ajax-add-new-tag').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-tag-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-tag').closest('form').after('<div id="add-new-tag-div"></div>');

        $('label[for="' + $('.ajax-add-new-tag').attr('id') + '"]').append(dom);
        $("#add-new-tag-button").on("click", function (event) {
            $("#add-new-tag-div").load(Routing.generate('ajax_get_tag_form'), function () {
                $("#add-new-Tag-Modal").modal();
            });
        });
    }

   // ajout bouton pour ajouter une nouveau type d'évènement depuis le formulaire évènement
    if ($('.ajax-add-new-eventType').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-eventType-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-eventType').closest('form').after('<div id="add-new-eventType-div"></div>');

        $('label[for="' + $('.ajax-add-new-eventType').attr('id') + '"]').append(dom);
        $("#add-new-eventType-button").on("click", function (event) {
            $("#add-new-eventType-div").load(Routing.generate('ajax_get_eventType_form'), function () {
                $("#add-new-eventType-Modal").modal();
            });
        });
    }
   // ajout bouton pour ajouter une nouveau type de mandat depuis le formulaire représentation
    if ($('.ajax-add-new-mandateType').length) {
        var dom = '&nbsp;<a href="javascript:void(0)" id="add-new-mandateType-button"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i></a>';
        $('.ajax-add-new-mandateType').closest('form').after('<div id="add-new-mandateType-div"></div>');

        $('label[for="' + $('.ajax-add-new-mandateType').attr('id') + '"]').append(dom);
        $("#add-new-mandateType-button").on("click", function (event) {
            $("#add-new-mandateType-div").load(Routing.generate('ajax_get_mandateType_form'), function () {
                $("#add-new-mandateType-Modal").modal();
            });
        });
    }

   // mise suppression d'un element via ajax
    $('.updateCoordinateGeoloc').click(function (event) {
        event.preventDefault();


        $.ajax({
            type: 'GET',
            url: Routing.generate('ajax_updateCoordinateGeolocInfos'),
            async: true,
            data: {id: $(this).data("id")},
            success: function (data) {
                message = Translator.trans('flash.updateCoordinateGeolocInfosSuccess');

             // decrement nb span nbResults
                if ($('span.coordinate').length) {
                      $('span.coordinate').html(data);
                }
                if (typeof markers !== 'undefined') {
                     markers.clearLayers();
                     coord = data.split(',');
                     var marker = L.marker([parseFloat(coord[0]), parseFloat(coord[1])]).addTo(markers);
                     map.panTo(new L.LatLng(parseFloat(coord[0]), parseFloat(coord[1])));
                }
                showAlertMessage('success', message);
            },
            failure: function () {
                message = Translator.trans('flash.updateCoordinateGeolocInfoseFailure');
                showAlertMessage('error', message);
            }

        });
    });


});