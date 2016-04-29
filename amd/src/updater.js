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
define(['jquery'], function($) {

    /**
     * @alias qtype_pmatch/updater
     */
    var t = {
        baseUrl: '',
        sessKey: '',
        qid: '',

        /**
         * Initialise the updater.
         */
        init: function() {
            var base = $('#attemptsform').attr('action');
            t.baseUrl = base.replace('testquestion.php', 'api/updater.php');
            t.sessKey = $('#attemptsform input[name="sesskey"]').val();
            t.qid = $('#attemptsform input[name="id"]').val();
            $('.updater-expectedfraction').each(function() {
                // Expected fraction column. The header does not have an id.
                var iden = $(this).attr('id');
                if (iden) {
                    // The response id is always in column 1.
                    var x = iden.slice(0, -1) + '1';
                    var id = $(this).prevAll('#' + x).text();
                    var val = $(this).text();
                    if (val === '') {
                        val = '&nbsp;&nbsp;'; // Two spaces looks better than one.
                    }
                    // Make the Human marks column items clickable (updateable).
                    $(this).html('<a href="#" title="Change score" id="updater-ef_' + id + '">' +
                            val + '</a>');
                    $('#updater-ef_' + id).click(function() {
                        t.update(id);
                        return false;
                    });
                }
            });
            $(document).ajaxError(function() {
                // Network problem or similar.
                window.alert('Sorry there appears to be an error connecting to the server.\n' +
                        'Please try again later. (Sometimes refreshing the page can ' +
                        'avoid a session timeout issue.)');
            });
        },
        update: function(id) {
            var val = $('#updater-ef_' + id).text();
            // If a spinner is required add it here $('#updater-ef_' + id).text(val + ' <img src="spinner.gif" alt="wait"/>');
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
