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
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Notification from 'core/notification';

/**
 * @alias qtype_pmatch/updater
 */
const t = {
    /**
     * Initialise the updater.
     */
    init: function() {
        const table = document.getElementById('attemptsform');
        table.addEventListener('click', e => {
            const updater = e.target.closest('.updater-ef');
            if (!updater) {
                return;
            }

            e.preventDefault();
            t.update(updater.dataset.id);
        });

        // Prevent the form submit when user press enter on checkbox. Toggle instead.
        table.addEventListener('keypress', e => {
            const checkbox = e.target.closest('input[type="checkbox"]');
            if (checkbox && e.key === 'Enter') {
                // Enter on checkbox should toggle it.
                e.preventDefault();
                checkbox.click();
                return;
            }

            const row = e.target.closest('tr');
            if (row && e.key === 'Enter') {
                // Ensure other actions in the table row don't submit the form.
                e.preventDefault();
                return;
            }
        });

        // We have to use legacy jQuery events here, for it to work in 3.11 and 4.0.
        const body = $('body');
        body.on('updatefailed', '[data-inplaceeditable]', e => {
            e.preventDefault();
            Notification.alert(M.util.get_string('error:title', 'qtype_pmatch'),
                    e.exception.message, M.util.get_string('ok', 'moodle'));
        });

        // We have to use legacy jQuery events here, for it to work in 3.11 and 4.0.
        body.on('updated', '[data-inplaceeditable]', e => {
            t.handleUpdated(e);
        });

        // If there is no row in table, bind core/inplace_editable to the page.
        if (document.getElementById('qtype-pmatch-testquestion_r0').classList.contains('emptyrow')) {
            require(['core/inplace_editable']);
        }
    },

    /**
     * Handle updated inplace-editable data returned.
     *
     * @param {object} e the event.
     */
    handleUpdated: function(e) {
        if (e.ajaxreturn.value === e.oldvalue) {
            return;
        }

        const result = JSON.parse(e.ajaxreturn.value);
        const existingRow = e.target.closest('tr');
        existingRow.outerHTML =
            result.html.replace(/qtype-pmatch-testquestion_r/g, existingRow.id);
        document.getElementById('testquestion_gradesummary').innerHtml = result.summary;
    },

    update: function(id) {
        const pendingid = {};
        M.util.js_pending(pendingid);

        const data = new FormData();
        data.append('qid', document.getElementById('attemptsform').querySelector('input[name="id"]').value);
        data.append('rid', id);
        data.append('expectedfraction', document.getElementById('updater-ef_' + id).innerText === '1' ? 0 : 1);
        data.append('sesskey', M.cfg.sesskey);

        fetch(M.cfg.wwwroot + '/question/type/pmatch/api/updater.php', {
            method: 'POST',
            body: data,
        }).then(response => response.json()
        ).then(result => {
            if (result.status !== 'success') {
                M.util.js_complete(pendingid);
                throw new Error(result.data);
            }

            // Update the ui.
            const updater = document.getElementById('updater-ef_' + id);
            updater.innerText = result.ef;
            updater.parentNode.previousElementSibling.innerText = result.gf;

            const tr = updater.parentNode.parentNode;
            tr.className = result.rowclass;

            document.getElementById('testquestion_gradesummary').innerHtml =
                    M.util.get_string('testquestionresultssummary', 'qtype_pmatch', result.counts);

            M.util.js_complete(pendingid);
            return; // Pointless return for eslint.
        }).catch(Notification.exception);
    }
};

export default t;
