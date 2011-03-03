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


class qtype_pmatch_test extends UnitTestCase {
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

        $embeddedwildcards = 'f*ulaciou?';
        $this->assertEqual(array(true, strlen($embeddedwildcards)),
                                $interpretword->interpret($embeddedwildcards, 0));
        $matcher = $interpretword->get_matcher();
        $studentresponse = "fabulacious";
        $this->assertEqual(true, $matcher->match_word($studentresponse, new qtype_pmatch_word_level_options()));
        $studentresponse2 = "fabalacious";
        $this->assertEqual(false,$matcher->match_word($studentresponse2, new qtype_pmatch_word_level_options()));
        $embeddedwildcards = 'a*b';
        $this->assertEqual(array(true, strlen($embeddedwildcards)),
                                $interpretword->interpret($embeddedwildcards, 0));
        $studentresponse = "agooglegotb";
        $matcher = $interpretword->get_matcher();
        $this->assertEqual(true,$matcher->match_word($studentresponse, new qtype_pmatch_word_level_options()));
        $studentresponse2 = "agooglebotb";
        $matcher = $interpretword->get_matcher();
        $this->assertEqual(true,$matcher->match_word($studentresponse2, new qtype_pmatch_word_level_options()));
        $studentresponse2 = "agooglebotc";
        $matcher = $interpretword->get_matcher();
        $this->assertEqual(false,$matcher->match_word($studentresponse2, new qtype_pmatch_word_level_options()));

        $embeddedwildcardsandspecialcharacters = 'a\?*\*b';
        $this->assertEqual(array(true, strlen($embeddedwildcardsandspecialcharacters)),
                                $interpretword->interpret($embeddedwildcardsandspecialcharacters, 0));
        $studentresponse = "a?googlegot*b";
        $matcher = $interpretword->get_matcher();
        $this->assertEqual(true,$matcher->match_word($studentresponse, new qtype_pmatch_word_level_options()));

        $mixedwildcardsandspecialcharacters = 'a\?*\*b?';
        $this->assertEqual(array(true, strlen($mixedwildcardsandspecialcharacters)),
                                $interpretword->interpret($mixedwildcardsandspecialcharacters, 0));
        $studentresponse = "a?googlegot*by";
        $matcher = $interpretword->get_matcher();
        $this->assertEqual(true,$matcher->match_word($studentresponse, new qtype_pmatch_word_level_options()));
    }
    public function test_qtype_pmatch_word_with_word_level_options() {
        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abba';
        $this->assertEqual(array(true, strlen($pmatchcode)),
                                $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "agooglegotbbacadabra";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_allow_extra_characters(true);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcdfe";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
        $wordleveloptions->set_misspellings(1);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcdefh";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_extra_char(true);
        $wordleveloptions->set_misspellings(1);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcde";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_fewer_char(true);
        $wordleveloptions->set_misspellings(1);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcdej";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_replace_char(true);
        $wordleveloptions->set_misspellings(1);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcdejk";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_replace_char(true);
        $wordleveloptions->set_misspelling_allow_extra_char(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abdefk";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_replace_char(true);
        $wordleveloptions->set_misspelling_allow_extra_char(true);
        $wordleveloptions->set_misspelling_allow_fewer_char(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcfde";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
        $wordleveloptions->set_misspellings(1);
        $this->assertEqual(false, $matcher->match_word($studentresponse, $wordleveloptions));

        $interpretword = new qtype_pmatch_interpreter_word();
        $pmatchcode = 'abcdef';
        $this->assertEqual(array(true, strlen($pmatchcode)), $interpretword->interpret($pmatchcode, 0));
        $studentresponse = "abcdfe";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_transpose_two_chars(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));
        $this->assertEqual(1, $matcher->get_used_misspellings());
        $studentresponse = "abcdf";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_fewer_char(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));
        $this->assertEqual(1, $matcher->get_used_misspellings());
        $studentresponse = "abcdefg";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_extra_char(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));
        $this->assertEqual(1, $matcher->get_used_misspellings());
        $studentresponse = "abcgdefg";
        $matcher = $interpretword->get_matcher();
        $wordleveloptions = new qtype_pmatch_word_level_options();
        $wordleveloptions->set_misspelling_allow_extra_char(true);
        $wordleveloptions->set_misspellings(2);
        $this->assertEqual(true, $matcher->match_word($studentresponse, $wordleveloptions));
        $this->assertEqual(2, $matcher->get_used_misspellings());
    }
    public function test_qtype_pmatch_word_delimiter() {

        $interpretworddelimiter = new qtype_pmatch_interpreter_word_delimiter_proximity();
        $this->assertEqual(array(true, 1), $interpretworddelimiter->interpret('_', 0));

        $interpretworddelimiter = new qtype_pmatch_interpreter_word_delimiter_space();
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
        $matcher = $interpretorlist->get_matcher();
        $this->assertEqual(true,$matcher->match_phrase(array('hello', 'ginger', 'top')
                    , new qtype_pmatch_phrase_level_options(), new qtype_pmatch_word_level_options()));
        $this->assertEqual(true,$matcher->match_word('googly', new qtype_pmatch_word_level_options()));
        
        $this->assertEqual(array(true, 13), $interpretorlist->interpret('[googly]|popo', 0));
        $this->assertEqual(array(false, 0), $interpretorlist->interpret('[]', 0));
        $this->assertEqual(get_string('ie_unrecognisedsubcontents', 'qtype_pmatch', '[]'),
                                        $interpretorlist->get_error_message());
    }
    public function test_qtype_pmatch_match_options() {

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match(less*|smaller|low*|light* calories)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));
        $matcher = $interpretmatchoptions->get_matcher();
        $this->assertEqual(true, 
                $matcher->match_phrase(array('less', 'calories'), new qtype_pmatch_phrase_level_options(), new qtype_pmatch_word_level_options()));

        $interpretmatchoptions = new qtype_pmatch_interpreter_match_options();
        $matchwithoptions = 'match_ow(less*|smaller|low*|light* calories)';
        $this->assertEqual(array(true, strlen($matchwithoptions)),
                                $interpretmatchoptions->interpret($matchwithoptions, 0));
        $matcher = $interpretmatchoptions->get_matcher();
        $this->assertEqual(true, 
                $matcher->match_whole_expression('calories are less likely to flower.'));

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
        $this->assertEqual(get_string('ie_lastsubcontenttypeworddelimiter', 'qtype_pmatch', $matchwithoptions),
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
