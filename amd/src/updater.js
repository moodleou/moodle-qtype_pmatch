// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This class provides functionality for the testquestion response updater.
 *
 * @module    qtype_pmatch
 * @class     updater
 * @package   question
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification'], function($, Notification) {
    /**
     * @alias qtype_pmatch/updater
     */
    var t = {
        baseUrl: '',
        sessKey: '',
        qid: '',
        headerCheckboxChecked: true,
        /**
         *  The string need to be replaced to get correct row.
         */
        REPLACESTRING: /qtype-pmatch-testquestion_r/g,
        /**
         * Initialise the updater.
         */
        init: function() {
            var base = $('#attemptsform').attr('action');
            var body = $('body');
            t.baseUrl = base.replace('testquestion.php', 'api/updater.php');
            t.sessKey = $('#attemptsform input[name="sesskey"]').val();
            t.qid = $('#attemptsform input[name="id"]').val();
            $(document).on('click', '.updater-ef', function() {
                var id = $(this).data('id');
                t.update(id);
                return false;
            });
            // Prevent the form submit when user press enter on checkbox.
            $(document).on('keypress', '#tablecontainer :checkbox', function(e) {
                if ((e.keyCode ? e.keyCode : e.which) == 13) {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
            $('#tqheadercheckbox').click(function() {
                if (t.headerCheckboxChecked) {
                    $(this).attr('title', M.util.get_string('deselectall', 'moodle'));
                    t.headerCheckboxChecked = false;
                } else {
                    $(this).attr('title', M.util.get_string('selectall', 'moodle'));
                    t.headerCheckboxChecked = true;
                }
                $('#tablecontainer :checkbox').each(function() {
                    this.checked = !t.headerCheckboxChecked;
                });
                $(this).prop('checked', false);
            });

            body.on('updatefailed', '[data-inplaceeditable]', function(e) {
                var exception = e.exception;
                e.preventDefault();
                Notification.alert(M.util.get_string('error:title', 'qtype_pmatch'),
                    exception.message, M.util.get_string('ok', 'moodle'));
            });
            body.on('updated', '[data-inplaceeditable]', function(e) {
                t.handleUpdated(e, this);
            });
            t.bindInplaceEditEvent();
        },
        /**
         * If there is no row in table, bind core/inplace_editable to the page.
         */
        bindInplaceEditEvent: function() {
            if ($('#qtype-pmatch-testquestion_r0').hasClass('emptyrow')) {
                require(['core/inplace_editable']);
            }
        },
        /**
         * Handle updated inplace-editable data returned.
         * @param {object} e the event handlers.
         * @param {object} target the DOM element.
         */
        handleUpdated: function(e, target) {
            var ajaxReturn = e.ajaxreturn;
            if (ajaxReturn.value !== e.oldvalue) {
                var jsonDecode = $.parseJSON(ajaxReturn.value);
                var row = $(target).parent().parent();
                var currentRow = row.attr('id');
                var html = jsonDecode.html;
                $(row).replaceWith(html.replace(t.REPLACESTRING, currentRow));
                $('#testquestion_gradesummary').html(jsonDecode.summary);
            }
        },
        update: function(id) {
            var val = $('#updater-ef_' + id).text();
            var ef = 0;
            if (val === '1') {
                ef = 0;
            } else {
                ef = 1;
            }
            // Send update.
            var data = {qid: t.qid, rid: id, expectedfraction: ef, sesskey: t.sessKey};
            $.post(t.baseUrl, data, function(result) {
                if (result.status === 'success') {
                    // Update the ui.
                    $('#updater-ef_' + id).text(result.ef);
                    $('#updater-ef_' + id).parent().prev().text(result.gf);
                    var tr = $('#updater-ef_' + id).parent().parent();
                    tr.removeClass();
                    tr.addClass(result.rowclass);
                    tr.find('td[class="c3"]').text(result.gf);
                    // Update the grade summary.
                    var c = M.util.get_string('testquestionresultssummary', 'qtype_pmatch', result.counts);
                    $('#testquestion_gradesummary').html(c);
                } else {
                    // Developer debugging - failure states are in api/updater.php.
                    window.console.log(
                        'Testquestion response updater has experienced an issue.\n' + result.data);
                    // If spinner is added - remove it here $('#updater-ef_' + id).text(val);.
                }
            });
        }
    };

    return t;
});
