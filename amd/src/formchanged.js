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
 * This file exists to work-around a limitation in formslib.
 *
 * See the comment in init below for more information.
 *
 * @module    qtype_pmatch
 * @class     formchanged
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Watch the value change for element that using disableIf rule.
 *
 * @param {String} fieldNamePrefix prefix of the field name.
 */
export const init = (fieldNamePrefix) => {
    // Spellcheck feature is not enabled if the hidden field is not exist.
    if (!document.querySelector('[name="' + fieldNamePrefix + 'applydictionarycheckselectedvalue"]')) {
        return;
    }
    // Moodle formslib handles disables form fields in a way that is not helpful.
    // With HTML, hidden fields are not sent back to the server in the HTML request,
    // but in this case, formslib replaces the missing value with with the current
    // value of that field from set_data when the form was created. So, there is
    // no good way to determine which fields in the form were disabled.
    //
    // To work around this, this JS modules copies the current value of certain key fields
    // into hidden fields, so they are always submitted when the form is saved.
    document.querySelector('[name="' + fieldNamePrefix + 'applydictionarycheck"]').addEventListener('change', function() {
        document.querySelector('[name="' + fieldNamePrefix + 'applydictionarycheckselectedvalue"]').value = this.value;
    });

    document.querySelector('[name="' + fieldNamePrefix + 'allowsubscript"]').addEventListener('change', function() {
        document.querySelector('[name="' + fieldNamePrefix + 'allowsubscriptselectedvalue"]').value = this.value;
    });

    document.querySelector('[name="' + fieldNamePrefix + 'allowsuperscript"]').addEventListener('change', function() {
        document.querySelector('[name="' + fieldNamePrefix + 'allowsuperscriptselectedvalue"]').value = this.value;
    });
};
