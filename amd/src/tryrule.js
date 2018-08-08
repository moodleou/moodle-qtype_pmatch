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
 * This class provides functionality for try rule.
 *
 * This is based on the work of Dr Alistair Willis published:
 * http://aclweb.org/anthology/W/W15/W15-0628.pdf
 *
 * @module    qtype_pmatch
 * @class     tryrule
 * @package   question
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     2.9
 */
define(['jquery'], function($) {

    /**
     * @alias qtype_pmatch/tryrule
     */
    var t = {
        baseUrl: '',
        sessKey: '',
        qid: '',
        pendingid: '',

        /**
         * Initialise the try rule button.
         */
        init: function() {
            // Set up base variables.
            t.pendingid = 'tryrule_' + Math.random().toString(36).slice(2); // Random string.
            var base = window.location;
            t.baseUrl = base.protocol + '//' + base.host +
                    base.pathname.replace('question.php', 'type/pmatch/api/api.php');
            t.sessKey = $('#mform1 input[name="sesskey"]').val();
            t.qid = $('#mform1 input[name="id"]').val();
            // Add ids to try rule buttons.
            $('textarea[name^="answer"]').each(function() {
                var id = $(this).attr('id').replace('id_answer_', '');
                $(this).parent().parent().next().next().find('input').attr('id', 'id_tryrule_' + id);
            });
            $('input[name="tryrule"]').on('click', function(e) {
                e.preventDefault();
                var id = $(this).attr('id').replace('id_tryrule_', '');
                t.tryrule(id);
            });
        },

        tryrule: function(id) {
            M.util.js_pending(t.pendingid);
            var rule = $('#id_answer_' + id).val();
            if (rule === undefined || rule === null || rule === '') {
                return;
            }
            rule = rule.trim();
            if (rule === '') {
                return;
            }
            var display = $('#id_tryrule_' + id).next();
            var fraction = $('#id_fraction_' + id).val();
            // Send request for tryrule result.
            var data = {type: 'tryrule', qid: t.qid, ruletxt: rule, sesskey: t.sessKey, fraction: fraction};
            $.post(t.baseUrl, data, function(result) {
                // Display feedback to the user.
                display.html(result);
                M.util.js_complete(t.pendingid);
            }, 'json');
        }
    };

    return t;
});
