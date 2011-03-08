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

class pmatch_test extends UnitTestCase {
    protected function match($string, $expression, $options = null) {
        $interpreter = new qtype_pmatch_interpreter_match_options();
        $expression = $interpreter->interpret($expression);
        $matcher = $interpreter->get_matcher();
        return $matcher->match_whole_expression($string);
    }
 
    public function test_pmatch() {
        // Tests from the original pmatch documentation.
        $this->assertTrue($this->match('tom dick harry', 'match(tom dick harry)')); // This is the exact match.
        $this->assertTrue($this->match('thomas', 'match_c(tom)')); // Extra characters are allowed anywhere within the word.
        $this->assertTrue($this->match('tom dick and harry', 'match_w(dick)')); // Extra words are allowed anywhere within the sentence.
        $this->assertTrue($this->match('harry dick tom', 'match_o(tom dick harry)')); // Any order of words is allowed.
        $this->assertTrue($this->match('rick', 'match_m(dick)')); // One character in the word can differ.
        $this->assertTrue($this->match('rick and harry and tom', 'match_mow(tom dick harry)'));
        $this->assertTrue($this->match('dick and harry and thomas', 'match_cow(tom dick harry)'));
        $this->assertTrue($this->match('arthur harry and sid', 'match_mow(tom|dick|harry)')); // Any of tom or dick or harry will be matched.
        $this->assertTrue($this->match('tomy harry and sid', 'match_mow(tom|dick harry|sid)')); // The pattern requires either tom or dick AND harry or sid.
        $this->assertTrue($this->match('tom was mesmerised by maud', 'match_mow([tom maud]|[sid jane])')); // The pattern requires either (tom and maud) or (sid and jane).
        $this->assertTrue($this->match('rick', 'match(?ick)')); // The first character can be anything.
        $this->assertTrue($this->match('harold', 'match(har*)')); // Any sequence of characters can follow 'har'.
        $this->assertTrue($this->match('tom married maud sid married jane', 'match_mow(tom_maud)')); // Only one word is between tom and maud.
        $this->assertFalse($this->match('maud married tom sid married jane', 'match_mow(tom_maud)')); // The proximity control also specifies word order and over-rides the 'o' matching option.
        $this->assertFalse($this->match('tom married maud sid married jane', 'match_mow(tom_jane)')); // Only two words are allowed between tom and jane.

        $this->assertTrue($this->match('married', 'match_mow(marr*)'));
        $this->assertTrue($this->match('tom married maud', 'match_mow(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('maud marries thomas', 'match_mow(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('tom is to marry maud', 'match_w(tom|thomas marr* maud)'));
        $this->assertFalse($this->match('tom is to marry maud', 'match_o(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('tom is to maud marry', 'match_ow(tom|thomas marr* maud)'));
        $this->assertFalse($this->match('tom is to maud marry', 'match_w(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('tempratur', 'match_m2ow(temperature)')); // Two characters are missing.
        $this->assertFalse($this->match('tempratur', 'match_mow(temperature)')); // Two characters are missing.
        $this->assertTrue($this->match('temporatur', 'match_m2ow(temperature)')); // Two characters are incorrect; one has been replaced and one is missing.
        $this->assertFalse($this->match('temporatur', 'match_mow(temperature)')); // Two characters are incorrect; one has been replaced and one is missing.
        $this->assertFalse($this->match('tmporatur', 'match_m2ow(temperature)')); // Three characters are incorrect; one has been replaced and two are missing.
        
        $this->assertTrue($this->match('cat toad frog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat newt frog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('dog', 'match([toad frog]|dog)'));
        $this->assertTrue($this->match('cat toad frog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat newt frog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat x x toad frog x', 'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat newt x x x x x frog x', 'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat x x dog x', 'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertFalse($this->match('A C B D', 'match([A B]_[C D])'));
        $this->assertFalse($this->match('B C A D', 'match_o([A B]_[C D])'));
        $this->assertTrue($this->match('A x x x x B C D', 'match_ow([A B]_[C D])'));
        $this->assertFalse($this->match('B x x x x A C D', 'match_ow([A B]_[C D])'));  //_ requires the words in [] to match in order.
        $this->assertFalse($this->match('A B C', 'match_ow([A B]_[B C])'));
        $this->assertFalse($this->match('A A', 'match(A)'));
        
        // Tests of the misspelling rules.
        $this->assertTrue($this->match('test', 'match(test)'));
        $this->assertFalse($this->match('tes', 'match(test)'));
        $this->assertFalse($this->match('testt', 'match(test)'));
        $this->assertFalse($this->match('tent', 'match(test)'));
        $this->assertFalse($this->match('tets', 'match(test)'));
 
        $this->assertTrue($this->match('test', 'match_mf(test)'));
        $this->assertTrue($this->match('tes', 'match_mf(test)'));
        $this->assertFalse($this->match('testt', 'match_mf(test)'));
        $this->assertFalse($this->match('tent', 'match_mf(test)'));
        $this->assertFalse($this->match('tets', 'match_mf(test)'));
        $this->assertTrue($this->match('te', 'match_mf(tes)'));
 
        // and so on.
    }
}