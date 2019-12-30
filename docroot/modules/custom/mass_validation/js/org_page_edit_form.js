/**
 * @file
 * Adds clientside functionality and validation to the Org Page Node Edit Form.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.massValidationBoardMember = {
    attach: function (context, settings) {
      // If board member position is checked as vacant, hide person reference field.
      $('.field--name-field-position-is-vacant', context).each(function () {
        $('input', this).on('change', function () {
          var input = this;
          var $fieldWrapper = $(input).closest('.field--name-field-position-is-vacant');
          if (input.checked) {
            $fieldWrapper.siblings('.field--name-field-person').addClass('js-hide');
          }
          else {
            $fieldWrapper.siblings('.field--name-field-person').removeClass('js-hide');
          }
        });
        $('input', this).trigger('change');
      });

      // Hide the heading and description fields if the toggle is checked.
      $('.field--name-field-add-heading-description', context).each(function () {
        var toggle = this;
        $('input', this).on('change', function () {
          var input = this;
          if (input.checked) {
            $(toggle).siblings('.field--name-field-heading, .field--name-field-description').removeClass('js-hide');
          }
          else {
            $(toggle).siblings('.field--name-field-heading, .field--name-field-description').addClass('js-hide');
          }
        });
        $('input', this).trigger('change');
      });
    }
  };

  Drupal.behaviors.massValidationOrgPageNodeEditForm = {
    $conditionalTabs: {},
    $boardsTab: {},
    $electedRequired: {},
    $generalRequired: {},
    $generalRequiredTabs: {},
    $conditionallyRequiredFields: {},
    $constituentOptionsRequiredFields: {},

    attach: function (context, settings) {
      var self = this;

      this.initVars(context);
      // Update the form based on the value of the Subtype field.
      self.updateElements(context);

      $('#edit-field-subtype, #edit-field-constituent-communication', context).on('change', function () {
        self.updateElements(context);
      });
    },

    initVars: function (context) {
      var $tabs = $('.horizontal-tab-button', context);
      this.$conditionalTabs = $tabs
        .find('a[href$="about-details-tab"]')
        .closest('li');

      this.$boardsTab = $tabs
        .find('a[href$="boards-tab"]')
        .closest('li');

      // Allow a simple array of machine field names to be used to calculate the selector
      // for conditionally required fields.
      var generalRequiredFields = [
        'field_bg_wide'
      ].map(function (field) {
        return '.field--name-' + field.replace(/_/g, '-').replace(/--/g, '__') + ' label';
      }).join(', ');

      var electedRequiredFields = [
        'field_banner_image',
        'field_person_bio'
      ].map(function (field) {
        return '.field--name-' + field.replace(/_/g, '-').replace(/--/g, '__') + ' label';
      }).join(', ');

      var conditionallyRequiredFields = [
        'field_verification',
        'field_approver',
        'field_response_expectations',
        'field_feedback_com_link'
      ].map(function (field) {
        return '.field--name-' + field.replace(/_/g, '-').replace(/--/g, '__') + ' label';
      }).join(', ');

      var constituentOptionsRequiredFields = [
        'field_org_sentence_phrasing'
      ].map(function (field) {
        return '.field--name-' + field.replace(/_/g, '-').replace(/--/g, '__') + ' label';
      }).join(', ');

      this.$generalRequired = $(generalRequiredFields, context);
      this.$electedRequired = $(electedRequiredFields, context);
      this.$conditionallyRequiredFields = $(conditionallyRequiredFields, context);
      this.$constituentOptionsRequiredFields = $(constituentOptionsRequiredFields, context);
      this.$generalRequiredTabs = $tabs.find('a[href$="edit-group-actions"]').find('strong');
    },

    updateElements: function (context) {
      var subtype = $('#edit-field-subtype option:selected', context).val();
      var constituentOption = $('#edit-field-constituent-communication option:selected', context).val();

      this.$conditionallyRequiredFields.addClass('form-required');
      if (constituentOption === 'none') {
        this.$constituentOptionsRequiredFields.removeClass('form-required');
      }
      else {
        this.$constituentOptionsRequiredFields.addClass('form-required');
      }

      if (subtype === 'General Organization') {
        this.$conditionalTabs.addClass('js-hide');
        this.$boardsTab.addClass('js-hide');
        this.$generalRequired.addClass('form-required');
        this.$electedRequired.removeClass('form-required');
        this.$generalRequiredTabs.addClass('form-required');
      }
      else if (subtype === 'Boards') {
        this.$conditionalTabs.addClass('js-hide');
        this.$boardsTab.removeClass('js-hide');
      }
      else {
        this.$conditionalTabs.removeClass('js-hide');
        this.$boardsTab.addClass('js-hide');
        this.$generalRequired.removeClass('form-required');
        this.$electedRequired.addClass('form-required');
        this.$generalRequiredTabs.removeClass('form-required');
      }
    }
  };

})(jQuery, Drupal);
