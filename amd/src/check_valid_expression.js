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
 * This class validation for expression of question type pmatch/pmatchjme/combinepmatch.
 *
 * @module    qtype_pmatch
 * @class     check_valid_expression
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import * as FormEvents from 'core_form/events';
import {exception as displayException} from 'core/notification';

/**
 * Validation for expression.
 */
const validation = () => {
    const answerTextareas = document.querySelectorAll('textarea[name*="answer"]');
    answerTextareas.forEach(answerextarea => {
        answerextarea.addEventListener('blur', function(e) {
            const pendingid = {};
            M.util.js_pending(pendingid);
            if (e.target.value.trim() !== '') {
                const promises = fetchMany([{
                    methodname: 'qtype_pmatch_validate_pmatch_expression',
                    args: {
                        expressionvalue: e.target.value.trim(),
                    }
                }]);
                promises[0].then(function(data) {
                    FormEvents.notifyFieldValidationFailure(e.target, data.message);
                    M.util.js_complete(pendingid);
                }).catch(displayException);
            } else {
                FormEvents.notifyFieldValidationFailure(e.target, '');
            }
        });
    });
};

export const init = () => {
    validation();
};
