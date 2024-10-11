/* custom ui for postparc */
jQuery(document).ready(function ($) {
    if (!event) {
        var event = window.event;
    }
  /* person autocomplete*/
    $('.person-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_person'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* person autocomplete*/
    $('.pfo-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_pfo'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* personFunction autocomplete*/
    $('.function-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_function'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* additionalFunction autocomplete */
    $('.additionalFunction-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_additionalFunction'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2,
    });

  /* service autocomplete */
    $('.service-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_service'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* mandateType autocomplete*/
    $('.mandateType-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_mandateType'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

    $('.city-autocomplete').autocomplete({
        delay: 500,
        minLength: 2,
        source: function (request, response) {
            $.ajax({
                url: Routing.generate('autocomplete_city'),
                dataType: 'json',
                data: {
                    q: request.term,
                },
                success: function (data) {
                    if (!data.length) {
                        response([{label: Translator.trans('noResult'), val: -1}]);
                    } else {
                        var array = data.error ? [] : $.map(data, function (m) {
                            return m;
                        });
                        response(array);
                    }
                }
            });
        },



        search: function (request) {
            $('#ui-spinner').addClass('fa-circle-o-notch fa-spin');
        },
        select: function (event, ui) {
            if (ui.item.value === -1) {
                $(this).val('');
                return false;
            }
            $('.city-value').val(ui.item.id);
            return ui.item.id;
        },
        open: function (event, ui) {
            $('#ui-spinner').removeClass('fa-circle-o-notch fa-spin');
            $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
        },
        close: function () {
            $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
        }
    }, initSelection());

    function initSelection()
    {
        if ($id = $(".city-value").val()) {
            $.get(Routing.generate('get_city'), {id: $id}, function (data) {
                if (data) {
                    $('.city-autocomplete').val(data.label);
                }
            }, 'json');
        }
    }
    ;

  /* city autocomplete */
    $('.city-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_city'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: Translator.trans('AdvancedSearch.data-placeholder.city')
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* department autocomplete */
    $('.department-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_department'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: Translator.trans('AdvancedSearch.data-placeholder.department')
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* city autocomplete */
    $('.city-select2-autocomplete-all').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_city_all'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: Translator.trans('AdvancedSearch.data-placeholder.city')
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* organization autocomplete */
    $('.organization-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_organization'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: Translator.trans('AdvancedSearch.data-placeholder.organization')
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* groups autocomplete */
    $('.group-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_group'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: Translator.trans('AdvancedSearch.data-placeholder.group')
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 2
    });

  /* profession autocomplete */
    $('.profession-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_profession'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 1
    });

  /* eventType autocomplete */
    $('.eventType-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_eventType'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 1
    });

  /* territory autocomplete */
    $('.territory-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_territory'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 0
    });

  /* territoryType autocomplete */
    $('.territoryType-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_territoryType'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 0,
    });

  /* event autocomplete */
    $('.event-select2-autocomplete').select2({
        width: '100%',
        ajax: {
            url: Routing.generate('autocomplete_event'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        },
        placeholder: {
            id: "",
            placeholder: "  "
        },
        allowClear: true,
        language: "fr",
        dropdownAutoWidth: true,
        minimumInputLength: 0
    });

  //event.preventDefault();
});
