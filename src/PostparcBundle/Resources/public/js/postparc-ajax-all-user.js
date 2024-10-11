/* custom ui for postparc */
jQuery(document).ready(function ($, event) {

    $('.favoriteButton').on("click", function (event) {
        var id = $(this).data('id');
        var type = $(this).data('class');
        var selector = $(this);
        $.ajax({
            url: Routing.generate('ajax_addOrRemoveFavorites'),
            type: "POST",
            async: true, // Mode asynchrone
            dataType: "json",
            data: {
                'id': id,
                'class': type
            },
            complete: function (data) {
                var newClass = data.responseText;
                var icon = selector.find('i').first();
                icon.removeClass('fa-star-o');
                icon.removeClass('fa-star');
                icon.addClass(newClass);
            },

            });
    });

    $(".delete-representation").on("click", function (event) {
        var id = $(this).attr('data-representationId');
        var pfoId = $(this).attr('data-pfo');
        if (id) {
            $.ajax({
                url: Routing.generate('representation_delete', {id: id}),
                type: "DELETE",
                async: true, // Mode asynchrone
                dataType: "json",
                complete: function (data) {
                    console.log('complete');
                    $('#representation-' + id).remove();

                    if (typeof pfoId != "undefined") {
                        $('#success-alert-' + pfoId).show();
                        $('#success-alert-' + pfoId).fadeTo(1500, 500).fadeOut(500, function () {
                            $('#success-alert-' + pfoId).hide();
                        });
                    } else {
                        $('#success-alert').show();
                        $('#success-alert').fadeTo(1500, 500).fadeOut(500, function () {
                            $('#success-alert').hide();
                        });
                    }
                },
                success: function (data) {
                    console.log('success');
                },
                error: function (data) {
                    console.log('error');
                }
            });
        }
        event.preventDefault();
        return false;
    });

    $(".addTabToSelection").on("click", function (event) {
        var type = $(this).data('content-type');

        if (type) {
            $.ajax({
                url: Routing.generate('search_addTabToSelection', {type: type}),
                type: "POST",
                async: true, // Mode asynchrone
                dataType: "json",
                complete: function (data) {
                    console.log('complete');
                },
                success: function (data) {
                    console.log('success ' + data);
                    var nbElementAdded = data;
                    message = Translator.trans('flash.addElementToSelectionSuccess');
                    showAlertMessage('success', message);
                    $("#selection-counter").html(nbElementAdded);
                },
                error: function (data) {
                    console.log('error');
                }
            });
        }
        event.preventDefault();
        return false;
    });

    // ajout bouton pour remettre a zéro un champ file
    if ($("input:file").length) {
        $("input:file:not('.not-btn-file-reset')").each(function (index) {
            var $dom = '<button class="btn-file-reset" type="button" class="btn btn-dafault">' + Translator.trans('actions.resetFile') + ' <i class="fa fa-times" aria-hidden="true"></i></button>';
            $(this).after($dom);
        });
    }

    // js pour remettre a zéro le champ
    $('.btn-file-reset').on('click', function (e) {
        var $el = $(this).prevAll("input[type=file]");
        $el.wrap('<form>').closest('form').get(0).reset();
        $el.unwrap();
    });


});
