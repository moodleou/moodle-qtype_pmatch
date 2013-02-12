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
        return $expression->matches($string);
    }

    protected function error_message($expression, $options = null) {
        $expression = new pmatch_expression($expression, $options);
        return $expression->get_parse_error();
    }

    public function test_pmatch_error() {
        // No closing bracket.
        $this->assertEquals($this->error_message('match_mow([tom maud]|[sid jane]'),
                get_string('ie_missingclosingbracket',
                'qtype_pmatch', 'match_mow([tom maud]|[sid jane]'));
        // No contents.
        $this->assertEquals($this->error_message('match_mow()'),
                get_string('ie_unrecognisedsubcontents', 'qtype_pmatch', 'match_mow()'));
        // Ends in an or character.
        $this->assertEquals($this->error_message('match_mow([tom maud]|)'),
                get_string('ie_lastsubcontenttypeorcharacter', 'qtype_pmatch', '[tom maud]|'));
        // Ends in a space.
        $this->assertEquals($this->error_message('match_mow([tom maud] )'),
                get_string('ie_lastsubcontenttypeworddelimiter',
                         'qtype_pmatch', 'match_mow([tom maud] )'));
        // Ends in a proximity delimiter.
        $this->assertEquals($this->error_message('match_mow([tom maud]_)'),
                get_string('ie_lastsubcontenttypeworddelimiter',
                         'qtype_pmatch', 'match_mow([tom maud]_)'));
    }

    public function test_pmatch_matching() {
        // Tests from the original pmatch documentation.
        // This is the exact match.
        $this->assertTrue($this->match('tom dick harry', 'match(tom dick harry)'));
        // Extra characters are allowed anywhere within the word.
        $this->assertTrue($this->match('thomas', 'match_c(tom)'));
        // Extra words are allowed anywhere within the sentence.
        $this->assertTrue($this->match('tom dick and harry', 'match_w(dick)'));
        // Any order of words is allowed.
        $this->assertTrue($this->match('harry dick tom', 'match_o(tom dick harry)'));
        // One character in the word can differ.
        $this->assertTrue($this->match('rick', 'match_m(dick)'));
        $this->assertTrue($this->match('rick and harry and tom', 'match_mow(tom dick harry)'));
        $this->assertTrue($this->match('dick and harry and thomas', 'match_cow(tom dick harry)'));
        // Any of tom or dick or harry will be matched.
        $this->assertTrue($this->match('arthur harry and sid', 'match_mow(tom|dick|harry)'));
        // The pattern requires either tom or dick AND harry or sid.
        $this->assertTrue($this->match('tomy harry and sid', 'match_mow(tom|dick harry|sid)'));
        // The pattern requires either (tom and maud) or (sid and jane).
        $this->assertTrue($this->match('tom was mesmerised by maud',
                                               'match_mow([tom maud]|[sid jane])'));
        // The first character can be anything.
        $this->assertTrue($this->match('rick', 'match(?ick)'));
        // Any sequence of characters can follow 'har'.
        $this->assertTrue($this->match('harold', 'match(har*)'));
        // Only one word is between tom and maud.
        $this->assertTrue($this->match('tom married maud sid married jane', 'match_mow(tom_maud)'));
        // The proximity control also specifies word order and over-rides the 'o' matching option.
        $this->assertFalse($this->match('maud married tom sid married jane',
                                                                        'match_mow(tom_maud)'));
        // Only two words are allowed between tom and jane.
        $this->assertFalse($this->match('tom married maud sid married jane',
                                            'match_mow(tom_jane)'));

        $this->assertTrue($this->match('married', 'match_mow(marr*)'));
        $this->assertTrue($this->match('tom married maud', 'match_mow(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('maud marries thomas', 'match_mow(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('tom is to marry maud', 'match_w(tom|thomas marr* maud)'));
        $this->assertFalse($this->match('tom is to marry maud', 'match_o(tom|thomas marr* maud)'));
        $this->assertTrue($this->match('tom is to maud marry', 'match_ow(tom|thomas marr* maud)'));
        $this->assertFalse($this->match('tom is to maud marry', 'match_w(tom|thomas marr* maud)'));
        // Two characters are missing.
        $this->assertTrue($this->match('tempratur', 'match_m2ow(temperature)'));
        // Two characters are missing.
        $this->assertFalse($this->match('tempratur', 'match_mow(temperature)'));
        // Two characters are incorrect; one has been replaced and one is missing.
        $this->assertTrue($this->match('temporatur', 'match_m2ow(temperature)'));
        // Two characters are incorrect; one has been replaced and one is missing.
        $this->assertFalse($this->match('temporatur', 'match_mow(temperature)'));
        // Three characters are incorrect; one has been replaced and two are missing.
        $this->assertFalse($this->match('tmporatur', 'match_m2ow(temperature)'));

        $this->assertTrue($this->match('cat toad frog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat newt frog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('dog', 'match([toad frog]|dog)'));
        $this->assertTrue($this->match('cat toad frog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat newt frog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat dog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertFalse($this->match('cat. dog', 'match(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('cat. dog', 'match(cat [toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat x x toad frog x',
                                                            'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat newt x x x x x frog x',
                                                            'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertTrue($this->match('x cat x x dog x', 'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertFalse($this->match('x cat x. x dog x', 'match_w(cat_[toad|newt frog]|dog)'));
        $this->assertFalse($this->match('A C B D', 'match([A B]_[C D])'));
        $this->assertFalse($this->match('B C A D', 'match_o([A B]_[C D])'));
        $this->assertTrue($this->match('A x x x x B C D', 'match_ow([A B]_[C D])'));
        $this->assertFalse($this->match('A x x x x B. C D', 'match_ow([A B]_[C D])'));
        $this->assertTrue($this->match('A x x x x B. C D', 'match_ow([A B] [C D])'));
        // Requires the words in [] to match in order.
        $this->assertFalse($this->match('B x x x x A C D', 'match_ow([A B]_[C D])'));
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
        // Fewer characters option is disabled for a pattern
        // of fewer than 4 normal characters in pattern.
        $this->assertFalse($this->match('te', 'match_mf(tes)'));

        // Allow fewer characters.
        $this->assertTrue($this->match('abcd', 'match_mf(abcd)'));
        $this->assertTrue($this->match('abc', 'match_mf(abcd)'));
        $this->assertFalse($this->match('acbd', 'match_mf(abcd)'));
        $this->assertFalse($this->match('abfd', 'match_mf(abcd)'));
        $this->assertFalse($this->match('abcf', 'match_mf(abcd)'));
        $this->assertTrue($this->match('bcd', 'match_mf(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mf(abcd)'));
        $this->assertFalse($this->match('gabcd', 'match_mf(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mf(abcd)'));

        // Allow replace character.
        $this->assertTrue($this->match('abcd', 'match_mr(abcd)'));
        $this->assertFalse($this->match('abc', 'match_mr(abcd)'));
        $this->assertFalse($this->match('acbd', 'match_mr(abcd)'));
        $this->assertTrue($this->match('abfd', 'match_mr(abcd)'));
        $this->assertTrue($this->match('abcf', 'match_mr(abcd)'));
        $this->assertTrue($this->match('fbcd', 'match_mr(abcd)'));
        $this->assertFalse($this->match('bcd', 'match_mr(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mr(abcd)'));
        $this->assertFalse($this->match('gabcd', 'match_mr(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mr(abcd)'));

        // Allow transpose characters.
        $this->assertTrue($this->match('abcd', 'match_mt(abcd)'));
        $this->assertFalse($this->match('abc', 'match_mt(abcd)'));
        $this->assertTrue($this->match('acbd', 'match_mt(abcd)'));
        $this->assertTrue($this->match('bacd', 'match_mt(abcd)'));
        $this->assertTrue($this->match('abdc', 'match_mt(abcd)'));
        $this->assertFalse($this->match('abfd', 'match_mt(abcd)'));
        $this->assertFalse($this->match('abcf', 'match_mt(abcd)'));
        $this->assertFalse($this->match('fbcd', 'match_mt(abcd)'));
        $this->assertFalse($this->match('bcd', 'match_mt(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mt(abcd)'));
        $this->assertFalse($this->match('gabcd', 'match_mt(abcd)'));
        $this->assertFalse($this->match('abcdg', 'match_mt(abcd)'));

        // Allow extra character.
        $this->assertTrue($this->match('abcd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('abc', 'match_mx(abcd)'));
        $this->assertFalse($this->match('acbd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('bacd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('abdc', 'match_mx(abcd)'));
        $this->assertFalse($this->match('abfd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('abcf', 'match_mx(abcd)'));
        $this->assertFalse($this->match('fbcd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('bcd', 'match_mx(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_mx(abcd)'));
        $this->assertTrue($this->match('gabcd', 'match_mx(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_mx(abcd)'));
        $this->assertTrue($this->match('abcd', 'match_mx(abcd)'));
        $this->assertFalse($this->match('abc', 'match_mx(abcd)'));

        // Allow any one mispelling.
        $this->assertTrue($this->match('abcd', 'match_m(abcd)'));
        $this->assertTrue($this->match('abc', 'match_m(abcd)'));
        $this->assertTrue($this->match('acbd', 'match_m(abcd)'));
        $this->assertTrue($this->match('bacd', 'match_m(abcd)'));
        $this->assertTrue($this->match('abdc', 'match_m(abcd)'));
        $this->assertTrue($this->match('abfd', 'match_m(abcd)'));
        $this->assertTrue($this->match('abcf', 'match_m(abcd)'));
        $this->assertTrue($this->match('fbcd', 'match_m(abcd)'));
        $this->assertTrue($this->match('bcd', 'match_m(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_m(abcd)'));
        $this->assertTrue($this->match('gabcd', 'match_m(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_m(abcd)'));

        $this->assertFalse($this->match('bacde', 'match_m(abcd)'));
        $this->assertFalse($this->match('badc', 'match_m(abcd)'));
        $this->assertFalse($this->match('affd', 'match_m(abcd)'));
        $this->assertFalse($this->match('fbcf', 'match_m(abcd)'));
        $this->assertFalse($this->match('ffcd', 'match_m(abcd)'));
        $this->assertFalse($this->match('bfcd', 'match_m(abcd)'));
        $this->assertFalse($this->match('abccdg', 'match_m(abcd)'));
        $this->assertFalse($this->match('gabbcd', 'match_m(abcd)'));
        $this->assertFalse($this->match('abbcdg', 'match_m(abcd)'));

        // Allow any two mispelling.
        // Default to one if there are less than 8 chars in word pattern.
        $this->assertTrue($this->match('abcd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abc', 'match_m2(abcd)'));
        $this->assertTrue($this->match('acbd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('bacd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abdc', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abfd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abcf', 'match_m2(abcd)'));
        $this->assertTrue($this->match('fbcd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('bcd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_m2(abcd)'));
        $this->assertTrue($this->match('gabcd', 'match_m2(abcd)'));
        $this->assertTrue($this->match('abcdg', 'match_m2(abcd)'));

        $this->assertFalse($this->match('bacde', 'match_m2(abcd)'));
        // The ffff padding is to increase pattern length to the required 8 chars
        // so that two misspellings are allowed.
        $this->assertTrue($this->match('ffffbacde', 'match_m2(ffffabcd)'));
        $this->assertTrue($this->match('ffffbadc', 'match_m2(ffffabcd)'));
        $this->assertTrue($this->match('ffffaffd', 'match_m2(ffffabcd)'));
        $this->assertTrue($this->match('fffffbcf', 'match_m2(ffffabcd)'));
        $this->assertTrue($this->match('ffffffcd', 'match_m2(ffffabcd)'));
        $this->assertTrue($this->match('bfcdffff', 'match_m2(abcdffff)'));
        $this->assertTrue($this->match('abccdgffff', 'match_m2(abcdffff)'));
        $this->assertTrue($this->match('gabbcdffff', 'match_m2(abcdffff)'));
        $this->assertTrue($this->match('abbcdgffff', 'match_m2(abcdffff)'));

        $this->assertTrue($this->match('', 'match(*)'));

        $options = new pmatch_options();
        $options->ignorecase = true;
        $this->assertTrue($this->match('ABCD', 'match(abcd)', $options));
        $this->assertTrue($this->match('Mary had a little LamB',
                                          'match(mary had a little lamb)', $options));
        $options->ignorecase = false;
        $this->assertFalse($this->match('ABCD', 'match(abcd)', $options));

        $this->assertTrue($this->match('efgh', 'not ( match_c(c) )'));
        $this->assertFalse($this->match('abc', 'not ( match_c(c) )'));
        $this->assertFalse($this->match('lock', 'not ( match_c(c) )'));
        $this->assertTrue($this->match('dog', 'not ( match_c(c) )'));

        $expression = <<<EOF
match_all(
    not ( match_c(a))
    not ( match_c(b))
    not ( match_c(c))
)
EOF;
        $this->assertTrue($this->match('efgh', $expression));
        $this->assertFalse($this->match('abc', $expression));
        $this->assertFalse($this->match('lock', $expression));
        $this->assertTrue($this->match('dog', $expression));

        $expression = <<<EOF
match_any(
    not ( match_c(a))
    not ( match_c(b))
    not ( match_c(c))
)
EOF;
        $this->assertTrue($this->match('efgh', $expression));
        $this->assertFalse($this->match('abc', $expression));
        $this->assertTrue($this->match('lock', $expression));
        $this->assertTrue($this->match('dog', $expression));

        $expression = <<<EOF
match_any(
    match_c(a)
    match_c(b)
    match_c(c)
)
EOF;
        $this->assertFalse($this->match('efgh', $expression));
        $this->assertTrue($this->match('abc', $expression));
        $this->assertTrue($this->match('lock', $expression));
        $this->assertFalse($this->match('dog', $expression));

        // When words are shorter than 8 characters, revert to allow one spelling mistake per word.
        $this->assertTrue($this->match('dogs are bitter than cuts',
                                                        'match_m2(dogs are better than cats)'));
        $this->assertTrue($this->match('digs are bitter than cuts',
                                                        'match_m2(dogs are better than cats)'));
        $this->assertFalse($this->match('diigs are bitter than cuts',
                                                        'match_m2(dogs are better than cats)'));

        // Try to trip up matcher, can match first to first with two spelling mistakes
        // but then will fail when trying to match second to second which will also have two mistakes
        // but should match first word to second and second to first with 2 mistakes total.
        $this->assertTrue($this->match('baccffff ffcdffff', 'match_m2o(abcdffff baccffff)'));
        $this->assertFalse($this->match('baccffff fffdffff', 'match_m2o(abcdffff baccffff)'));

        // Similar attempt to trip up matcher as above.
        $this->assertTrue($this->match('baccffff ffcdffff ffffffff',
                                        'match_m2o(ffffffff abcdffff baccffff)'));
        $this->assertFalse($this->match('baccffff fffdffff ffffffff',
                                        'match_m2o(ffffffff abcdffff baccffff)'));

        // This should not match as the proximity delimiter precludes another word match occuring
        // between the two words separated by it.
        $this->assertFalse($this->match('abcd ccc ffff', 'match_o(abcd_ffff ccc)'));

        $this->assertTrue($this->match('one two five', 'match(one_two|[three four] five)'));
        $this->assertTrue($this->match('one two five', 'match(one_two|[three four] five)'));
        $this->assertFalse($this->match('one four three five', 'match(one_two|[three four] five)'));
        $this->assertFalse($this->match('one four five three', 'match(one_two|[three four] five)'));

        $this->assertFalse($this->match('one four. two.', 'match_w(one_two)'));
        $this->assertTrue($this->match('one four two.', 'match_w(one_two)'));

        $this->assertFalse($this->match('one four two.', 'match_wp0(one_two)'));
        $this->assertTrue($this->match('one two.', 'match_wp0(one_two)'));

        $this->assertTrue($this->match('one two three.', 'match_wp1(one_three)'));
        $this->assertTrue($this->match('one three.', 'match_wp1(one_three)'));
        $this->assertFalse($this->match('one two two three.', 'match_wp1(one_three)'));

        $this->assertTrue($this->match('one two three four.', 'match_wp2(one_four)'));
        $this->assertTrue($this->match('one three four.', 'match_wp2(one_four)'));
        $this->assertTrue($this->match('one four.', 'match_wp2(one_four)'));
        $this->assertFalse($this->match('one two two three four.', 'match_wp2(one_four)'));

        $this->assertTrue($this->match('one two three four five.', 'match_wp3(one_five)'));
        $this->assertTrue($this->match('one three four five.', 'match_wp3(one_five)'));
        $this->assertTrue($this->match('one five.', 'match_wp3(one_five)'));
        $this->assertFalse($this->match('one two two three four five.', 'match_wp3(one_five)'));

        $this->assertTrue($this->match('one two three four five six.', 'match_wp4(one_six)'));
        $this->assertTrue($this->match('one three four five six.', 'match_wp4(one_six)'));
        $this->assertTrue($this->match('one five six.', 'match_wp4(one_six)'));
        $this->assertFalse($this->match('one two two three four five six.', 'match_wp4(one_six)'));

        // The sentence divider can be any characters (although they should not be characters that
        // might appear in a word).
        $options = new pmatch_options();
        $options->sentencedividers = '|';
        $this->assertFalse($this->match('one four| two|', 'match_w(one_two)', $options));
        $this->assertTrue($this->match('one four two|', 'match_w(one_two)', $options));
        $options = new pmatch_options();
        $options->sentencedividers = '|$';
        $this->assertFalse($this->match('one four| two|', 'match_w(one_two)', $options));
        $this->assertTrue($this->match('one four two|', 'match_w(one_two)', $options));
        $this->assertFalse($this->match('one four$ two$', 'match_w(one_two)', $options));
        $this->assertTrue($this->match('one four two$', 'match_w(one_two)', $options));

        $expression = new pmatch_expression('match_all(match_any(not(match_cow(one_two))'.
                'match_mfw(three|[four five]))'.
                'match_any(match_mrw(six|nine nine)match_m2w(seven|[eight ten])))');
        $formattedexpression = <<<EOF
match_all (
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

EOF;
        $this->assertEquals($expression->get_formatted_expression_string(), $formattedexpression);
        // When formatting phrase and word level options in expression they are simplied
        // and arranged into a standard order.
        $expression = new pmatch_expression('match_mfmtxr(three|[four five])');
        $this->assertEquals($expression->get_formatted_expression_string(),
                                                                "match_m (three|[four five])\n");
        $expression = new pmatch_expression('match_mfmtxrow(three|[four five])');
        $this->assertEquals($expression->get_formatted_expression_string(),
                                                                "match_mow (three|[four five])\n");
        $expression = new pmatch_expression('match_mfmtxr2(three|[four five])');
        $this->assertEquals($expression->get_formatted_expression_string(),
                                                                "match_m2 (three|[four five])\n");
        $expression = new pmatch_expression('match_mtxrow(three|[four five])');
        $this->assertEquals($expression->get_formatted_expression_string(),
                                                            "match_mrtxow (three|[four five])\n");
        $expression = new pmatch_expression('match_all(match_any(not(match_cow(one_two))'.
                        'match_mfw(three|[four five]))match_any(match_mrw(six|nine nine)'.
                        'match_m2w(seven|[eight ten])))');

        $expressionstr = 'match_all(match_any(not(match_c(a))match_c(b))'.
                        'match_all(match_all(match_any(match_c(c)match_c(d))'.
                        'match_any(match_c(e)match_c(f))match_all(match_c(g)match_c(h)))'.
                        'not(match_any(match_any(match_c(i)match_c(j))'.
                        'match_any(match_c(k)match_c(l))match_all(match_c(m)match_c(n))))))';
        $expression = new pmatch_expression($expressionstr);
        $formattedexpression = <<<EOF
match_all (
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

EOF;

        $this->assertEquals($expression->get_formatted_expression_string(), $formattedexpression);
        // Notice match_c (m) will match any one word string with an 'm' in it.
        $this->assertTrue($this->match('cegh', $expressionstr));
        $this->assertFalse($this->match('acegh', $expressionstr));
        $this->assertTrue($this->match('abcegh', $expressionstr));
        $this->assertFalse($this->match('abceghi', $expressionstr));
        $this->assertTrue($this->match('abceghm', $expressionstr));
        $this->assertFalse($this->match('abceghmn', $expressionstr));

        $options = new pmatch_options();
        $this->assertFalse($this->match('fghij', 'match(abcde)', $options));
        $options->set_synonyms(array((object)array('word'=>'abcde', 'synonyms' => 'xyz|fghij')));
        $this->assertTrue($this->match('fghij', 'match(abcde)', $options));

        // Further tests to check that phrase is matching the right no of words.
        $this->assertFalse($this->match('it does not really contain an object which is a verb',
                                                                'match_mw([not contain]_verb)'));
        $this->assertFalse($this->match('it is not really a sentence it would be classed as a'.
                ' phrase as it does not contain an object which would indicate who thought of the '.
                'good idea or a verb', 'match_m([not contain]|abc_verb)'));
        $this->assertTrue($this->match('not contain is a verb',
                                                        'match_mw([not contain]|abc_verb)'));
        $this->assertFalse($this->match('not contain is not a verb',
                                                        'match_mw([not contain]|abc_verb)'));

        // Test full stop as word separator.
        $this->assertFalse($this->match('one four.two.', 'match_w(one_two)'));
        $this->assertTrue($this->match('one four two.greeedy', 'match_w(one_two)'));
        $this->assertTrue($this->match('one four two.', 'match_w(one_two)'));

        $this->assertTrue($this->match('one two.', 'match_wp0(one_two)'));
        $this->assertFalse($this->match('one hello.two.', 'match_wp0(one_two)'));

        $this->assertTrue($this->match('one two three.', 'match_wp1(one_three)'));
        $this->assertFalse($this->match('one two.three.', 'match_wp1(one_three)'));
        $this->assertTrue($this->match('one two.three.', 'match_w(one three)'));

        $this->assertFalse($this->match('one two three.four five.', 'match_wp3(one_five)'));
        $this->assertFalse($this->match('one three.four five.', 'match_wp3(one_five)'));
        $this->assertFalse($this->match('one.five.', 'match_wp3(one_five)'));
    }

    public function test_pmatch_number_matching() {
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '1.981'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '-1.981'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '- 1.985'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101x10<sup>3</sup>'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101x10<sup>-3</sup>'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101.11x10<sup>-3</sup>'));
        $this->assertTrue(0===preg_match('!'.PMATCH_NUMBER.'$!A', '101.11<sup>-3</sup>'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101e3'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101e-3'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '101.11e-3'));
        $this->assertTrue(0===preg_match('!'.PMATCH_NUMBER.'$!A', '101.11x3'));

        $this->assertTrue($this->match('1.981', 'match(1.981)'));
        $this->assertTrue($this->match('-1.985', 'match(- 1.985)'));
        $this->assertTrue($this->match('1.98', 'match(+1.98)'));
        $this->assertTrue($this->match('+1.98', 'match(+1.98)'));
        $this->assertTrue($this->match('+101', 'match(101)'));
        $this->assertTrue($this->match('- 50', 'match(- 50)'));
        $this->assertFalse($this->match('- 50.333', 'match(- 50)'));

        $this->assertTrue($this->match('- 50', 'match(-50e0)'));
        $this->assertTrue($this->match('- 50', 'match(- 50e0)'));
        $this->assertTrue($this->match('- 50', 'match(- 5e1)'));
        $this->assertTrue($this->match('- 50', 'match(- 5e+1)'));
        $this->assertTrue($this->match('-0.5', 'match(- 5e-1)'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '-5*10<sup>-1</sup>'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '- 5x10<sup>-1</sup>'));
        $this->assertTrue(1===preg_match('!'.PMATCH_NUMBER.'$!A', '- 5X10<sup>-1</sup>'));
        $this->assertTrue($this->match('-0.5', 'match(-5*10<sup>-1</sup>)'));
        $this->assertTrue($this->match('-0.5', 'match(- 5x10<sup>-1</sup>)'));
        $this->assertTrue($this->match('-0.5', 'match(- 5X10<sup>-1</sup>)'));

        $this->assertTrue($this->match('100.11', 'match(1.001099e2)'));
        $this->assertTrue($this->match('1.234561x10<sup>3</sup>', 'match(1234.56)'));

        $this->assertTrue($this->match('10.011<sup>3</sup>', 'match(10 011<sup>3</sup>)'));
        $this->assertTrue($this->match('a.011<sup>3</sup>', 'match(a 011<sup>3</sup>)'));
    }
    public function test_pmatch_unicode_matching() {
        // Unicode normalisation means that the same characters with two different
        // unicode representations should match.
        // "\xC3\x85" = 'LATIN CAPITAL LETTER A WITH RING ABOVE' (U+00C5)
        // "\xCC\x8A" = 'COMBINING RING ABOVE' (U+030A).
        $this->assertTrue($this->match("A\xCC\x8A", "match(\xC3\x85)"));
    }
}
