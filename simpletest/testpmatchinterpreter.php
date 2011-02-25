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
 * This file contains tests that tests the interpretation of a pmatch string.
 *
 * @package qtype
 * @subpackage pmatch
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot . '/question/type/pmatch/pmatchinterpreter.php');


class qtype_pmatch_interpreter extends UnitTestCase {
/*    public function test_qtype_pmatch_all() {
        $interpretall = new qtype_pmatch_interpreter_match_all();
        $this->assertEqual(array(true, 5), $interpretall->interpret_not(' not pmatch_all()', 0));
        $this->assertEqual(array(true, 6), $interpretall->interpret_not(' not  pmatch_all()', 0));
        $this->assertEqual(array(false, 0), $interpretall->interpret_not(' notpmatch_all()', 0));
        $this->assertEqual(array(false, 2), $interpretall->interpret_not(' notpmatch_all()', 2));
        $this->assertEqual(array(true, 16), $interpretall->interpret(' not pmatch_all()', 0));
        $this->assertEqual(array(true, 17), $interpretall->interpret(' not  pmatch_all()', 0));
        $this->assertEqual(array(false, 0), $interpretall->interpret(' notpmatch_all()', 0));
        $this->assertEqual(array(false, 2), $interpretall->interpret(' notpmatch_all()', 2));
    }*/
    public function test_qtype_pmatch_character_in_word() {
        $interpretchar = new qtype_pmatch_interpreter_character_in_word();
        $this->assertEqual(array(true, 1), $interpretchar->interpret('f', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('fF', 1));

        //interpret other keyboard characters
        $this->assertEqual(array(true, 1), $interpretchar->interpret('!', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('"', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('#', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('£', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('$', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('%', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('&', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('\'', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('/', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('-', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('+', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('<', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('=', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('>', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('@', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('^', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('`', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('{', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('}', 0));
        $this->assertEqual(array(true, 1), $interpretchar->interpret('~', 0));
    }
    public function test_qtype_pmatch_special_character_in_word() {
        $interpretchar = new qtype_pmatch_interpreter_special_character_in_word();
        //test interpreting of escaped special characters
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\_', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\]', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\[', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\_', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\(', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\)', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\ ', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\|', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\\\\', 0));
        //should not interpret non escaped special characters
        $this->assertEqual(array(false, 0), $interpretchar->interpret('|', 0));
        $this->assertEqual(array(false, 0), $interpretchar->interpret(' ', 0));
        $this->assertEqual(array(false, 0), $interpretchar->interpret('(', 0));
        //should interpret escaped wild cards
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\*', 0));
        $this->assertEqual(array(true, 2), $interpretchar->interpret('\?', 0));
    }
    public function test_qtype_pmatch_wildcard_match_multiple() {
        $interpretchar = new qtype_pmatch_interpreter_wildcard_match_multiple();
        //should interpret wild cards
        $this->assertEqual(array(true, 1), $interpretchar->interpret('*', 0));

    }
    public function test_qtype_pmatch_wildcard_match_single() {
        $interpretchar = new qtype_pmatch_interpreter_wildcard_match_single();
        //should interpret wild cards
        $this->assertEqual(array(true, 1), $interpretchar->interpret('?', 0));
    }
    public function test_qtype_pmatch_word() {
        $interpretword = new qtype_pmatch_interpreter_word();
        $otherkeyboardchars = '!"#£$%&\'/-+<=>@^`{}~';
        $this->assertEqual(array(true, strlen($otherkeyboardchars)), $interpretword->interpret($otherkeyboardchars, 0));
        $alphanum = 'abcdefABCDEF1234567890';
        $anotherword = 'fgdfgdfg';
        $this->assertEqual(array(true, strlen($otherkeyboardchars)+strlen($alphanum)),
                                $interpretword->interpret($otherkeyboardchars.$alphanum.' '.$anotherword, 0));
        $this->assertEqual(array(true, strlen($otherkeyboardchars)+strlen($alphanum)+1+strlen($anotherword)),
                                $interpretword->interpret($otherkeyboardchars.$alphanum.' '.$anotherword, strlen($otherkeyboardchars)+strlen($alphanum)+1));
        $escapedspecialcharsword = '\\\\\\(\\)\\ \\|\\?\\*\\_\\[\\]'; // \() |?*_[]
        $this->assertEqual(array(true, strlen($otherkeyboardchars)+strlen($alphanum)+1+strlen($anotherword)+1+strlen($escapedspecialcharsword)),
                                $interpretword->interpret($otherkeyboardchars.$alphanum.' '.$anotherword.' '.$escapedspecialcharsword, strlen($otherkeyboardchars)+strlen($alphanum)+1+strlen($anotherword)+1));
        $this->assertEqual(array(true, strlen($otherkeyboardchars)+strlen($alphanum)+2),
                                $interpretword->interpret($otherkeyboardchars.$alphanum.'*? '.$anotherword, 0));
        $embeddedwildcards = 'ab*cdefABC?DEF12*34567890';
        $this->assertEqual(array(true, strlen($embeddedwildcards)),
                                $interpretword->interpret($embeddedwildcards, 0));
    }

    public function test_qtype_pmatch_word_delimiter() {
        $interpretworddelimiter = new qtype_pmatch_interpreter_word_delimiter();

        $this->assertEqual(array(true, 1), $interpretworddelimiter->interpret('_', 0));
        $this->assertEqual(array(true, 3), $interpretworddelimiter->interpret('   ', 0));
    }
    public function test_qtype_pmatch_phrase() {
        $interpretphrase = new qtype_pmatch_interpreter_phrase();

        $this->assertEqual(array(true, 16), $interpretphrase->interpret('hello_ginger top', 0));
        $this->assertEqual(array(true, 6), $interpretphrase->interpret('googly', 0));
        $this->assertEqual(array(false, 0), $interpretphrase->interpret('[]', 0));
    }
    public function test_qtype_pmatch_or_list_phrase() {
        $interpretphrase = new qtype_pmatch_interpreter_or_list_phrase();

        $this->assertEqual(array(true, 18), $interpretphrase->interpret('[hello_ginger top]', 0));
        $interpretphrase->interpret('[hello_ginger top');
        $this->assertEqual(get_string('ie_missingclosingbracket', 'qtype_pmatch', '[hello_ginger top'), $interpretphrase->get_error_message());
        $this->assertEqual(array(true, 8), $interpretphrase->interpret('[googly]', 0));
        $this->assertEqual(array(false, 0), $interpretphrase->interpret('[]', 0));
    }
    public function test_qtype_pmatch_or_list() {
        $interpretorlist = new qtype_pmatch_interpreter_or_list();

        $orlist = '[hello_ginger top]|googly|[googly]';
        $this->assertEqual(array(true, strlen($orlist)), $interpretorlist->interpret($orlist, 0));
        $this->assertEqual(array(true, 13), $interpretorlist->interpret('[googly]|popo', 0));
        $this->assertEqual(array(false, 0), $interpretorlist->interpret('[]', 0));
        $this->assertEqual(get_string('ie_unrecognisedsubcontents', 'qtype_pmatch', '[]'),
                                        $interpretorlist->get_error_message());
    }
    public function test_qtype_pmatch_match_options() {

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(less*|smaller|low*|light*)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptionserr = 'match_mow(less*|smaller|low*|light*|)';
        $this->assertEqual(array(true, strlen($matchwithoptionserr)), $interpretmatchoptions->interpret($matchwithoptionserr, 0));
        $this->assertEqual(get_string('ie_lastsubcontenttypeorcharacter', 'qtype_pmatch', 'less*|smaller|low*|light*|'),
                                        $interpretmatchoptions->get_error_message());

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(dens*|[specific gravity]|sg)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(dens*|[specific gravity]|sg)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(hello_ginger top)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow([specific gravity])';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));
        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(hello ginger top)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(hello ginger top )';
        $this->assertEqual(array(true, strlen($matchwithoptions)), $interpretmatchoptions->interpret($matchwithoptions, 0));
        $this->assertEqual(get_string('ie_lastsubcontenttypeworddelimiter', 'qtype_pmatch', 'top '),
                                        $interpretmatchoptions->get_error_message());
                                        
        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_mow(hello ginger top [specific gravity]|sg)';
        $this->assertEqual(array(true, strlen($matchwithoptions)), $interpretmatchoptions->interpret($matchwithoptions, 0));
    }
    public function test_qtype_pmatch_whole_expression() {
        $wholeexpression = new qtype_pmatch_interpreter_whole_expression();
        $expression = <<<EOF
match_all (
    match_mow(great&|high|higher|more|bigger|heavier|heavy dens&|[specific gravity]|sg)
    not (
        match_mw(water|not|higher)
   )
)
EOF;
        $this->assertEqual(array(true, strlen($expression)), $wholeexpression->interpret($expression));

        $wholeexpression = new qtype_pmatch_interpreter_whole_expression();
        $expression = <<<EOF
match_all (
    match_any (
         match_mw(great&|high|higher|more|bigger|heavier|heavy dens&|[specific gravity]|sg than_water)
         match_mw(dens&|[specific gravity]|sg great&|high|higher|more|bigger|heavier|heavy than_water)
         match_mw(dens& water < dens& oil)
    )
    not (
         match_w(than_oil)
   )
)
EOF;
        $this->assertEqual(array(true, strlen($expression)), $wholeexpression->interpret($expression));
        $matcher = $wholeexpression->get_matcher();

        $wholeexpression = new qtype_pmatch_interpreter_whole_expression();
        $expression = <<<EOF
match_all (
    match_mow(less&|smaller|low&|light& dens&|[specific gravity]|sg)
    not (
        match_mw(water|not|higher)
    )
)
EOF;
        $this->assertEqual(array(true, strlen($expression)), $wholeexpression->interpret($expression));
    }
    public function test_qtype_pmatch_not() {
        $wholeexpression = new qtype_pmatch_interpreter_whole_expression();
        $expression = <<<EOF
not (
    match_mw (water|not|higher)
)
EOF;
        $this->assertEqual(array(true, strlen($expression)), $wholeexpression->interpret($expression));
    }
    public function test_qtype_pmatch_whitespace_removal_tests() {
        $wholeexpression = new qtype_pmatch_interpreter_match_all();
        $expression = <<<EOF
match_all(
    match_mow(great&|high|higher|more|bigger|heavier|heavy dens&|[specific gravity]|sg)
)
EOF;
        $this->assertEqual(array(true, strlen($expression)), $wholeexpression->interpret($expression));
    }
}
