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
 * Question type class for the pattern-match question type.
 *
 * @package   qtype_pmatch
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/pmatch/question.php');


/**
 * The pattern-match question type.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch extends question_type {

    public function get_extra_question_bank_actions(stdClass $question): array {
        $actions = parent::get_extra_question_bank_actions($question);

        if (question_has_capability_on($question, 'view')) {
            $actions[] = new \action_menu_link_secondary(
                    new moodle_url('/question/type/pmatch/testquestion.php', ['id' => $question->id]),
                    new \pix_icon('t/approve', ''),
                    get_string('testquestiontool', 'qtype_pmatch'));
        }

        return $actions;
    }

    public function get_question_options($question) {
        global $DB;
        parent::get_question_options($question);
        $question->options->synonyms = $DB->get_records('qtype_pmatch_synonyms',
                                                        array('questionid' => $question->id),
                                                        'id ASC');
        return true;
    }

    public function extra_question_fields() {
        return array('qtype_pmatch', 'usecase', 'allowsubscript', 'allowsuperscript',
                'forcelength', 'applydictionarycheck', 'extenddictionary', 'sentencedividers', 'converttospace', 'modelanswer');
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_question_options($questionform) {
        global $DB;

        $oldsynonyms = $DB->get_records('qtype_pmatch_synonyms',
                array('questionid' => $questionform->id), 'id ASC');

        foreach ($questionform->synonymsdata as $key => $synonymfromform) {
            // Check for, and ignore, completely blank synonym from the form.
            $word = trim($synonymfromform['word']);
            if ($word == '') {
                continue;
            }

            // Update an existing answer if possible.
            $synonym = array_shift($oldsynonyms);
            if (!$synonym) {
                $synonym = new stdClass();
                $synonym->questionid = $questionform->id;
                $synonym->synonyms = '';
                $synonym->word = '';
                $synonym->id = $DB->insert_record('qtype_pmatch_synonyms', $synonym);
            }

            $synonym->word = $word;
            $synonym->synonyms = trim($synonymfromform['synonyms']);
            $DB->update_record('qtype_pmatch_synonyms', $synonym);

        }

        // Delete any remaining synonyms.
        foreach ($oldsynonyms as $oldsynonym) {
            $DB->delete_records('qtype_pmatch_synonyms', array('id' => $oldsynonym->id));
        }

        if (!isset($questionform->extenddictionary)) {
            $questionform->extenddictionary = '';
        }
        $parentresult = parent::save_question_options($questionform);

        if ($parentresult !== null) {
            // Parent function returns null if all is OK.
            return $parentresult;
        }

        $this->save_hints($questionform);

        $savedanswersresult = $this->save_answers($questionform);

        // If the data include exemplar test cases then add them to database.
        if (isset($questionform->responsesdata)) {
            $responses = $questionform->responsesdata;
            foreach ($responses as $response) {
                $response->questionid = $questionform->id;
            }
            \qtype_pmatch\testquestion_responses::add_responses($responses);
        }

        $this->save_rule_matches($questionform);

        return $savedanswersresult;
    }

    protected function save_rule_matches($question) {
        // Purge this question from the cache.
        question_bank::notify_question_edited($question->id);
        $questionobj = question_bank::load_question($question->id);
        // If there are test responses grade them with the new answers and record matches.
        \qtype_pmatch\testquestion_responses::grade_responses_and_save_matches($questionobj);
    }

    protected function save_answers($question) {
        global $DB;
        $oldanswers = $DB->get_records('question_answers',
                                            array('question' => $question->id), 'id ASC');

        $context = $question->context;
        $maxfraction = -1;

        // Insert all the new answers.
        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ignore, completely blank answer from the form.
            if (trim($answerdata) == '' && $question->fraction[$key] == 0 &&
                    html_is_blank($question->feedback[$key]['text'])) {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer = trim($answerdata);
            $expression = new pmatch_expression($answer->answer);
            if ($expression->is_valid()) {
                $answer->answer = $expression->get_formatted_expression_string();
            }

            $answer->fraction = $question->fraction[$key];
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];
            $DB->update_record('question_answers', $answer);

            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
            $this->save_extra_answer_data($question, $key, $answer->id);
        }

        if (isset($question->otherfeedback) && !html_is_blank($question->otherfeedback['text'])) {
            $otheranswer = new stdClass();
            $otheranswer->answer = '*';
            $otheranswer->fraction = 0;
            $otheranswer->feedback = '';
            $otheranswer->question = $question->id;
            $oldotheranswer = array_shift($oldanswers);
            if (!$oldotheranswer) {
                $otheranswer->id = $DB->insert_record('question_answers', $otheranswer);
            } else {
                $otheranswer->id = $oldotheranswer->id;
            }
            $otheranswer->feedback = $this->import_or_save_files($question->otherfeedback,
                    $context, 'question', 'answerfeedback', $otheranswer->id);
            $otheranswer->feedbackformat = $question->otherfeedback['format'];
            $DB->update_record('question_answers', $otheranswer);
            $this->save_extra_answer_data($question, 'other', $otheranswer->id);
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        // Perform sanity checks on fractional grades.
        if ($maxfraction != 1) {
            $result = new stdClass();
            $result->noticeyesno = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        } else {
            return null;
        }
    }

    public function save_extra_answer_data($question, $key, $answerid) {
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $question = parent::import_from_xml($data, $question, $format, $extra);
        if (!$question) {
            return false;
        }

        $synonyms = $format->getpath($data, array('#', 'synonym'), false);
        if ($synonyms) {
            $this->import_synonyms($format, $question, $synonyms);
        } else {
            $question->synonymsdata = array();
        }

        $testquestionresponses = $format->getpath($data, array('#', 'testquestionresponse'), false);
        if ($testquestionresponses) {
            $this->import_responses($format, $question, $testquestionresponses);
        } else {
            $question->responsesdata = [];
        }

        $format->import_hints($question, $data, true, false,
                $format->get_format($question->questiontextformat));
        return $question;
    }

    /**
     * Helper method used by {@link import_from_xml()}. Handle the data for test question responses text.
     *
     * @param qformat_xml $format the importer/exporter object.
     * @param object $question the question.
     * @param array $testquestionresponses the bit of the XML representing test question responses data.
     */
    public function import_responses($format, &$question, $testquestionresponses) {
        $responses = [];
        foreach ($testquestionresponses as $testquestionresponse) {
            $response = $this->get_response_data($format, $testquestionresponse);
            $responses[] = $response;
        }
        $question->responsesdata = $responses;
    }

    /**
     * Get response data.
     *
     * @param qformat_xml $format the importer/exporter object.
     * @param array $testquestionresponse the bit of the XML representing one test question response.
     * @return testquestion_response $response A simple object representing one test response.
     */
    public function get_response_data($format, $testquestionresponse) {
        $response = new \qtype_pmatch\testquestion_response();
        $response->response = $format->import_text($format->getpath($testquestionresponse, ['#', 'response', 0, '#', 'text'], ''));
        $response->expectedfraction = $format->import_text($format->getpath($testquestionresponse,
                ['#', 'expectedfraction', 0, '#', 'text'], ''));
        $response->gradedfraction = $format->import_text($format->getpath($testquestionresponse,
                ['#', 'gradedfraction', 0, '#', 'text'], ''));
        return $response;
    }

    public function import_synonyms($format, &$question, $synonyms) {
        foreach ($synonyms as $synonym) {
            $this->import_synonym($format, $question, $synonym);
        }
    }

    public function import_synonym($format, &$question, $synonym) {
        static $indexno = 0;
        $question->synonymsdata[$indexno]['word'] =
                    $format->import_text($format->getpath($synonym,
                                                            array('#', 'word', 0, '#', 'text'),
                                                            ''));
        $question->synonymsdata[$indexno]['synonyms'] =
                    $format->import_text($format->getpath($synonym,
                                                            array('#', 'synonyms', 0, '#', 'text'),
                                                            ''));
        $indexno++;
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = parent::export_to_xml($question, $format, $extra);

        $output .= $this->write_synonyms($question->options->synonyms, $format);

        $output .= $this->write_testquestion_responses($question, $format);
        return $output;
    }

    /**
     * Helper method used by {@link export_to_xml()}.
     *
     * @param object $question the question.
     * @param qformat_xml $format the importer/exporter object.
     * @return string $output XML fragment.
     */
    protected function write_testquestion_responses($question, $format) {
        $responses = \qtype_pmatch\testquestion_responses::get_responses_by_questionid($question->id);
        if (empty($responses)) {
            return '';
        }
        $output = '';
        foreach ($responses as $response) {
            $output .= $this->write_testquestion_response($response, $format);
        }
        return $output;
    }

    /**
     * Write XML fragment for one test question response.
     * @param object $response The test question response.
     * @param qformat_xml $format the importer/exporter object.
     * @return string $output XML fragment.
     */
    protected function write_testquestion_response($response, $format) {
        $output = '';
        $output .= "    <testquestionresponse>\n";
        $output .= "      <response>\n";
        $output .= $format->writetext($response->response, 4);
        $output .= "      </response>\n";
        $output .= "      <expectedfraction>\n";
        $output .= $format->writetext($response->expectedfraction, 4);
        $output .= "      </expectedfraction>\n";
        $output .= "      <gradedfraction>\n";
        $output .= $format->writetext($response->gradedfraction, 4);
        $output .= "      </gradedfraction>\n";
        $output .= "    </testquestionresponse>\n";
        return $output;
    }

    protected function write_synonyms($synonyms, $format) {
        if (empty($synonyms)) {
            return '';
        }
        $output = '';
        foreach ($synonyms as $synonym) {
            $output .= $this->write_synonym($synonym, $format);
        }
        return $output;
    }

    protected function write_synonym($synonym, $format) {
        $output = '';
        $output .= "    <synonym>\n";
        $output .= "      <word>\n";
        $output .= $format->writetext($synonym->word, 4);
        $output .= "      </word>\n";
        $output .= "      <synonyms>\n";
        $output .= $format->writetext($synonym->synonyms, 4);
        $output .= "      </synonyms>\n";
        $output .= "    </synonym>\n";
        return $output;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->pmatchoptions = new pmatch_options();
        $question->pmatchoptions->ignorecase = !$questiondata->options->usecase;
        $question->pmatchoptions->set_extra_dictionary_words(
                                                        $questiondata->options->extenddictionary);
        $question->pmatchoptions->sentencedividers = $questiondata->options->sentencedividers;
        $question->pmatchoptions->converttospace = $questiondata->options->converttospace;
        $question->pmatchoptions->set_synonyms($questiondata->options->synonyms);

        $question->allowsubscript = $questiondata->options->allowsubscript;
        $question->allowsuperscript = $questiondata->options->allowsuperscript;
        $question->forcelength = $questiondata->options->forcelength;
        $question->applydictionarycheck = $questiondata->options->applydictionarycheck;
        $question->modelanswer = $questiondata->options->modelanswer;
        $this->initialise_question_answers($question, $questiondata);
    }

    public function get_random_guess_score($questiondata) {
        return 0;
    }

    public function get_possible_responses($questiondata) {
        $responses = array();

        $starfound = false;
        foreach ($questiondata->options->answers as $aid => $answer) {
            if ($answer->answer === '*') {
                $starfound = true;
            }
            $responses[$aid] = new question_possible_response($answer->answer,
                    $answer->fraction);
        }
        if (!$starfound) {
            $responses[0] = new question_possible_response(get_string('didnotmatchanyanswer', 'question'), 0);
        }

        $responses[null] = question_possible_response::no_response();

        return array($questiondata->id => $responses);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_pmatch_synonyms', array('questionid' => $questionid));
        $DB->delete_records('qtype_pmatch_rule_matches', array('questionid' => $questionid));
        $DB->delete_records('qtype_pmatch_test_responses', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }
}
