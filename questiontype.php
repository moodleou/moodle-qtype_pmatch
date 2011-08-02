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
 * Question type class for the short answer question type.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/pmatch/question.php');


/**
 * The short answer question type.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch extends question_type {
    function get_question_options($question) {
        global $DB;
        parent::get_question_options($question);
        $question->options->synonyms = $DB->get_records('qtype_pmatch_synonyms', array('questionid' => $question->id), 'id ASC');
        return true;
    }

    public function extra_question_fields() {
        return array('qtype_pmatch', 'usecase', 'allowsubscript', 'allowsuperscript',
                'forcelength', 'applydictionarycheck', 'extenddictionary', 'converttospace');
    }

    protected function questionid_column_name() {
        return 'questionid';
    }

    function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    public function save_question_options($question) {
        global $DB;
        $result = new stdClass();

        $context = $question->context;

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $maxfraction = -1;

        // Insert all the new answers
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
            if ($expression->is_valid()){
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
        }

        if (!html_is_blank($question->otherfeedback['text'])){
            $otheranswer = new stdClass();
            $otheranswer->answer = '*';
            $otheranswer->fraction = 0;
            $otheranswer->feedback = '';
            $otheranswer->question = $question->id;
            $oldotheranswer = array_shift($oldanswers);
            if (!$oldotheranswer){
                $otheranswer->id = $DB->insert_record('question_answers', $otheranswer);
            } else {
                $otheranswer->id = $oldotheranswer->id;
            }
            $otheranswer->feedback = $this->import_or_save_files($question->otherfeedback,
                    $context, 'question', 'answerfeedback', $otheranswer->id);
            $otheranswer->feedbackformat = $question->otherfeedback['format'];
            $DB->update_record('question_answers', $otheranswer);
        }


        $oldsynonyms = $DB->get_records('qtype_pmatch_synonyms',
                array('questionid' => $question->id), 'id ASC');
        // Insert all the new answers
        foreach ($question->synonymsdata as $key => $synonymfromform) {
            // Check for, and ignore, completely blank synonym from the form.
            $word = trim($synonymfromform['word']);
            if ($word == '') {
                continue;
            }

            // Update an existing answer if possible.
            $synonym = array_shift($oldsynonyms);
            if (!$synonym) {
                $synonym = new stdClass();
                $synonym->questionid = $question->id;
                $synonym->synonyms = '';
                $synonym->word = '';
                $synonym->id = $DB->insert_record('qtype_pmatch_synonyms', $synonym);
            }

            $synonym->word = $word;
            $synonym->synonyms = trim($synonymfromform['synonyms']);
            $DB->update_record('qtype_pmatch_synonyms', $synonym);

        }

        if (!isset($question->extenddictionary)) {
            $question->extenddictionary = '';
        }
        $parentresult = parent::save_question_options($question);
        if ($parentresult !== null) {
            // Parent function returns null if all is OK
            return $parentresult;
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $this->save_hints($question);

        // Perform sanity checks on fractional grades
        if ($maxfraction != 1) {
            $result->noticeyesno = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        }
    }

    public function import_from_xml($data, $question, $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'pmatch') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'pmatch';

        $question->allowsubscript = $format->trans_single(
                $format->getpath($data, array('#', 'allowsubscript', 0, '#'), 1));
        $question->allowsuperscript = $format->trans_single(
                $format->getpath($data, array('#', 'allowsuperscript', 0, '#'), 1));
        $question->forcelength = $format->trans_single(
                $format->getpath($data, array('#', 'forcelength', 0, '#'), 1));
        $question->usecase = $format->trans_single(
                $format->getpath($data, array('#', 'usecase', 0, '#'), 1));
        $question->applydictionarycheck = $format->trans_single(
                $format->getpath($data, array('#', 'applydictionarycheck', 0, '#'), 1));
        $question->converttospace = $format->import_text(
                $format->getpath($data, array('#', 'converttospace', 0, '#', 'text'), ''));
        $question->extenddictionary = $format->import_text(
                $format->getpath($data, array('#', 'extenddictionary', 0, '#', 'text'), ''));

        // Run through the answers
        $answers = $data['#']['answer'];
        $acount = 0;
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer);
            $question->answer[$acount] = $ans->answer['text'];
            $question->fraction[$acount] = $ans->fraction;
            $question->feedback[$acount] = $ans->feedback;
            ++$acount;
        }


        $format->import_hints($question, $data, true);

        $question->otherfeedback['text'] = '';

        $synonyms = $format->getpath($data, array('#', 'synonym'), false);
        if ($synonyms) {
            $this->import_synonyms($format, $question, $synonyms);
        } else {
            $question->synonymsdata =array();
        }

        return $question;
    }

    public function import_synonyms($format, &$question, $synonyms) {
        foreach ($synonyms as $synonym){
            $this->import_synonym($format, $question, $synonym);
        }
    }

    public function import_synonym($format, &$question, $synonym) {
        static $indexno = 0;
        $question->synonymsdata[$indexno]['word'] = $format->import_text($format->getpath($synonym, array('#', 'word', 0, '#', 'text'), ''));
        $question->synonymsdata[$indexno]['synonyms'] = $format->import_text($format->getpath($synonym, array('#', 'synonyms', 0, '#', 'text'), ''));
        $indexno++;
    }

    public function export_to_xml($question, $format, $extra = null) {
        $output = '';

        $output .= "    <allowsubscript>" . $format->get_single($question->options->allowsubscript) . "</allowsubscript>\n";
        $output .= "    <allowsuperscript>" . $format->get_single($question->options->allowsuperscript) . "</allowsuperscript>\n";
        $output .= "    <forcelength>" . $format->get_single($question->options->forcelength) . "</forcelength>\n";
        $output .= "    <usecase>" . $format->get_single($question->options->usecase) . "</usecase>\n";
        $output .= "    <converttospace>\n";
        $output .= $format->writetext($question->options->converttospace, 3);
        $output .= "    </converttospace>\n";
        $output .= "    <applydictionarycheck>" . $format->get_single($question->options->applydictionarycheck) . "</applydictionarycheck>\n";
        $output .= "    <extenddictionary>\n";
        $output .= $format->writetext($question->options->extenddictionary, 3);
        $output .= "    </extenddictionary>\n";

        $output .= $format->write_answers($question->options->answers);
        $output .= $this->write_synonyms($question->options->synonyms, $format);

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
        $question->pmatchoptions->extradictionarywords = preg_split('!\s+!',$questiondata->options->extenddictionary);
        $question->pmatchoptions->converttospace = $questiondata->options->converttospace;
        $question->pmatchoptions->set_synonyms($questiondata->options->synonyms);

        $question->allowsubscript = $questiondata->options->allowsubscript;
        $question->allowsuperscript = $questiondata->options->allowsuperscript;
        $question->forcelength = $questiondata->options->forcelength;
        $question->applydictionarycheck = $questiondata->options->applydictionarycheck;
        $this->initialise_question_answers($question, $questiondata);
    }

    public function get_random_guess_score($questiondata) {
        return 0;
    }

    public function get_possible_responses($questiondata) {
        $responses = array();

        foreach ($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer,
                    $answer->fraction);
        }
        $responses[null] = question_possible_response::no_response();

        return array($questiondata->id => $responses);
    }


    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_pmatch_synonyms', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

}
