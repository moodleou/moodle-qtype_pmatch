/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 *
 * @module    qtype_pmatch
 * @class     populate_placeholder
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Modifies the length in a placeholder string.
 *
 * @param {string} originalPlaceholder Original placeholder string.
 * @param {number} newLength New length to replace in the placeholder.
 * @return {string} Placeholder string with the new length.
 */
function modifyLengthInPlaceholder(originalPlaceholder, newLength) {
    // This regex matches the model answer placeholder format to 4 groups:
    // 1. The underscores at the start of the string.
    // 2. The length of the placeholder.
    // 3. Optional 'x' followed by a number.
    // 4. The underscores at the end of the string.
    const regex = /(_+)?(\d+)(x+\d+)?(_+)?/;
    return originalPlaceholder.replace(regex, `$1${newLength}$3$4`);
}

/**
 * Get the length of the text without <sub>, </sub>, <sup>, </sup> tags.
 *
 * @param {string} text The text to get the length of.
 * @returns {number} The length of the text without the tags.
 */
function getLengthWithoutSubSupTags(text) {
    // Remove only <sub>, </sub>, <sup>, </sup> tags from the string.
    let textWithoutHtml = text.replace(/<\/?sub>|<\/?sup>/g, '');
    // Remove the trailing whitespace.
    textWithoutHtml = textWithoutHtml.trimEnd();
    return textWithoutHtml.length + 2;
}

/**
 * Update the placeholder values based on the model answer input.
 *
 * @param {HTMLElement} modelAnswer The model answer input element.
 * @param {array} placeholderInputs An array of placeholder input elements.
 * @param {array} originalPlaceholders An array of original placeholder values.
 */
function updatePlaceholders(modelAnswer, placeholderInputs, originalPlaceholders) {
    const modelAnswerLength = getLengthWithoutSubSupTags(modelAnswer.value);

    placeholderInputs.forEach((input, index) => {
        if (/^_+$/.test(originalPlaceholders[index])) {
            input.value = "_".repeat(modelAnswerLength);
        } else {
            input.value = modifyLengthInPlaceholder(originalPlaceholders[index], modelAnswerLength);
        }
    });
}

/**
 * Reset the placeholder values to the original values.
 *
 * @param {array} placeholderInputs An array of placeholder input elements.
 * @param {array} originalPlaceholders An array of original placeholder values.
 */
function resetPlaceholders(placeholderInputs, originalPlaceholders) {
    placeholderInputs.forEach((input, index) => {
        input.value = originalPlaceholders[index];
    });
}

/**
 * Sync possible answer placeholders with model input in pmatch question type.
 *
 * @param {string} fieldNamePrefix The prefix of the field name.
 */
export const init = (fieldNamePrefix) => {
    const modelAnswer = document.querySelector('[name="' + fieldNamePrefix + 'modelanswer"]');
    const placeholderInputs = document.querySelectorAll('[name="' + fieldNamePrefix + 'placeholder"]');
    const originalPlaceholders = Array.from(placeholderInputs, input => input.value);

    if (modelAnswer.value.length !== 0) {
        updatePlaceholders(modelAnswer, placeholderInputs, originalPlaceholders);
    }

    modelAnswer.addEventListener('keyup', () => {
        // The placeholders should update when it is not empty, and it not contains only white space.
        if (modelAnswer.value.length > 0 && !/^\s*$/.test(modelAnswer.value)) {
            updatePlaceholders(modelAnswer, placeholderInputs, originalPlaceholders);
        } else {
            resetPlaceholders(placeholderInputs, originalPlaceholders);
        }
    });
};
