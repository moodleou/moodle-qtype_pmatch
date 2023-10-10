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
 * This class reset the answer to the original text.
 *
 * @module    qtype_pmatch
 * @class     reset_button
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Reset to the prefill text.
 *
 * @param {String} resetButtonId Id of the reset button.
 * @param {String} answerFieldId Id of the answer field.
 * @param {String} prepopulate The original text.
 */
export const initResetButton = (resetButtonId, answerFieldId, prepopulate) => {
    const resetButton = document.getElementById(resetButtonId);
    const answerField = document.getElementById(answerFieldId);
    // Reset the answer to the original text.
    resetButton.addEventListener("click", (event) => {
        event.preventDefault();
        const answerFieldEditable = document.getElementById(answerFieldId + "editable");
        if (answerFieldEditable) {
            answerFieldEditable.innerHTML = prepopulate;
        } else {
            answerField.value = prepopulate;
        }
    });
};
