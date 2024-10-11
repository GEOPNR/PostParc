jQuery(document).ready(function ($) {

    // search switch
    $('.search-switch').click(function (event) {
        $('#search').toggleClass('advanced');
        if ($('#filterAdvancedSearch').val() == 0) {
            $('#filterAdvancedSearch').val(1);
        } else {
            $('#filterAdvancedSearch').val(0);
        }
        event.preventDefault();
        return false;
    });

    // select 2 integration
    // champ fonction
    $("#filterFunction").select2({
        ajax: {
            url: Routing.generate('autocomplete_function'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2,

    });

    // champ service
    $("#filterService").select2({
        ajax: {
            url: Routing.generate('autocomplete_service'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ Organisme
    $("#filterOrganization").select2({
        ajax: {
            url: Routing.generate('autocomplete_organization'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ OrganismeType
    $("#filterOrganizationType").select2({
        ajax: {
            url: Routing.generate('autocomplete_organizationType'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ Abreviation
    $("#filterAbbreviation").select2({
        ajax: {
            url: Routing.generate('autocomplete_abbreviation'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ Territoire
    $("#filterTerritory").select2({
        ajax: {
            url: Routing.generate('autocomplete_territory'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2,

    });

    // champ Commune
    $("#filterCity").select2({
        ajax: {
            url: Routing.generate('autocomplete_city'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ Departement
    $("#filterDepartment").select2({
        ajax: {
            url: Routing.generate('autocomplete_department'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ Groupe
    $("#filterGroup").select2({
        ajax: {
            url: Routing.generate('autocomplete_group'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ tag
    $("#filterTag").select2({
        ajax: {
            url: Routing.generate('autocomplete_tag'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ profession
    $("#filterProfession").select2({
        ajax: {
            url: Routing.generate('autocomplete_profession'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });

    // champ mandateType
    $("#filterMandateType").select2({
        ajax: {
            url: Routing.generate('autocomplete_mandateType'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2

    });
    
    // select 2 integration
    // champ createdBy
    $("#filterCreatedBy").select2({
        ajax: {
            url: Routing.generate('autocomplete_user'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2,

    });
    
    // select 2 integration
    // champ createdByEntities
    $("#filterCreatedByEntities").select2({
        ajax: {
            url: Routing.generate('autocomplete_entity'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
              // parse the results into the format expected by Select2
              // since we are using custom formatting functions we do not need to
              // alter the remote JSON data, except to indicate that infinite
              // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup; }, // let our custom formatter work
        minimumInputLength: 2,

    });


    // surcharge element style width des classes select2-input select2-default
    $(".select2-container").attr('style','width: 400px;');
    $(".select2-search__field").attr('style','width: 400px;');

    // bouton effacer les critères de recherche
     $('#search-reset').click(function (event) {
         // select2 fields
        $('#filterFunction').select2('val', null);
        $('#filterService').select2('val', null);
        $('#filterOrganizationType').select2('val', null);
        $('#filterOrganization').select2('val', null);
        $('#filterTerritory').select2('val', null);
        $('#filterCity').select2('val', null);
        $('#filterGroup').select2('val', null);
        ('#filterCreatedBy').select2('val', null);
        // text fields
        $('#filterObservation').val('');
        $('#filterFullText').val('');
        // checkbox fields
        $('#filterFunction_exclusion').prop('checked', false);
        $('#filterService_exclusion').prop('checked', false);
        $('#filterorganizationType_exclusion').prop('checked', false);
        $('#filterOrganization_exclusion').prop('checked', false);
        $('#filterTerritory_exclusion').prop('checked', false);
        $('#filterCity_exclusion').prop('checked', false);
        $('#filterGroup_exclusion').prop('checked', false);
        $('#filterGroup_sub').prop('checked', false);
        $('#filterTerritory_sub').prop('checked', false);

         event.preventDefault();
         return false;
     });
});
