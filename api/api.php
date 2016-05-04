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
 * Ajax endpoint for pmatch.
 *
 * @package question
 * @subpackage qtype_pmatch/tryrule
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('locallib.php');

try {
    require_login();
    require_sesskey();
} catch (Exception $e) {
    $return = 'Login error: ' . $e->getMessage();
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

try {
    $qid = required_param('qid', PARAM_INT);
    $question = question_bank::load_question($qid);
} catch (Exception $e) {
    $return = 'Question id error: ' . $e->getMessage();
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

try {
    $type = required_param('type', PARAM_ALPHA);
} catch (Exception $e) {
    $return = 'API call type error: ' . $e->getMessage();
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

if (!$question || !is_a($question->qtype, 'qtype_pmatch')) {
    $return = 'The question id is not a pattern match question.';
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

if (!question_has_capability_on($question, 'edit')) {
    $return = 'You do not have permission to edit this record.';
    header('Content-type: application/json');
    echo json_encode($return);
    die();
}

$return = '';
switch ($type) {
    case 'tryrule':
        $return = try_rule($question);
        break;
}

header('Content-type: application/json');
echo json_encode($return);
