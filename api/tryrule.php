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
 * Ajax endpoint for try rule.
 *
 * @package question
 * @subpackage qtype_pmatch/tryrule
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/questionlib.php');

try {
    require_login();
    require_sesskey();
} catch (Exception $e) {
    $return = 'Login error: ' . $e->getMessage();
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

$qid = optional_param('qid', 0, PARAM_INT);
$ruletxt = optional_param('ruletxt', '', PARAM_RAW);
$grade = unformat_float(optional_param('grade', '1.0', PARAM_RAW));
try {
    $question = question_bank::load_question($qid);
} catch (Exception $e) {
    $return = 'Question id error: ' . $e->getMessage();
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

if (!$question || !is_a($question->qtype, 'qtype_pmatch')) {
    $return = 'The question id is not a pattern match question.';
} else if (!question_has_capability_on($question, 'edit')) {
    $return = 'You do not have permission to edit this record.';
} else if (empty($ruletxt)) {
    $return = 'The rule is empty, please add a rule in the Answer textbox above.';
} else if (!\qtype_pmatch\test_responses::has_responses($question)) {
    $return = 'There are no responses, please upload a set of human marked responses.';
} else if ($grade != '1.0' && $grade != '0.0') {
    $return = get_string('tryrulegradeerror', 'qtype_pmatch');
} else {
    $return = \qtype_pmatch\test_responses::try_rule($question, $ruletxt, $grade);
}

header('Content-type: application/json');
echo json_encode($return);
