var $collectionHolder;

// setup an "add a tag" link
var $addTagLink = $('<a class="btn btn-default add_attachment_link" href="#"><i class="fa fa-plus" aria-hidden="true"></i> ' + Translator.trans('Attachment.actions.addNewAttachment') + '</a>');
var $newLinkLi = $('<li class="new"></li>').append($addTagLink);

jQuery(document).ready(function () {
    // Get the ul that holds the collection of tags
    $collectionHolder = $('ul.attachments');

    // add a delete link to all of the existing tag form li elements
    $collectionHolder.find('li.new').each(function () {
        addTagFormDeleteLink($(this));
    });

    // add the "add a tag" anchor and li to the tags ul
    $collectionHolder.append($newLinkLi);

    // count the current form inputs we have (e.g. 2), use that as the new
    // index when inserting a new item (e.g. 2)
    $collectionHolder.data('index', $collectionHolder.find(':input').length);

    $addTagLink.on('click', function (e) {
        // prevent the link from creating a "#" on the URL
        e.preventDefault();

        // add a new tag form (see next code block)
        addAttachmentForm($collectionHolder, $newLinkLi);
    });
});

function addAttachmentForm($collectionHolder, $newLinkLi)
{
    // Get the data-prototype explained earlier
    var prototype = $collectionHolder.data('prototype');

    // get the new index
    var index = $collectionHolder.data('index');

    // Replace '__name__' in the prototype's HTML to
    // instead be a number based on how many items we have
    var newForm = prototype.replace(/__name__/g, index);

    // increase the index with one for the next item
    $collectionHolder.data('index', index + 1);

    // Display the form in the page in an li, before the "Add a tag" link li
    var $newFormLi = $('<li></li>').append(newForm.replace('hidden','block'));
    $newLinkLi.before($newFormLi);

    // add a delete link to the new form
    addTagFormDeleteLink($newFormLi);
}

function addTagFormDeleteLink($tagFormLi)
{
    var $removeFormA = $('<a href="#"><i class="fa-solid fa-trash-alt" aria-hidden="true"></i> ' + Translator.trans('Attachment.actions.deleteThisBlock') + '</a>');
    $tagFormLi.append($removeFormA);

    $removeFormA.on('click', function (e) {
        // prevent the link from creating a "#" on the URL
        e.preventDefault();

        // remove the li for the tag form
        $tagFormLi.remove();
    });
}
