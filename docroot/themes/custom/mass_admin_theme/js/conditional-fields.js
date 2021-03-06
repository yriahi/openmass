/**
 * @file
 * Override field configuration based on given conditions in content types.
 *
 * Supplement Conditional Fields module for changes the module doesn't cover.
 */
(function ($, Drupal) {

  'use strict';

  /**
   * Override field configuration in News.
   */
  (function overrideNews() {
    // This grabs the text of the field to depend on.
    var selectedText = $('#edit-field-news-type option:checked').text();

    if (selectedText === 'News' || selectedText === 'Press Statement') {
      $('#edit-field-news-body-0--description').text('Use this for an overview. You can add additional content using Sections below.');
    }
    else if (selectedText === 'Press Release') {
      $('#edit-field-news-body-0--description').text('Please enter the full press release text here. A “###” will be added automatically after this area.');
    }
    else if (selectedText === 'Speech') {
      $('#edit-field-news-body-0--description').text('');
    }

    $('.layout-node-form').find('label').each(function () {

      /**
     * Override the field_news_body label based on selected option of
     * field_news_type in News.
     */
      if ($(this).attr('for') === 'edit-field-news-body-0-value') {
        if (selectedText === 'News' || selectedText === 'Press Statement') {
          $(this).text('Overview');
        }
        else {
          $(this).text('Content');
        }
      }

      /**
     * Add an asterisk to indicate 'required' for field_news_location label in
     * News.
     */
      if ($(this).attr('for') === 'edit-field-news-location-0-value') {
        if (selectedText !== 'Press Release') {
          $(this).removeAttr('class');
        }
        else {
          $(this).addClass('js-form-required form-required');
        }
      }
    });

    /**
   * Add an asterisk to indicate 'required' to tab and Media contacts field in
   * News.
   */
    $('li.horizontal-tab-button').each(function () {
      // Limit this to only the News node; this fixes the asterisk
      // on the Contact tab for the How-to Page.
      if (!document.getElementById('node-news-form')) { return; }
      var horizontalTabLabel = $(this).find('strong');
      var tabLabel = horizontalTabLabel.text();
      if (tabLabel === 'Contacts') {
        if (selectedText === 'Press Release' || selectedText === 'Press Statement') {
          horizontalTabLabel.addClass('form-required');
          $('#edit-field-news-media-contac > strong').addClass('form-required');
        }
        else {
          horizontalTabLabel.removeAttr('class');
          $('#edit-field-news-media-contac > strong').removeAttr('class');
        }
      }
      if (tabLabel === 'Main Content') {
        if (selectedText === 'Press Release') {
          horizontalTabLabel.addClass('form-required');
          $('#edit-field-news-media-contac > strong').addClass('form-required');
        }
        else {
          horizontalTabLabel.removeAttr('class');
          $('#edit-field-news-media-contac > strong').removeAttr('class');
        }
      }
    });

    setTimeout(overrideNews, 1);
  })();

  /**
   * Alert Link Type.
   * Hide or show options based on the value of the link type field.
   *
   * @param {string} val - link type value.
   *
   * Field values are defined in the link type field on the emergency_alert
   * paragraph.
   */
  function processLinkType(val) {
    switch (val) {
      case '0':
        $('.field--name-field-emergency-alert-link').hide();
        $('.field--name-field-emergency-alert-content').show();
        break;
      case '1':
        $('.field--name-field-emergency-alert-link').show();
        $('.field--name-field-emergency-alert-content').hide();
        break;
      case '2':
        $('.field--name-field-emergency-alert-link').hide();
        $('.field--name-field-emergency-alert-content').hide();
        break;
      default:
    }
  }

  var $linkTypeFields = $('#edit-field-alert-0-subform-field-emergency-alert-link-type input');
  var $linkTypeChecked = $('#edit-field-alert-0-subform-field-emergency-alert-link-type input[checked=checked]');
  var linkType = $linkTypeChecked.val();
  processLinkType(linkType);
  $linkTypeFields.click(function () {
    processLinkType($(this).val());
  });

})(jQuery);
