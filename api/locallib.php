<?php
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
 * Local library supporting the API.
 *
 * @package question
 * @subpackage qtype_pmatch
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function try_rule($question) {
    $ruletxt = optional_param('ruletxt', '', PARAM_RAW);
    $fraction = unformat_float(optional_param('fraction', '1.0', PARAM_RAW));
    if (empty($ruletxt)) {
        $return = 'The rule is empty, please add a rule in the Answer textbox above.';
    } else if (!\qtype_pmatch\testquestion_responses::has_responses($question)) {
        $return = 'There are no responses, please upload a set of human marked responses.';
    } else if ($fraction != '1.0' && $fraction != '0.0') {
        $return = get_string('tryrulegradeerror', 'qtype_pmatch');
    } else {
        $return = \qtype_pmatch\testquestion_responses::try_rule($question, $ruletxt, $fraction);
    }
    return $return;
}