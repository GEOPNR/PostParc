/* custom ui for postparc */
jQuery(document).ready(function ($) {

    hideAlertMessage();
    $.fn.select2.defaults.set("theme", "bootstrap");
    $(document).on('show.bs.modal', function (e) {

        $('.select2entity').each(function () {
            $(this).css('width', '100%');
            $(this).select2();
        });
    });
   // fixed header
    head_height = $("#header").height();
    $("#header").css('position', 'fixed');
    $("#content").css('margin-top', head_height + 20);

    pwstrengthOptions = {
        common: {
            'usernameField': '#user_username',
            'onScore': function (options, word, totalScoreCalculated) {
                console.log(totalScoreCalculated);
                if (totalScoreCalculated < 15) {
                    $(':input[type="submit"]').prop('disabled', true);
                } else {
                    $(':input[type="submit"]').prop('disabled', false);
                }
                return totalScoreCalculated;
            }
        },
        rules: {},
        ui: {
            'bootstrap3': true
        }
    };
    $('#user_plainPassword_first').pwstrength(pwstrengthOptions);

    $('.js-datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });
    jQuery.datetimepicker.setLocale('fr');
    $('.js-datetimepicker').datetimepicker({
        lang: 'fr',
        format: 'd/m/Y H:i',
        step: 30,
        minTime: '07:00',
        maxTime: '22:00'

    });
   // bootstrap WYSIHTML5 - text editor
    var $summernote = $('.summernote').summernote({
        lang: 'fr-FR',
        height: 300,        
        toolbar: [
         ['cleaner',['cleaner']],
         ['custom',['pageTemplates','blocks']], // Custom Buttons
         ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
         ['font', ['strikethrough', 'superscript', 'subscript']],
         ['fontname', ['fontname']],
         ['fontsize', ['fontsize']],
         ['color', ['color']],
         ['para', ['ul', 'ol', 'paragraph', 'height']],
         ['table', ['table']],
         ['insert', ['link', 'picture', 'video']],
         ['view', ['fullscreen', 'codeview', 'help']]
        ],
        cleaner:{
            action: 'button', // both|button|paste 'button' only cleans via toolbar button, 'paste' only clean when pasting content, both does both options.
            newline: '<br>', // Summernote's default is to use '<p><br></p>'
            notStyle: 'position:absolute;top:0;left:0;right:0', // Position of Notification
            icon: '<i class="note-icon"><i class="fas fa-broom"></i></i>',
            keepHtml: false, // Remove all Html formats
            keepOnlyTags: ['<p>', '<br>', '<ul>', '<li>', '<b>', '<strong>','<i>', '<a>'], // If keepHtml is true, remove all tags except these
            keepClasses: false, // Remove Classes
            badTags: ['style', 'script', 'applet', 'embed', 'noframes', 'noscript', 'html'], // Remove full tags with contents
            badAttributes: ['style', 'start'], // Remove attributes from remaining tags
            limitChars: false, // 0/false|# 0/false disables option
            limitDisplay: 'both', // text|html|both
            limitStop: false // true/false
        },
        pageTemplates:{
            icon: '<i class="note-icon"><i class="fas fa-file-alt"></i></i>',
            templates: '/bundles/postparc/lib/summernote-templates-plugin/page-templates/', // The folder where the templates are stored.
            insertDetails: false, // true|false This toggles whether the below options are automatically filled when inserting the chosen page template.
            dateFormat:    'longDate',
            yourName:      'Your Name',
            yourTitle:     'Your Title',
            yourCompany:   'Your Comapny',
            yourPhone:     'Your Phone',
            yourAddress:   'Your Address',
            yourCity:      'Your City',
            yourState:     'Your State',
            yourCountry:   'Your Country',
            yourPostcode:  'Your Postcode',
            yourEmail:     'your@email.com'
        },
        blocks:{
            icon: '<i class="note-icon"><i class="fas fa-th"></i></i>',
            templates: '/bundles/postparc/lib/summernote-templates-plugin/bootstrap-templates/' // The folder where the Block Templates are stored
        },
        callbacks: {
            onImageUpload: function (files) {
                sendFile($summernote, files[0]);
            }
        }
    });
    
    defaultFontName = $summernote.data('default-font-family');
    if (defaultFontName){
        $summernote.summernote('fontName', defaultFontName);
        $('.note-editor').css('font-family', defaultFontName);
    }
    
    defaultFontSize = $summernote.data('default-font-size');
    if (defaultFontSize){
        $summernote.summernote('fontSize', defaultFontSize);
        $('.note-editor').css('font-size', defaultFontSize);
    }


   

    function sendFile($summernote, file)
    {
        var formData = new FormData();
        formData.append("file", file);
        $.ajax({
            url: Routing.generate('summerUploads'),
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            success: function (data) {
                $summernote.summernote('insertImage', data, function ($image) {
                    $image.attr('src', data);
                });
            }
        });
    }
    $summernote.on("summernote.enter", function(we, e) {
     $(this).summernote("pasteHTML", "<br><br>");
     e.preventDefault();
    });


    $('#resultsPerPage').change(function () {
        var url = $(location).attr('pathname') + $(location).attr('search').replace('page=' + $_GET('page'), 'page=1');
        var item = $('#resultsPerPage').find(":selected").text();
        if (~url.indexOf('per_page')) {
            jQuery(location).attr('href', url.replace($_GET('per_page'), item));
        } else {
            if (~url.indexOf('?')) {
                jQuery(location).attr('href', url + '&per_page=' + item);
            } else {
                jQuery(location).attr('href', url + '?per_page=' + item);
            }
        }
    });
    $('.submitFormOnSelect').change(function () {
        var selecteditemValue = $(this).find(":selected").val();
        if (selecteditemValue.length > 0) {
            var form = $(this).closest("form");
            form.submit();
        }
    });
   // ajout des boutons d'actions des formulaire en haut du formulaire
    $('.btn-group.form-actions.addActionsOnTop').each(function () {
        var domAction = '<div class="form-group">' + $(this).get(0).outerHTML + '</div>';
        $(this).closest('form').prepend(domAction);
    });
    $('.btn-maj-rpp').on('click', function () {
        var value = $('#resultsPerPage input[name=options]:checked').val();
        console.log('value' + value);
        $.ajax({
            url: Routing.generate('update_results_per_page'),
            dataType: 'json',
            method: 'POST',
            data: {
                'value': value
            },
            complete: function (data) {
                console.log('complete');
            },
            success: function (data) {
                console.log('success');
            },
            error: function (data) {
                console.log('error');
            }
        });
    });
   // ajout hack pour afficher onglet particulier
    var url = document.location.toString();
    if (url.match('#') && $('.nav-tabs').length > 0) {
       //console.log(url.split('#')[1]);
        var mainTab = url.split('#')[1];
        $('.nav-tabs a[href="#' + mainTab + '"]').tab('show');
    }
   // tabs
    if (getUrlParameter('activTab')) {
        var activTab = getUrlParameter('activTab');
        $('.nav-tabs a[href="#' + activTab + '"]').tab('show');
    }
   // subtabs
    if (getUrlParameter('activSubTab')) {
        var activSubTab = getUrlParameter('activSubTab');
        $('.nav-pills a[href="#' + activSubTab + '"]').tab('show');
    }

   // get GET params in url
    function getUrlParameter(sParam)
    {
        var sPageURL = window.location.search.substring(1),
              sURLVariables = sPageURL.split('&'),
              sParameterName,
              i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    }
    ;

// scrolled class on body
    $(document).scroll(function () {
        scroll_position = $(document).scrollTop();
        if (scroll_position > 0) {
            $('body').addClass('scrolled');
        } else {
            $('body').removeClass('scrolled');
        }
    });
   //filters toggle

    $(".sf-admin-filters-toggle").click(function (event) {
        event.preventDefault();
        $(".sf-admin-filters").slideToggle('fast');
        $(this).toggleClass('active');
       // refresh eventuel select inputs
       //$(".select2").not('.city-select2').select2();
        $('.city-select2').select2entity();
    });
    $("a[href='#down']").click(function () {
        $("html, body").animate({scrollTop: $(document).height()}, 800);
        return false;
    });
   // au niveau du bloc d'impression d'etiquettes affichage des options d'affichage
    $("#impressionOptionShowHide").click(function () {
        $("#impressionOptionBlock").slideToggle('fast');
    });
   // au niveau page resultat recherche / affichage massquage des critères de recherches
    $("#searchCriteriaShowHide").click(function () {
        $("#searchCriteriaBlock").slideToggle('fast');
    });
   // Enable tooltip
    $('[data-toggle="tooltip"]').tooltip();
    if ($('#postparcTree').length) {
        $("#postparcTree").treetable({expandable: true, initialState: "expanded"});
    }


    if (document.getElementById('copyDiv')) {
 // fonction do copy text in clipboard
        var clipboard = new Clipboard('.btn');
        clipboard.on('success', function (e) {
          //e.clearSelection();

            console.info('Action:', e.action);
            console.info('Text:', e.text);
            console.info('Trigger:', e.trigger);
            $("#copyDivSuccess").slideToggle('slow');
            $("#copyDivSuccess").fadeOut("slow");
        });
        clipboard.on('error', function (e) {
            console.error('Action:', e.action);
            console.error('Trigger:', e.trigger);
            showTooltip(e.trigger, fallbackMessage(e.action));
        });
    }

    $(".select2").select2({
        language: "fr",
        width: '100%'
    });
    if ($('tr.warning').length) {
        $('#createByOtherEntity').show();
    }

    $(".insertToBody").click(function (event) {
        event.preventDefault();

        if ($(".summernote")) {
            htmlToInsert = $(this).val().trim();
            const textArea = $('textarea.summernote').first();
            // Get the current selection and range
            const editableDiv = document.querySelector('.note-editable');
            const sel = window.getSelection();
            const range = sel.getRangeAt(0);
            const startPos = range.startOffset;
            
            // Insert the HTML at the current cursor position:
            range.deleteContents();
            const newElem = document.createElement('span');
            newElem.innerHTML = htmlToInsert;
            range.insertNode(newElem);                      
            
            // Set the cursor position to the end of the inserted HTML:
            range.setStartAfter(newElem);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
            editableDiv.focus();
            
            // Update the editor content and set the cursor position:
            const updatedHtml = editableDiv.innerHTML;            
            const cursorPosition = startPos + htmlToInsert.length;
            //const newRange = document.createRange();
            //newRange.setStart(editableDiv.childNodes[0], cursorPosition);
            //newRange.setEnd(editableDiv.childNodes[0], cursorPosition);
            //sel.removeAllRanges();
            //sel.addRange(newRange);
            editableDiv.focus();            
            textArea.val(updatedHtml);
            
        }
    });
    $("#loadDocumentTemplate").click(function (event) {
 // recuperation modele selectionné
        var documentTemplateId = $("#documentTemplate").val();
        $.ajax({
            type: 'GET',
            url: Routing.generate('ajax_getDocumentTemplate'),
            data: {id: documentTemplateId},
            success: function (response) {
                var response = JSON.parse(response);
    //            if ($(".wysihtml5-sandbox")[0]) {
    //               $('.wysihtml5-sandbox').contents().find('body').html(response.body);
    //            }
             // pour tinymce_init
    //            if ($(".mce-tinymce")[0]) {
    //               tinymce.activeEditor.execCommand('mceInsertContent', false, response.body);
    //            }
             // pour ckeditor
    //            if (typeof CKEDITOR != 'undefined') {
    //               CKEDITOR.instances['mail_massif_module_body'].setData(response.body);
    //               //CKEDITOR.instances['mail_massif_module_body'].insertHtml(response.body);
    //            }
                if ($(".summernote")) {
                      $('#mail_massif_module_body').val(response.body);
                      $('#mail_massif_module_body').summernote('pasteHTML', response.body);
                      $('#mail_massif_module_body').summernote('focus');
                }

                $('#subject').val(response.subject);
                $('#mail_massif_module_subject').val(response.subject);
            }

        });
    });
    $("#loadDocumentTemplateForEventAlert").click(function (event) {
 // recuperation modele selectionné
        var documentTemplateId = $("#documentTemplate").val();
        $.ajax({
            type: 'GET',
            url: Routing.generate('ajax_getDocumentTemplate'),
            data: {id: documentTemplateId},
            success: function (response) {
                var response = JSON.parse(response);
    //            if ($(".wysihtml5-sandbox")[0]) {
    //               $('.wysihtml5-sandbox').contents().find('body').html(response.body);
    //            }
             // pour tinymce_init
    //            if ($(".mce-tinymce")[0]) {
    //               tinymce.activeEditor.execCommand('mceInsertContent', false, response.body);
    //            }
             // pour ckeditor
    //            if (typeof CKEDITOR != 'undefined') {
    //               CKEDITOR.instances['postparcbundle_eventalert_message'].setData(response.body);
    //               //CKEDITOR.instances['postparcbundle_eventalert_message'].insertHtml(response.body);
    //            }
                if ($(".summernote")) {
                      $('#postparcbundle_eventalert_message').val(response.body);
                      $('#postparcbundle_eventalert_message').summernote('pasteHTML', response.body);
                      $('#postparcbundle_eventalert_message').summernote('focus');
                }

                $('#postparcbundle_eventalert_name').val(response.subject);
            }

        });
    });
   // mise en place stockage dans la selection d'un element via ajax
    $('.add-to-selection-button').click(function (event) {
        event.preventDefault();
        $.ajax({
            type: 'GET',
            url: Routing.generate('ajax_addToSelection'),
            data: {id: $(this).data("id"), type: $(this).data("type")},
            success: function (response) {
                if (response !== 'alreadyExist') {
                      message = Translator.trans('flash.addElementToSelectionSuccess');
                      showAlertMessage('success', message);
                      $("#selection-counter").html(response);
                } else {
                    message = Translator.trans('flash.alreadyPresent');
                    showAlertMessage('warning', message);
                }
            }

        });
    });
   // mise suppression d'un element via ajax
    $('.delete-ajax-button').click(function (event) {
        event.preventDefault();
        var parent = $(this).closest("tr");
        if ($(this).data("confirm")) {
            message = $(this).data("confirm");
        } else {
            message = Translator.trans('actions.confirmDelete');
        }
        if (confirm(message)) {
            $.ajax({
                type: 'GET',
                url: Routing.generate('ajax_removeElement'),
                data: {id: $(this).data("id"), type: $(this).data("type")},
                success: function () {
                    message = Translator.trans('flash.deleteSuccess');
                    parent.hide('slow', function () {
                        parent.remove();
                    });
                   // decrement nb span nbResults
                    if ($('span.nbResults').length) {
                          nbresult = $('span.nbResults').html();
                          nbresult--;
                          $('span.nbResults').html(nbresult);
                    }
                    showAlertMessage('success', message);
                },
                failure: function () {
                    message = Translator.trans('flash.deleteFailure');
                    showAlertMessage('error', message);
                }

            });
        }
    });
   // modal message confirmAction
    $('a[data-confirm]').not(".delete-ajax-button").click(function (ev) {
        var href = $(this).attr('href');
        if (!$('#dataConfirmModal').length) {
            $('body').append('<div id="dataConfirmModal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">' + Translator.trans('confirm') + '</h3></div><div class="modal-body"></div><div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true">' + Translator.trans('no') + '</button><a class="btn btn-danger" id="dataConfirmOK">' + Translator.trans('yes') + '</a></div></div></div></div>');
        }
        $('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
        $('#dataConfirmOK').attr('href', href);
        $('#dataConfirmModal').modal({show: true});
        return false;
    });
    $('#batchFormSubmitButton').on('click', function (event) {
        if ($('select').filter(function () {
            return this.value == 'batchDelete';
        }).length > 0) {
            event.preventDefault();
            if (confirm(Translator.trans('actions.confirmDeleteMultiple'))) {
                $('form').submit();
            }
        }
    });
   // js for phone call
    $(".phoneNumber").click(function () {
        var PhoneNumber = $(this).text();
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            window.location.href = "tel:" + PhoneNumber;
        } else {
            window.location.href = "callto:" + PhoneNumber;
        }
    });
   // js for open modal eventAlert
    if ($("#modalAlertMessage").length) {
        $('#modalAlertMessage').modal('show');
    }

// js for Representation form
    if ($('#postparcbundle_representation_mandatDurationIsUnknown').length) {
        updateRepresentationForm();
    }
    $('#postparcbundle_representation_mandatDurationIsUnknown').on('click', function () {
        updateRepresentationForm();
    });
   //Fix bug email massif : quand le formulaire est validé on perd la valeur du ckeditor
   //$('#submitMailMassifModule').click(function() {
   //récuère la valeur du ckeditor et on la rajoute dans le input
    $("input#mail_massif_module_body").val(function (index, value) {
 //return CKEDITOR.instances['mail_massif_module_body'].getData();
    });
   //});

}); // end document.Ready

/**
 * function to update begin_date and mandatDuration if mandatDurationIsUnknown is checked
 * @returns {undefined}
 */
function updateRepresentationForm()
{
    if ($('#postparcbundle_representation_mandatDurationIsUnknown').is(":checked")) {
       // postparcbundle_representation_beginDate
        $("#postparcbundle_representation_beginDate").removeAttr('required');
        $("label[for='postparcbundle_representation_beginDate']").removeClass('required');
       // postparcbundle_representation_mandatDuration
        $("#postparcbundle_representation_mandatDuration").removeAttr('required');
        $("label[for='postparcbundle_representation_mandatDuration']").removeClass('required');
        $('.representation-alert').closest('.form-group').css('display', 'none');
    } else {
        $("#postparcbundle_representation_beginDate").attr('required', 'required');
        $("label[for='postparcbundle_representation_beginDate']").addClass('required');
        $("#postparcbundle_representation_mandatDuration").attr('required', 'required');
        $("label[for='postparcbundle_representation_mandatDuration']").addClass('required');
        $('.representation-alert').closest('.form-group').css('display', 'block');
    }
}

function $_GET(param)
{
    var vars = {};
    window.location.href.replace(location.hash, '').replace(
        /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
        function (m, key, value) {
        // callback
            vars[key] = value !== undefined ? value : '';
        }
    );
    if (param) {
        return vars[param] ? vars[param] : null;
    }
    return vars;
}

function sortBranch(id, e)
{
    var node = $("#postparcTree").treetable('node', id);
    $("#postparcTree").treetable('sortBranch', node);
}

function showAlertMessage(type, message)
{
    if ($('#alert-box').length) {
        dom = '<div class="alert alert-' + type + ' alert-dismissible alert-auto-hidden" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
        $('#alert-box').append(dom);
    } else {
        dom = '<div id="alert-box"><div class="alert alert-' + type + ' alert-dismissible alert-auto-hidden" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
        $('#header').append(dom);
    }
    hideAlertMessage();
}
// masquage auto des message d'alerte
function hideAlertMessage()
{

    $(".alert-auto-hidden").delay(5000).fadeOut(function () {
        $(".alert-auto-hidden").alert('close');
        $(this).remove();
    });
}

/**
 *
 * @param {type} elt
 * @returns {undefined}
 */
function showHideCoordinateBloc(elt)
{
    $(elt).slideToggle();
    var eyeElmt = $('#show-hide-coordinate-bloc');
    if (eyeElmt.hasClass('fa-eye')) {
        eyeElmt.removeClass('fa-eye');
        eyeElmt.addClass('fa-eye-slash');
        $('.city-select2').select2entity();
    } else {
        eyeElmt.removeClass('fa-eye-slash');
        eyeElmt.addClass('fa-eye');
    }
}

function checkAll()
{
    var boxes = document.getElementsByTagName('input');
    for (var index = 0; index < boxes.length; index++) {
        box = boxes[index];
        if (box.type == 'checkbox' && box.className == 'sf_admin_batch_checkbox') {
            box.checked = document.getElementById('sf_admin_list_batch_checkbox').checked
        }
    }
    return true;
}

function checkAllInActiveTab(caller)
{

    if ($(caller).is(':checked')) {
        $(caller).prop('checked', false);
    } else {
        $(caller).prop('checked', true);
    }
   // parent tab-content
    $tabContent = $(caller).closest('.tab-pane');
    $tabContent.find(':checkbox').each(function () {
        if ($(this).is(':checked')) {
            $(this).prop('checked', false);
        } else {
            $(this).prop('checked', true);
        }
    });
    return true;
}

$.fn.insertAtCaretCKeditor = function (myValue) {
    myValue = myValue.trim();
    if (typeof CKEDITOR.instances['mail_massif_module_body'] != 'undefined') {
        CKEDITOR.instances['mail_massif_module_body'].insertText(myValue);
    }
    if (typeof CKEDITOR.instances['document_template_body'] != 'undefined') {
        CKEDITOR.instances['document_template_body'].insertText(myValue);
    }
};
$.fn.extend({
    insertAtCaret: function (myValue) {
        var obj;
        if (typeof this[0] != 'undefined' && typeof this[0].name != 'undefined') {
            obj = this[0];
        } else {
            obj = this;
        }
        if ($.browser.msie) {
            obj.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
            obj.focus();
        } else if ($.browser.mozilla || $.browser.webkit) {
            var startPos = obj.selectionStart;
            var endPos = obj.selectionEnd;
            var scrollTop = obj.scrollTop;
            obj.value = obj.value.substring(0, startPos) + myValue + obj.value.substring(endPos, obj.value.length);
            obj.focus();
            obj.selectionStart = startPos + myValue.length;
            obj.selectionEnd = startPos + myValue.length;
            obj.scrollTop = scrollTop;
        } else {
            obj.value += myValue;
            obj.focus();
        }
        return obj.value;
    }
})
        ;

