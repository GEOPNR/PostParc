$(document).ready(function () {
   //changement de couleur de la bordure basse de l'entête
   //au survol de certains éléments du menu
   //dans le cas du panier
    $("#panier").hover(function () {
        $("#page").addClass("panier");
    }, function () {
        $("#page").removeClass("panier");
    });

   //dans le cas du menu d'admin
    $("ul#menu_admin li").hover(function () {
        $("#page").addClass("admin");
    }, function () {
        $("#page").removeClass("admin");
    });


    ShowHideFormElementsFromCheckboxOrSelect();
    $(document).on("click", '.hiddenPilotField', function () {
        ShowHideFormElementsFromCheckboxOrSelect();
    });


   // mise en place toggle pour affichage des champs coordinate
    $('div[id$=_coordinate]').each(function () {
        $(this).addClass('well');

        if (!$(this).hasClass('no-toggle')) {
            var dom = '&nbsp;<a href="javascript:void(0);"  class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="top" data-animation="true" title="' + Translator.trans('Coordinate.actions.show-hide-coordinate-bloc') + '" onclick="showHideCoordinateBloc(' + $(this).attr('id') + ');"><i id="show-hide-coordinate-bloc" class="fa fa-eye" aria-hidden="true"></i></a>';
            $(this).before(dom);
           // hide bloc on load
            $(this).hide();
           // load bootstrap tooltip
            $(function () {
                $('[data-toggle="tooltip"]').tooltip();
            });
        }

    });

    $("#unCheckAll").click(function () {
        if ($(this).hasClass('checkAll')) {
            $(".checkbox").each(function () {
                $(this).prop("checked", true);
            });
            $(this).removeClass('checkAll');
            $(this).html(Translator.trans('actions.unCheckAll'));
        } else {
            $(".checkbox").each(function () {
                $(this).prop("checked", false);
            });
            $(this).addClass('checkAll');
            $(this).html(Translator.trans('actions.checkAll'));
        }

    });


    $(".showHideClassElements").click(function () {

        var classname = $(this).data("classname");
        var eyeElmt = $(this).find('.fa');
        if (eyeElmt.hasClass('fa-eye')) {
            eyeElmt.removeClass('fa-eye');
            eyeElmt.addClass('fa-eye-slash');
            $('.city-select2').select2entity();
        } else {
            eyeElmt.removeClass('fa-eye-slash');
            eyeElmt.addClass('fa-eye');
        }
        $('.' + classname).toggle();
    });


    $('.checkAllInLine').click(function (e) {
        var tr = $(e.target).closest('tr');
        $('td input:checkbox', tr).prop('checked', this.checked);
    });

    $('.checkAllInColumn').on('click', function () {
        var colNum = $(this).data('column');
        console.log(colNum);
        $('input[data-column=' + colNum + ']').prop('checked', this.checked);
    });




   // init Infinite Scroll
    if ($('a[rel="next"]').length) {
        $('.scrollable_list').infiniteScroll({
            path: '.pagination a[rel="next"]',
            append: '.sf_admin_list table',
            status: '.scroller-status',
            hideNav: '.navigation',
        });
    }

});

function ShowHideFormElementsFromCheckboxOrSelect()
{
   //console.log('dddd');
    $('.hiddenPilotField').each(function (index) {
        var hiddedClass = $(this).data('hiddedclass');
        var showedClass = $(this).data('showedclass');
        if ($(this).is("select")) {
            if ($(this).val() > 1) {
                $('.' + hiddedClass).parent().show();
                $('.' + showedClass).parent().hide();
            } else {
                $('.' + hiddedClass).parent().hide();
                $('.' + showedClass).parent().show();
            }
        } else {
            if ($(this).is(":checked")) {
                $('.' + hiddedClass).parent().show();
                $('.' + showedClass).parent().hide();
            } else {
                $('.' + hiddedClass).parent().hide();
                $('.' + hiddedClass + ' :input').prop('checked', false);
                $('.' + showedClass).parent().show();
            }
        }
    });
}
