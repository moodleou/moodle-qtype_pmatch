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

namespace qtype_pmatch\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use qtype_pmatch\form_utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/**
 * This is the check expression API for pattern match question type.
 *
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_expression extends external_api {
    /**
     * Describes the return value for check_valid_expression
     *
     * @return external_single_structure
     */
    public static function check_valid_expression_returns(): external_single_structure {
        return new external_single_structure([
            'isvalid' => new external_value(PARAM_BOOL, 'Status when check'),
            'message' => new external_value(PARAM_RAW, 'The error message', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Describes the parameters for check_valid_expression webservice.
     *
     * @return external_function_parameters
     */
    public static function check_valid_expression_parameters(): external_single_structure {
        return new external_function_parameters([
            'expressionvalue' => new external_value(PARAM_TEXT, 'The expressionvalue is'),
        ]);
    }

    /**
     * Execute and check valid expression.
     *
     * @param string $expressionvalue The expression value.
     * @return array The status and data after execute and check valid expression.
     */
    public static function check_valid_expression(string $expressionvalue): array {
        $params = self::validate_parameters(self::check_valid_expression_parameters(), [
            'expressionvalue' => $expressionvalue,
        ]);
        $errors = form_utils::validate_pmatch_expression($params['expressionvalue']);

        return ['isvalid' => $errors === '', 'message' => $errors];
    }
}
