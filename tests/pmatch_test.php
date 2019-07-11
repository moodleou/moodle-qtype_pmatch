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
 * @package   qtype_pmatch
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');

/**
 * Tests of the interpretation of the pmatch pattern in the pmatch library.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group     qtype_pmatch
 */
class qtype_pmatch_test extends basic_testcase {
    protected function match($string, $expression, $options = null) {
        $string = new pmatch_parsed_string($string, $options);
        $expression = new pmatch_expression($expression, $options);
        $this->assertEquals('', $expression->get_parse_error());
        return $expression->matches($string);
    }

    protected function error_message($expression, $options = null) {
        $expression = new pmatch_expression($expression, $options);
        return $expression->get_parse_error();
    }

    public function test_strip_sentence_divider() {
        $options = new pmatch_options();

        $this->assertEquals('Cat', $options->strip_sentence_divider('Cat'));
        $this->assertEquals('Cat', $options->strip_sentence_divider('Cat.'));

        $options->sentencedividers = '';
        $this->assertEquals('Cat', $options->strip_sentence_divider('Cat'));
        $this->assertEquals('Cat.', $options->strip_sentence_divider('Cat.'));
    }

    /**
     * Data provider function for test_pmatch_error
     *
     * @return array
     */
    public function pmatch_error_provider() {
        return [
                // No closing bracket.
                ['match_mow([tom maud]|[sid jane]', get_string('ie_missingclosingbracket',
                        'qtype_pmatch', 'match_mow([tom maud]|[sid jane]')],
                // No contents.
                ['match_mow()', get_string('ie_unrecognisedsubcontents',
                        'qtype_pmatch', 'match_mow()')],
                // Ends in an or character.
                ['match_mow([tom maud]|)', get_string('ie_lastsubcontenttypeorcharacter',
                        'qtype_pmatch', '[tom maud]|')],
                // Ends in a space.
                ['match_mow([tom maud] )', get_string('ie_lastsubcontenttypeworddelimiter',
                        'qtype_pmatch', 'match_mow([tom maud] )')],
                // Ends in a proximity delimiter.
                ['match_mow([tom maud]_)', get_string('ie_lastsubcontenttypeworddelimiter',
                        'qtype_pmatch', 'match_mow([tom maud]_)')],
                // A full stop is only allowed in match expressions if surrounded on both sides by digits.
                ['match(abc.)', ''],
                ['match(abc.def)', ''],
                ['match(2.)', ''],
                ['match(.2)', ''],
                ['match(3.141)', ''],
                // Sentence dividers.
                ['match(Is this an statement.)', ''],
                ['match(Is this an statement?)', ''],
                ['match(This is an statement!)', ''],
                // Character punctuation.
                ['match (¡¿Y tú quién te crees?!)', ''],
                ['match (« Attends, je dois te dire quelque chose d\'important »)', ''],
                ['match («Das Mädchen ist sehr schön»)', ''],
                ['match (»Ich weiß nicht was ich sagen soll «)', ''],
                ['match („Kommst du mit?”)', ''],
        ];
    }

    /**
     * Test for messege error
     *
     * @dataProvider pmatch_error_provider
     * @param $expression
     * @param $actual
     */
    public function test_pmatch_error($expression, $actual) {
        $this->assertEquals($this->error_message($expression), $actual);
    }

