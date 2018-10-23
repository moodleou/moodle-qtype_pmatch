// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This class provides functionality for the testquestion response creator.
 *
 * @module    qtype_pmatch
 * @class     creator
 * @package   question
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/ajax', 'core/templates', 'core/key_codes'], function($, Str, Ajax, Templates, KeyCodes) {
    /**
     * @alias qtype_pmatch/creator
     */
    var t = {
        /**
         * The id of row will append to Table Response.
         */
        newRowId: '',
        /**
         * Index of last one in the Table Response.
         */
        idxLastRow: 0,
        /**
         * The Id of editing question.
         */
        questionId: '',
        /**
         * Initialise the creator.
         */
        init: function() {
            t.questionId = $('#attemptsform input[name="id"]').val();
            t.table = $('#responses');
            t.idxLastRow = t.table.find('tbody tr').length - 1;
            t.disableControlButtonAndSelectionBox();
            $('#newresponsebutton').click(function(e) {
                e.preventDefault();
                t.idxLastRow++;
                t.newRowId = 'qtype-pmatch-new-response_' + t.idxLastRow;
                Templates.render('qtype_pmatch/newresponse', {newrowid: t.newRowId})
                    .done(function(html) {
                        t.table.append(html);
                        $('html, body').animate({
                            scrollTop: $('#' + t.newRowId).offset().top
                        }, 800);
                        $('.new-expectedfraction').focus();
                        M.core_formchangechecker.set_form_changed();
                    });
                t.disableControlButtons(true);
            });

            var timeoutCheckResponse = null;

            // Check response when key up or paste content.
            $(document).on('keyup paste', '.new-response', function() {
                var response = $(this).val().trim();

                if (timeoutCheckResponse) {
                    M.util.js_complete('checkresponse');
                    clearTimeout(timeoutCheckResponse);
                }
                M.util.js_pending('checkresponse');
                timeoutCheckResponse = setTimeout(function() {
                    if (response === '') {
                        t.handleSaveNewResponseButton(false, '');
                    } else {
                        t.checkResponse(response);
                    }
                    M.util.js_complete('checkresponse');
                }, 500);
            });

            $(document).on('keydown', '.new-response', function(e) {
                if (e.keyCode == KeyCodes.enter) {
                    t.saveNewResponse();
                    return false;
                }
            });

            $(document).on('keydown', '.new-expectedfraction, .new-response, .savenewresponse, .cancelnewresponse', function(e) {
                if (e.keyCode == KeyCodes.escape) {
                    t.cancelNewResponse();
                }
            });

            $(document).on('click', '.savenewresponse', function() {
                return t.saveNewResponse();
            });
            $(document).on('click', '.cancelnewresponse', function() {
                t.cancelNewResponse();
            });
        },

        /**
         * Submit to save a new response.
         */
        saveNewResponse: function() {
            var response = $('.new-response').val().trim();
            if (response !== '') {
                var mark = $('.new-expectedfraction').is(':checked') ? 1 : 0;
                var promises = Ajax.call([{
                    methodname: 'qtype_pmatch_create_response',
                    args: {questionid: t.questionId, expectedfraction: mark, response: response, curentrow: t.idxLastRow}
                }], true);
                promises[0]
                    .done(function(result) {
                        if (result.status === 'error') {
                            t.handleSaveNewResponseButton(false, result.message);
                        } else {
                            t.disableControlButtons(false);
                            $('#' + t.newRowId).detach();
                            t.table.append($(result.html));
                            var resultssummary = M.util.get_string('testquestionresultssummary', 'qtype_pmatch', result.counts);
                            $('#testquestion_gradesummary').html(resultssummary);
                        }
                    })
                    .fail(function(response) {
                        t.handleSaveNewResponseButton(false, response.message);
                    });
                t.disableControlButtonAndSelectionBox();
                t.resetFormState();
            }
        },

        /**
         * Cancel to input a new response.
         */
        cancelNewResponse: function() {
            $('#' + t.newRowId).remove();
            t.disableControlButtons(false);
            t.disableControlButtonAndSelectionBox();
            t.resetFormState();
            t.idxLastRow--;
        },

        /**
         * Function check correct a response.
         *
         * @method checkResponse
         * @param {String} response The response to check.
         */
        checkResponse: function(response) {
            var promises = Ajax.call([{
                methodname: 'qtype_pmatch_check_response',
                args: {questionid: t.questionId, response: response}
            }], true);
            promises[0]
                .done(function(result) {
                    var isCorrectResponse = false;
                    if (result.status == 'success') {
                        result.message = '';
                        isCorrectResponse = true;
                    }
                    t.handleSaveNewResponseButton(isCorrectResponse, result.message);
                })
                .fail(function(response) {
                    t.handleSaveNewResponseButton(false, response.message);
                });
        },

        /**
         * Disable or enable for save button when and update the error message.
         *
         * @method handleSaveNewResponseButton
         * @param {Boolean} isCorrectResponse Response input is correct or not.
         * @param {String} message The message for the error.
         */
        handleSaveNewResponseButton: function(isCorrectResponse, message) {
            if (isCorrectResponse) {
                $('.savenewresponse').removeAttr('disabled');
            } else {
                $('.savenewresponse').attr('disabled', 'true');
            }
            $('.response.error').html(message);
        },

        /**
         * Function disable or enable the outside table buttons when create new response.
         *
         * @method disableControlButtons
         * @param {Bool} disable true if disable else enable the buttons.
         */
        disableControlButtons: function(disable) {
            if (disable) {
                $('#newresponsebutton').attr('disabled', 'true');
                $('#deleteresponsesbutton').attr('disabled', 'true');
                $('#testresponsesbutton').attr('disabled', 'true');
            } else {
                $('#newresponsebutton').removeAttr('disabled');
                $('#deleteresponsesbutton').removeAttr('disabled');
                $('#testresponsesbutton').removeAttr('disabled');
            }
        },

        /**
         * Notify user when the form Add new response has changed.
         */
        resetFormState: function() {
            if (M.core_formchangechecker.get_form_dirty_state()) {
                M.core_formchangechecker.reset_form_dirty_state();
            }
        },

        /**
         * Function disable or enable selection box,delete response button,test response button.
         *
         * @method disableControlButtons
         */
        disableControlButtonAndSelectionBox: function() {
            var checkbox = $('#tqheadercheckbox');
            var table = $('#responses');
            if (table.find('tbody tr:not(.emptyrow)').length <= 0) {
                checkbox.attr('disabled', true);
                $('#deleteresponsesbutton').attr('disabled', 'true');
                $('#testresponsesbutton').attr('disabled', 'true');
            } else {
                checkbox.removeAttr('disabled');
                $('#deleteresponsesbutton').removeAttr('disabled');
                $('#testresponsesbutton').removeAttr('disabled');
            }
        }
    };
    return t;
});
