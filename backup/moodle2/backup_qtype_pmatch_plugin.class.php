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
 * @package   qtype_pmatch
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Provides the information to backup pmatch questions.
 *
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qtype_pmatch_plugin extends backup_qtype_plugin {

    /**
     * Returns the qtype information to attach to question element.
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'pmatch');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // This qtype uses standard question_answers, add them here
        // to the tree before any other information that will use them.
        $this->add_question_question_answers($pluginwrapper);

        // Now create the qtype own structures.
        $pmatchoptions = new backup_nested_element('pmatch', array('id'), array('forcelength',
            'usecase', 'converttospace', 'applydictionarycheck', 'extenddictionary',
            'allowsubscript', 'allowsuperscript'));

        $synonyms = new backup_nested_element('synonyms');

        $synonym = new backup_nested_element('synonym', array('id'), array('word', 'synonyms'));

        $responses = new backup_nested_element('test_responses');
        $response = new backup_nested_element('test_response', array('id'),
                array('response', 'expectedfraction', 'gradedfraction'));

        $matches = new backup_nested_element('rule_matches');
        $match = new backup_nested_element('rule_match', array('id'),
                array('answerid'));

        $pluginwrapper->add_child($pmatchoptions);
        $pluginwrapper->add_child($synonyms);
        $synonyms->add_child($synonym);
        $pluginwrapper->add_child($responses);
        $responses->add_child($response);
        // Add matches within responses.
        $response->add_child($matches);
        $matches->add_child($match);

        // Set source to populate the data.
        $pmatchoptions->set_source_table('qtype_pmatch',
                array('questionid' => backup::VAR_PARENTID));
        $synonym->set_source_table('qtype_pmatch_synonyms',
                array('questionid' => backup::VAR_PARENTID), 'id ASC');
        $response->set_source_table('qtype_pmatch_test_responses',
                array('questionid' => backup::VAR_PARENTID), 'id ASC');
        $match->set_source_table('qtype_pmatch_rule_matches',
                array('testresponseid' => backup::VAR_PARENTID));

        // Don't need to annotate ids nor files.

        return $plugin;
    }
}
