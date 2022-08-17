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
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     2.9
 */

import Notification from 'core/notification';

/**
 * Try rule support send request try rule.
 *
 * @param {number} answerNumber id answer try rule
 * @param {Element} tryRow is element button click call tryrule function
 */
const tryRule = (answerNumber, tryRow) => {
    const rule = document.getElementById('id_answer_' + answerNumber).value;
    if (!rule) {
        return;
    }

    // Send request for tryrule result.
    const pendingid = {};
    M.util.js_pending(pendingid);

    const data = new FormData();
    data.append('type', 'tryrule');
    data.append('qid', tryRow.closest('form').querySelector('input[name="id"]').value);
    data.append('ruletxt', rule);
    data.append('fraction', document.getElementById('id_fraction_' + answerNumber).value);
    data.append('sesskey', M.cfg.sesskey);

    fetch(M.cfg.wwwroot + '/question/type/pmatch/api/api.php', {
        method: 'POST',
        body: data,
    }).then(response => response.json()
    ).then(text => {
        tryRow.querySelector('.try-rule-result').innerHTML = text;
        M.util.js_complete(pendingid);
        return;
    }).catch(Notification.exception);
};

export const init = () => {
    document.addEventListener('click', (e) => {
        const button = e.target.closest('input[name="tryrule"]');
        if (!button) {
            // Not an event we care about. Ignore.
            return;
        }

        // This is ours.
        e.preventDefault();

        const tryRow = button.closest('.try-rule');

        // Find the corresponding answer rule id.
        let ruleRow = tryRow;
        while (!ruleRow.matches('.answer-rule')) {
            ruleRow = ruleRow.previousElementSibling;
        }
        const answerNumber = ruleRow.id.replace('fitem_id_answer_', '');

        tryRule(answerNumber, tryRow);
    });
};