    /**
     * Data provider function for test_pmatch_matching
     *
     * @return array
     */
    public function pmatch_matching_provider() {
        $options = new pmatch_options();
        $options->sentencedividers = '|$';

        $expressionallnot = <<<EOF
match_all(
    not ( match_c(a))
    not ( match_c(b))
    not ( match_c(c))
)
EOF;

        $expressionanynot = <<<EOF
match_any(
    not ( match_c(a))
    not ( match_c(b))
    not ( match_c(c))
)
EOF;

        $expressionany = <<<EOF
match_any(
    match_c(a)
    match_c(b)
    match_c(c)
)
EOF;
        $expressionstr = 'match_all(match_any(not(match_c(a))match_c(b))' .
                'match_all(match_all(match_any(match_c(c)match_c(d))' .
                'match_any(match_c(e)match_c(f))match_all(match_c(g)match_c(h)))' .
                'not(match_any(match_any(match_c(i)match_c(j))' .
                'match_any(match_c(k)match_c(l))match_all(match_c(m)match_c(n))))))';

        return [
                // This is the exact match.
                ['tom dick harry', 'match(tom dick harry)', true],
                // Extra characters are allowed anywhere within the word.
                ['thomas', 'match_c(tom)', true],
                // Extra words are allowed anywhere within the sentence.
                ['tom dick and harry', 'match_w(dick)', true],
                // Any order of words is allowed.
                ['harry dick tom', 'match_o(tom dick harry)', true],
                // One character in the word can differ.
                ['rick', 'match_m(dick)', true],
                ['rick and harry and tom', 'match_mow(tom dick harry)', true],
                ['dick and harry and thomas', 'match_cow(tom dick harry)', true],
                // Any of tom or dick or harry will be matched.
                ['arthur harry and sid', 'match_mow(tom|dick|harry)', true],
                // The pattern requires either tom or dick AND harry or sid.
                ['tomy harry and sid', 'match_mow(tom|dick harry|sid)', true],
                // The pattern requires either (tom and maud) or (sid and jane).
                ['tom was mesmerised by maud', 'match_mow([tom maud]|[sid jane])', true],
                // The first character can be anything.
                ['rick', 'match(?ick)', true],
                // Any sequence of characters can follow 'har'.
                ['harold', 'match(har*)', true],
                // Only one word is between tom and maud.
                ['tom married maud sid married jane', 'match_mow(tom_maud)', true],
                // The proximity control also specifies word order and over-rides the 'o' matching option.
                ['maud married tom sid married jane', 'match_mow(tom_maud)', false],
                // Only two words are allowed between tom and jane.
                ['tom married maud sid married jane', 'match_mow(tom_jane)', false],
                ['married', 'match_mow(marr*)', true],
                ['tom married maud', 'match_mow(tom|thomas marr* maud)', true],
                ['maud marries thomas', 'match_mow(tom|thomas marr* maud)', true],
                ['tom is to marry maud', 'match_w(tom|thomas marr* maud)', true],
                ['tom is to marry maud', 'match_o(tom|thomas marr* maud)', false],
                ['tom is to maud marry', 'match_ow(tom|thomas marr* maud)', true],
                ['tom is to maud marry', 'match_w(tom|thomas marr* maud)', false],
                // Two characters are missing.
                ['tempratur', 'match_m2ow(temperature)', true],
                // Two characters are missing.
                ['tempratur', 'match_mow(temperature)', false],
                // Two characters are incorrect; one has been replaced and one is missing.
                ['temporatur', 'match_m2ow(temperature)', true],
                // Two characters are incorrect; one has been replaced and one is missing.
                ['temporatur', 'match_mow(temperature)', false],
                // Three characters are incorrect; one has been replaced and two are missing.
                ['tmporatur', 'match_m2ow(temperature)', false],
                ['cat toad frog', 'match(cat [toad|newt frog]|dog)', true],
                ['cat newt frog', 'match(cat [toad|newt frog]|dog)', true],
                ['cat dog', 'match(cat [toad|newt frog]|dog)', true],
                ['dog', 'match([toad frog]|dog)', true],
                ['cat toad frog', 'match(cat_[toad|newt frog]|dog)', true],
                ['cat newt frog', 'match(cat_[toad|newt frog]|dog)', true],
                ['cat dog', 'match(cat_[toad|newt frog]|dog)', true],
                ['cat dog', 'match(cat_[toad|newt frog]|dog)', true],
                ['cat. dog', 'match(cat_[toad|newt frog]|dog)', false],
                ['cat. dog', 'match(cat [toad|newt frog]|dog)', true],
                ['x cat x x toad frog x', 'match_w(cat_[toad|newt frog]|dog)', true],
                ['x cat newt x x x x x frog x', 'match_w(cat_[toad|newt frog]|dog)', true],
                ['x cat x x dog x', 'match_w(cat_[toad|newt frog]|dog)', true],
                ['x cat x. x dog x', 'match_w(cat_[toad|newt frog]|dog)', false],
                ['A C B D', 'match([A B]_[C D])', false],
                ['B C A D', 'match_o([A B]_[C D])', false],
                ['A x x x x B C D', 'match_ow([A B]_[C D])', true],
                ['A x x x x B. C D', 'match_ow([A B]_[C D])', false],
                ['A x x x x B. C D', 'match_ow([A B] [C D])', true],
                // Requires the words in [] to match in order.
                ['B x x x x A C D', 'match_ow([A B]_[C D])', false],
                ['A B C', 'match_ow([A B]_[B C])', false],
                ['A A', 'match(A)', false],
                // Tests of the misspelling rules.
                ['test', 'match(test)', true],
                ['tes', 'match(test)', false],
                ['testt', 'match(test)', false],
                ['tent', 'match(test)', false],
                ['tets', 'match(test)', false],
                ['test', 'match_mf(test)', true],
                ['tes', 'match_mf(test)', true],
                ['testt', 'match_mf(test)', false],
                ['tent', 'match_mf(test)', false],
                ['tets', 'match_mf(test)', false],
                // Fewer characters option is disabled for a pattern
                // of fewer than 4 normal characters in pattern.
                ['te', 'match_mf(tes)', false],
                // Allow fewer characters.
                ['abcd', 'match_mf(abcd)', true],
                ['abc', 'match_mf(abcd)', true],
                ['acbd', 'match_mf(abcd)', false],
                ['abfd', 'match_mf(abcd)', false],
                ['abcf', 'match_mf(abcd)', false],
                ['bcd', 'match_mf(abcd)', true],
                ['abcdg', 'match_mf(abcd)', false],
                ['gabcd', 'match_mf(abcd)', false],
                ['abcdg', 'match_mf(abcd)', false],
                // Allow replace character.
                ['abcd', 'match_mr(abcd)', true],
                ['abc', 'match_mr(abcd)', false],
                ['acbd', 'match_mr(abcd)', false],
                ['abfd', 'match_mr(abcd)', true],
                ['abcf', 'match_mr(abcd)', true],
                ['fbcd', 'match_mr(abcd)', true],
                ['bcd', 'match_mr(abcd)', false],
                ['abcdg', 'match_mr(abcd)', false],
                ['gabcd', 'match_mr(abcd)', false],
                ['abcdg', 'match_mr(abcd)', false],
                // Allow transpose characters.
                ['abcd', 'match_mt(abcd)', true],
                ['abc', 'match_mt(abcd)', false],
                ['acbd', 'match_mt(abcd)', true],
                ['bacd', 'match_mt(abcd)', true],
                ['abdc', 'match_mt(abcd)', true],
                ['abfd', 'match_mt(abcd)', false],
                ['abcf', 'match_mt(abcd)', false],
                ['fbcd', 'match_mt(abcd)', false],
                ['bcd', 'match_mt(abcd)', false],
                ['abcdg', 'match_mt(abcd)', false],
                ['gabcd', 'match_mt(abcd)', false],
                ['abcdg', 'match_mt(abcd)', false],
                // Allow extra character.
                ['abcd', 'match_mx(abcd)', true],
                ['abc', 'match_mx(abcd)', false],
                ['acbd', 'match_mx(abcd)', false],
                ['bacd', 'match_mx(abcd)', false],
                ['abdc', 'match_mx(abcd)', false],
                ['abfd', 'match_mx(abcd)', false],
                ['abcf', 'match_mx(abcd)', false],
                ['fbcd', 'match_mx(abcd)', false],
                ['bcd', 'match_mx(abcd)', false],
                ['abcdg', 'match_mx(abcd)', true],
                ['gabcd', 'match_mx(abcd)', true],
                ['abcdg', 'match_mx(abcd)', true],
                ['abcd', 'match_mx(abcd)', true],
                ['abc', 'match_mx(abcd)', false],
                // Allow any one mispelling.
                ['abcd', 'match_m(abcd)', true],
                ['abc', 'match_m(abcd)', true],
                ['acbd', 'match_m(abcd)', true],
                ['bacd', 'match_m(abcd)', true],
                ['abdc', 'match_m(abcd)', true],
                ['abfd', 'match_m(abcd)', true],
                ['abcf', 'match_m(abcd)', true],
                ['fbcd', 'match_m(abcd)', true],
                ['bcd', 'match_m(abcd)', true],
                ['abcdg', 'match_m(abcd)', true],
                ['gabcd', 'match_m(abcd)', true],
                ['abcdg', 'match_m(abcd)', true],
                ['bacde', 'match_m(abcd)', false],
                ['badc', 'match_m(abcd)', false],
                ['affd', 'match_m(abcd)', false],
                ['fbcf', 'match_m(abcd)', false],
                ['ffcd', 'match_m(abcd)', false],
                ['bfcd', 'match_m(abcd)', false],
                ['abccdg', 'match_m(abcd)', false],
                ['gabbcd', 'match_m(abcd)', false],
                ['abbcdg', 'match_m(abcd)', false],
                // Allow any two mispelling.
                // Default to one if there are less than 8 chars in word pattern.
                ['abcd', 'match_m2(abcd)', true],
                ['abc', 'match_m2(abcd)', true],
                ['acbd', 'match_m2(abcd)', true],
                ['bacd', 'match_m2(abcd)', true],
                ['abdc', 'match_m2(abcd)', true],
                ['abfd', 'match_m2(abcd)', true],
                ['abcf', 'match_m2(abcd)', true],
                ['fbcd', 'match_m2(abcd)', true],
                ['bcd', 'match_m2(abcd)', true],
                ['abcdg', 'match_m2(abcd)', true],
                ['gabcd', 'match_m2(abcd)', true],
                ['abcdg', 'match_m2(abcd)', true],
                ['bacde', 'match_m2(abcd)', false],
                // The ffff padding is to increase pattern length to the required 8 chars
                // so that two misspellings are allowed.
                ['ffffbacde', 'match_m2(ffffabcd)', true],
                ['ffffbadc', 'match_m2(ffffabcd)', true],
                ['ffffaffd', 'match_m2(ffffabcd)', true],
                ['fffffbcf', 'match_m2(ffffabcd)', true],
                ['ffffffcd', 'match_m2(ffffabcd)', true],
                ['bfcdffff', 'match_m2(abcdffff)', true],
                ['abccdgffff', 'match_m2(abcdffff)', true],
                ['gabbcdffff', 'match_m2(abcdffff)', true],
                ['abbcdgffff', 'match_m2(abcdffff)', true],
                ['', 'match(*)', true],
                ['ABCD', 'match(abcd)', true, pmatch_options::make(['ignorecase' => true])],
                ['Mary had a little LamB', 'match(mary had a little lamb)', true,
                        pmatch_options::make(['ignorecase' => true])],
                ['ABCD', 'match(abcd)', false, pmatch_options::make(['ignorecase' => false])],
                ['efgh', 'not ( match_c(c) )', true],
                ['abc', 'not ( match_c(c) )', false],
                ['lock', 'not ( match_c(c) )', false],
                ['dog', 'not ( match_c(c) )', true],
                ['efgh', $expressionallnot, true],
                ['abc', $expressionallnot, false],
                ['lock', $expressionallnot, false],
                ['dog', $expressionallnot, true],

                ['efgh', $expressionanynot, true],
                ['abc', $expressionanynot, false],
                ['lock', $expressionanynot, true],
                ['dog', $expressionanynot, true],

                ['efgh', $expressionany, false],
                ['abc', $expressionany, true],
                ['lock', $expressionany, true],
                ['dog', $expressionany, false],
                // When words are shorter than 8 characters, revert to allow one spelling mistake per word.
                ['dogs are bitter than cuts', 'match_m2(dogs are better than cats)', true],
                ['digs are bitter than cuts', 'match_m2(dogs are better than cats)', true],
                ['diigs are bitter than cuts', 'match_m2(dogs are better than cats)', false],
                // Try to trip up matcher, can match first to first with two spelling mistakes
                // but then will fail when trying to match second to second which will also have two mistakes
                // but should match first word to second and second to first with 2 mistakes total.
                ['baccffff ffcdffff', 'match_m2o(abcdffff baccffff)', true],
                ['baccffff fffdffff', 'match_m2o(abcdffff baccffff)', false],
                // Similar attempt to trip up matcher as above.
                ['baccffff ffcdffff ffffffff', 'match_m2o(ffffffff abcdffff baccffff)', true],
                ['baccffff fffdffff ffffffff', 'match_m2o(ffffffff abcdffff baccffff)', false],
                // This should not match as the proximity delimiter precludes another word match occuring
                // between the two words separated by it.
                ['abcd ccc ffff', 'match_o(abcd_ffff ccc)', false],
                ['one two five', 'match(one_two|[three four] five)', true],
                ['one two five', 'match(one_two|[three four] five)', true],
                ['one four three five', 'match(one_two|[three four] five)', false],
                ['one four five three', 'match(one_two|[three four] five)', false],
                ['one four. two.', 'match_w(one_two)', false],
                ['one four two.', 'match_w(one_two)', true],
                ['one four two.', 'match_wp0(one_two)', false],
                ['one two.', 'match_wp0(one_two)', true],
                ['one two three.', 'match_wp1(one_three)', true],
                ['one three.', 'match_wp1(one_three)', true],
                ['one two two three.', 'match_wp1(one_three)', false],
                ['one two three four.', 'match_wp2(one_four)', true],
                ['one three four.', 'match_wp2(one_four)', true],
                ['one four.', 'match_wp2(one_four)', true],
                ['one two two three four.', 'match_wp2(one_four)', false],
                ['one two three four five.', 'match_wp3(one_five)', true],
                ['one three four five.', 'match_wp3(one_five)', true],
                ['one five.', 'match_wp3(one_five)', true],
                ['one two two three four five.', 'match_wp3(one_five)', false],
                ['one two three four five six.', 'match_wp4(one_six)', true],
                ['one three four five six.', 'match_wp4(one_six)', true],
                ['one five six.', 'match_wp4(one_six)', true],
                ['one two two three four five six.', 'match_wp4(one_six)', false],
                // The sentence divider can be any characters (although they should not be characters that
                // might appear in a word).
                ['one four| two|', 'match_w(one_two)', false,
                        pmatch_options::make(['sentencedividers' => '|'])],
                ['one four two|', 'match_w(one_two)', true,
                        pmatch_options::make(['sentencedividers' => '|'])],
                ['one four| two|', 'match_w(one_two)', false, $options],
                ['one four two|', 'match_w(one_two)', true, $options],
                ['one four$ two$', 'match_w(one_two)', false, $options],
                ['one four two$', 'match_w(one_two)', true, $options],
                // Notice match_c (m) will match any one word string with an 'm' in it.
                ['cegh', $expressionstr, true],
                ['acegh', $expressionstr, false],
                ['abcegh', $expressionstr, true],
                ['abceghi', $expressionstr, false],
                ['abceghm', $expressionstr, true],
                ['abceghmn', $expressionstr, false],
                ['fghij', 'match(abcde)', false],
                ['fghij', 'match(abcde)', true,
                        pmatch_options::make(['synonyms' => ['abcde' => 'xyz|fghij']])],
                // Further tests to check that phrase is matching the right no of words.
                ['it does not really contain an object which is a verb',
                        'match_mw([not contain]_verb)', false],
                ['it is not really a sentence it would be classed as a' .
                        ' phrase as it does not contain an object which would indicate who thought of the ' .
                        'good idea or a verb', 'match_m([not contain]|abc_verb)', false],
                ['not contain is a verb', 'match_mw([not contain]|abc_verb)', true],
                ['not contain is not a verb', 'match_mw([not contain]|abc_verb)', false],
                // Test full stop as word separator.
                ['one four.two.', 'match_w(one_two)', false],
                ['one four two.greeedy', 'match_w(one_two)', true],
                ['one four two.', 'match_w(one_two)', true],
                ['one two.', 'match_wp0(one_two)', true],
                ['one hello.two.', 'match_wp0(one_two)', false],
                ['one two three.', 'match_wp1(one_three)', true],
                ['one two.three.', 'match_wp1(one_three)', false],
                ['one two.three.', 'match_w(one three)', true],
                ['one two three.four five.', 'match_wp3(one_five)', false],
                ['one three.four five.', 'match_wp3(one_five)', false],
                ['one.five.', 'match_wp3(one_five)', false],
                // Problem from Redmine issue #8018.
                ['first phrase second sequence',
                        'match_mow ([first phrase]|firstphrase [second sequence]|secondsequence)', true],
                ['second sequence first phrase',
                        'match_mow ([first phrase]|firstphrase [second sequence]|secondsequence)', true],
                ['firstphrase secondsequence',
                        'match_mow ([first phrase]|firstphrase [second sequence]|secondsequence)', true],
                ['secondsequence firstphrase',
                        'match_mow ([first phrase]|firstphrase [second sequence]|secondsequence)', true],
                // Problem from Redmine issue #8948.
                ['Pb<sup>2+</sup>(aq) + 2Cl<sup>-</sup>(aq) = PbCl<sub>2</sub>(s)',
                        'match(Pb<sup>2+</sup>\(aq\) + 2Cl<sup>-</sup>\(aq\) = PbCl<sub>2</sub>\(s\))', true],
                // Character punctuation.
                ['¡¿Y tú quién te crees', 'match(¡¿Y tú quién te crees)', true],
                ['«Das Mädchen ist sehr schön»', 'match(«Das Mädchen ist sehr schön»)', true],
                ['»Ich weiß nicht was ich sagen soll «', 'match(»Ich weiß nicht was ich sagen soll «)', true],
                ['„Kommst du mit”', 'match(„Kommst du mit”)', true],
                ['« Attends, je d\'important »', 'match(« Attends, je d\'important »)', true],
                ['Test?', 'match(Test\?)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Test', 'match(Test\?)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Test.', 'match(Test\?)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Testa', 'match(Test\?)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Test?', 'match(Test?)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Test', 'match(Test?)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Test.', 'match(Test?)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Testa', 'match(Test?)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Punctuation is important.', 'match(Punctuation is important.)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Punctuation is important', 'match(Punctuation is important.)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Is punctuation important?', 'match(Is punctuation important?)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Is punctuation important?', 'match(Is punctuation important)', false, pmatch_options::make(['sentencedividers' => ''])],
                ['Punctuation is important!', 'match(Punctuation is important!)', true, pmatch_options::make(['sentencedividers' => ''])],
                ['Punctuation is important!', 'match(Punctuation is important)', false, pmatch_options::make(['sentencedividers' => ''])],
        ];
    }

