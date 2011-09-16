<?php

require_once($CFG->dirroot . '/question/type/pmatch/pmatchlib.php');

class pmatch_parse_string_test extends UnitTestCase {
    public function test_pmatch_parse_string() {
        $options = new pmatch_options();

        $parsedstring = new pmatch_parsed_string('abc.def', $options);
        $this->assertEqual($parsedstring->get_words(), array('abc.', 'def'));

        $parsedstring = new pmatch_parsed_string('abc def', $options);
        $this->assertEqual($parsedstring->get_words(), array('abc', 'def'));

        $parsedstring = new pmatch_parsed_string('abc<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(), array('abc<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(), array('123<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>?456<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(),
                                                array('123<sup>3</sup>?', '456<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>!456<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(),
                                                array('123<sup>3</sup>!', '456<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('1.23', $options);
        $this->assertEqual($parsedstring->get_words(), array('1.23'));

        $parsedstring = new pmatch_parsed_string('1.23e-10', $options);
        $this->assertEqual($parsedstring->get_words(), array('1.23e-10'));

        $parsedstring = new pmatch_parsed_string('1.23x10<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(), array('1.23x10<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('123<sup>3</sup>', $options);
        $this->assertEqual($parsedstring->get_words(), array('123<sup>3</sup>'));

        $parsedstring = new pmatch_parsed_string('cat. dog', $options);
        $this->assertEqual($parsedstring->get_words(), array('cat.', 'dog'));

        $parsedstring = new pmatch_parsed_string('cat? dog', $options);
        $this->assertEqual($parsedstring->get_words(), array('cat?', 'dog'));
    }
/*    public function test_pmatch_spelling() {

        if (!function_exists('pspell_new')) {
            throw new coding_exception('pspell not installed on your server. '.
                                            'Spell checking will not work.');
        }

        $options = new pmatch_options();
        $options->lang = 'en';
        $options->set_synonyms(array((object)array('word'=>'queek', 'synonyms' => 'abcde|fghij')));

        //e.g. passes as it is an extra dictionary word
        //tool passes as it is correctly spelt
        $parsedstring = new pmatch_parsed_string('e.g. tool', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        //full stop (sentence divider) should pass test
        $parsedstring = new pmatch_parsed_string('e.g.. tool.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        //only allow one full stop (sentence divider)
        $parsedstring = new pmatch_parsed_string('e.g... tool.', $options);
        $this->assertFalse($parsedstring->is_parseable());

        //anything in synonyms automatically passes
        $parsedstring = new pmatch_parsed_string('e.g.. tool. queek queek', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        //anything in synonyms automatically passes
        $parsedstring = new pmatch_parsed_string('e.g.. tool. abcde fghij.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        //synonyms may include * wild card
        $options = new pmatch_options();
        $options->lang = 'en';
        $options->set_synonyms(
                    array((object)array('word'=>'queek*', 'synonyms' => 'abcde|fghij')));
        $parsedstring = new pmatch_parsed_string('e.g.. tool. queeking.', $options);
        $this->assertTrue($parsedstring->is_spelt_correctly());

        $parsedstring = new pmatch_parsed_string('e.g.. tool. queenking.', $options);
        $this->assertFalse($parsedstring->is_spelt_correctly());
    }*/
}