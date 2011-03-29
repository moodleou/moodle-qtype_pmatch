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
 * JavaScript objects, functions as well as usage of some YUI library for
 * enabling sub and super script usage.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.qtype_pmatch = {};
M.qtype_pmatch.initeditor = function (Y, editorid, editorwidth, editorheight, subscript, superscript) {
    var textstylebuttons = new Array();
    if (subscript){
        textstylebuttons.push({ type: 'push', label: 'Subscript', value: 'subscript' });
    }
    if (superscript){
        textstylebuttons.push({ type: 'push', label: 'Superscript', value: 'superscript' });
    }
    var editorconfig = {height: editorheight, width: editorwidth,
        toolbar: { buttons: [ { group: 'textstyle',  buttons: textstylebuttons } ] }
    };

    var editor = new YAHOO.widget.Editor(editorid, editorconfig);

    editor.on('beforeEditorKeyDown', function(e) {
        switch (e.ev.keyCode) {
            case 13: // Enter
                YAHOO.util.Event.stopEvent(e.ev);
                return false;
            case 38: // Up
                editor.execCommand('superscript');
                YAHOO.util.Event.stopEvent(e.ev);
                return false;
            case 40: // Down
                editor.execCommand('subscript');
                YAHOO.util.Event.stopEvent(e.ev);
                return false;
        }
    });


    editor.render();
}