    /**
     * For Test pmatch matching
     *
     * @dataProvider pmatch_matching_provider
     * @param string $string
     * @param pmatch_expression $expression
     * @param bool|null $shouldmatch is method assert.
     * @param pmatch_options|null $option is optionfor method assert.
     */
    public function test_pmatch_matching($string, $expression, $shouldmatch, $options = null) {
        if ($shouldmatch) {
            $this->assertTrue($this->match($string, $expression, $options));
        } else {
            $this->assertFalse($this->match($string, $expression, $options));
        }
    }

    /**
     * Data provider function for test_pmatch_formatting
     *
     * @return array
     */
    public function pmatch_formatting_provider() {
        return [
                ['match_all (
    match_any (
        not (
            match_cow (one_two)
        )
        match_mfw (three|[four five])
    )
    match_any (
        match_mrw (six|nine nine)
        match_m2w (seven|[eight ten])
    )
)
', 'match_all(match_any(not(match_cow(one_two))' .
                        'match_mfw(three|[four five]))' .
                        'match_any(match_mrw(six|nine nine)match_m2w(seven|[eight ten])))'],
                ["match_m (three|[four five])\n", 'match_mfmtxr(three|[four five])'],
                ["match_mow (three|[four five])\n", 'match_mfmtxrow(three|[four five])'],
                ["match_m2 (three|[four five])\n", 'match_mfmtxr2(three|[four five])'],
                ["match_mrtxow (three|[four five])\n", 'match_mtxrow(three|[four five])'],
                ['match_all (
    match_any (
        not (
            match_c (a)
        )
        match_c (b)
    )
    match_all (
        match_all (
            match_any (
                match_c (c)
                match_c (d)
            )
            match_any (
                match_c (e)
                match_c (f)
            )
            match_all (
                match_c (g)
                match_c (h)
            )
        )
        not (
            match_any (
                match_any (
                    match_c (i)
                    match_c (j)
                )
                match_any (
                    match_c (k)
                    match_c (l)
                )
                match_all (
                    match_c (m)
                    match_c (n)
                )
            )
        )
    )
)
', 'match_all(match_any(not(match_c(a))match_c(b))' .
                            'match_all(match_all(match_any(match_c(c)match_c(d))' .
                            'match_any(match_c(e)match_c(f))match_all(match_c(g)match_c(h)))' .
                            'not(match_any(match_any(match_c(i)match_c(j))' .
                            'match_any(match_c(k)match_c(l))match_all(match_c(m)match_c(n))))))'],
                        ];
    }

    /**
     * For Test pmatch formatting
     *
     * @dataProvider pmatch_formatting_provider
     * @param string $expected
     * @param $string $unformattedexpression
     */
    public function test_pmatch_formatting($expected, $unformattedexpression) {
        $expression = new pmatch_expression($unformattedexpression);
        $this->assertEquals($expected, $expression->get_formatted_expression_string());
    }

    public function pmatch_number_regex_testcases() {
        return [
            ['1.981', 1],
            ['-1.981', 1],
            ['101', 1],
            ['101x10<sup>3</sup>', 1],
            ['101x10<sup>-3</sup>', 1],
            ['101.11x10<sup>-3</sup>', 1],
            ['101.11<sup>-3</sup>', 0],
            ['101e3', 1],
            ['101e-3', 1],
            ['101.11e-3', 1],
            ['101.11x3', 0],
            ['-5*10<sup>-1</sup>', 1],

            // Spaces after unary plus/minus are not allowed.
            ['- 1.985', 0],
            ['- 5x10<sup>-1</sup>', 0],
            ['- 5X10<sup>-1</sup>', 0],
        ];
    }

    /**
     * @dataProvider pmatch_number_regex_testcases
     */
    public function test_pmatch_number_regex($string, $expectedmatches) {
        $this->assertSame($expectedmatches, preg_match('!'.PMATCH_NUMBER.'$!A', $string));
    }

    public function pmatch_number_matching_cases() {
        return [
            ['2', 'match(2)', true],
            ['1', 'match(1)', true],
            ['0', 'match(0)', true],
            ['-1', 'match(-1)', true],
            ['-2', 'match(-2)', true],

            ['1.981', 'match(1.981)', true],
            ['1.98', 'match(+1.98)', true],
            ['+1.98', 'match(+1.98)', true],
            ['+101', 'match(101)', true],
            ['- 50', 'match(- 50)', true],
            ['- 50.333', 'match(- 50)', false],

            ['- 50', 'match(- 50e0)', true],
            ['- 50', 'match(- 5e1)', true],
            ['- 50', 'match(- 5e+1)', true],
            ['-0.5', 'match(-5*10<sup>-1</sup>)', true],

            // Test matching around the limits of float accuracy.
            ['100.11', 'match(1.00109999999999e2)', true],
            ['1.23456000000001x10<sup>3</sup>', 'match(1234.56)', true],

            // Spaces after unary plus/minus are not allowed.
            ['-1.985', 'match(- 1.985)', false],
            ['- 50', 'match(-50e0)', false],
            ['-0.5', 'match(- 5e-1)', false],
            ['-0.5', 'match(- 5x10<sup>-1</sup>)', false],
            ['-0.5', 'match(- 5X10<sup>-1</sup>)', false],

            // Numbers run into the surrounding 'unit'.
            ['2ml', 'match(2ml)', true],
            ['0.21e1 ml', 'match(2.1 ml)', true],
            ['2.5ml', 'match(2.5ml)', true],
            ['2.6mm', 'match(2.6ml)', false],
            ['2', 'match(2)', true],
            ['a2', 'match(a2)', true],
            ['a2b', 'match(a2b)', true],
            ['c2d', 'match(a2b)', false],
            ['2b', 'match(2b)', true],
            ['2.4', 'match(2.4)', true],
            ['a2.4', 'match(a2.4)', true],
            ['a2.4b', 'match(a2.4b)', true],
            ['2.4b', 'match(2.4b)', true],
            ['0.24e1b', 'match(2.4b)', false],
            ['0.24e1 b', 'match(2.4 b)', true],
            ['2.6 ml', 'match(2.6|2.7 ml)', true],
            ['2.7ml', 'match(2.6ml|2.7ml)', true],
            ['2.8ml', 'match(2.6ml|2.7ml)', false],
            ['$2.9million', 'match($2.9million)', true],
            ['£2.9million', 'match(£2.9mill*)', true],
            ['$2.9millian', 'match($2.9million)', false],
            ['a1b2.3c4.5e6d7', 'match(a1b2.3c4.5e6d7)', true],
            ['a1b2.3c4.8e6d7', 'match(a1b2.3c4.5e6d7)', false],
        ];
    }

    /**
     * @dataProvider pmatch_number_matching_cases
     */
    public function test_pmatch_number_matching($string, $expression, $shouldmatch) {
        if ($shouldmatch) {
            $this->assertTrue($this->match($string, $expression));
        } else {
            $this->assertFalse($this->match($string, $expression));
        }
    }

    public function test_pmatch_unicode_matching() {
        // Unicode normalisation means that the same characters with two different
        // unicode representations should match.
        // "\xC3\x85" = 'LATIN CAPITAL LETTER A WITH RING ABOVE' (U+00C5)
        // "\xCC\x8A" = 'COMBINING RING ABOVE' (U+030A).
        $this->assertTrue($this->match("A\xCC\x8A", "match(\xC3\x85)"));
    }

    public function test_pmatch_matching_countries() {
        // This is a minimal failure in that doing any one of these things fixes it:
        // - Removing the set_synonyms call.
        // - Removing A from both the string and the pattern.
        // - Removing B from both the string and the pattern.
        $options = new pmatch_options();
        $options->set_synonyms(array(
            (object) array('word' => 'B', 'synonyms' => '*B*'),
        ));
        $this->assertTrue($this->match('A B', 'match (A B)', $options));
    }
}
