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
 * This class provides functionality for the rule creation assistant.
 *
 * This is based on the work of Dr Alistair Willis published:
 * http://aclweb.org/anthology/W/W15/W15-0628.pdf
 *
 * @module    qtype_pmatch
 * @class     rulecreator
 * @package   question
 * @copyright 2016 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     2.9
 */
define(['jquery'], function($) {

    /**
     * @alias qtype_pmatch/rulecreator
     */
    var t = {
        store: {},

        /**
         * Initialise the rule creation assistant.
         */
        init: function() {
            $('textarea[name^="answer"]').each(function() {
                var id = $(this).attr('id').replace('id_answer_', '');
                var ref = 'id_' + id;
                t.store[ref] = [];
                // Hide the 'show/hide rule creator assistant' unless there is no existing rule.
                // It would be much better to not show this element unless js is enabled, but
                // using a static element means we cannot add classes to enable this.
                if ($(this).val() !== '') {
                    $(this).parent().parent().next().addClass('rc-hidden');
                } else {
                    $(this).parent().parent().next().addClass('rcw');
                }
                var rc = $(this).parent().parent().next().find('div.rule-creator');
                // Add ids to make things easier, and add button clicks.
                rc.attr('id', 'rc_' + id);
                $(this).parent().parent().next().find('a.rule-creator-btn').attr('id', 'rc_btn_' + id);
                rc.find('div[class="rc-notice"]').attr('id', 'rc_notice_' + id);
                rc.find('label[for="term"]').attr('for', 'rc_term_' + id);
                rc.find('input[name="term"]').attr('id', 'rc_term_' + id);
                rc.find('input[name="termadd"]').attr('id', 'rc_termadd_' + id).click(function() {
                    t.termAdd(id);
                    return false;
                });
                rc.find('input[name="termexclude"]').attr('id', 'rc_termexclude_' + id).click(function() {
                    t.termExclude(id);
                    return false;
                });
                rc.find('input[name="termor"]').attr('id', 'rc_termor_' + id).click(function() {
                    t.termOr(id);
                    return false;
                });
                rc.find('label[for="template"]').attr('for', 'rc_template_' + id);
                rc.find('input[name="template"]').attr('id', 'rc_template_' + id);
                rc.find('input[name="templateadd"]').attr('id', 'rc_templateadd_' + id).click(function() {
                    t.templateAdd(id);
                    return false;
                });
                rc.find('input[name="templateexclude"]').attr('id', 'rc_templateexclude_' + id).click(function() {
                    t.templateExclude(id);
                    return false;
                });
                rc.find('label[for="precedesadd"]').attr('for', 'rc_precedes1_' + id);
                rc.find('select[name="precedes1"]').attr('id', 'rc_precedes1_' + id);
                rc.find('select[name="precedes2"]').attr('id', 'rc_precedes2_' + id);
                rc.find('input[name="precedesadd"]').attr('id', 'rc_precedesadd_' + id).click(function() {
                    t.precedesAdd(id);
                    return false;
                });
                rc.find('label[for="cprecedesadd"]').attr('for', 'rc_cprecedes1_' + id);
                rc.find('select[name="cprecedes1"]').attr('id', 'rc_cprecedes1_' + id);
                rc.find('select[name="cprecedes2"]').attr('id', 'rc_cprecedes2_' + id);
                rc.find('input[name="cprecedesadd"]').attr('id', 'rc_cprecedesadd_' + id).click(function() {
                    t.cprecedesAdd(id);
                    return false;
                });
                rc.find('div.rc-result').attr('id', 'rc_result_' + id);
                rc.find('input[name="add"]').attr('id', 'rc_add_' + id).click(function() {
                    t.addToAnswer(id);
                    return false;
                });
                rc.find('input[name="clear"]').attr('id', 'rc_clear_' + id).click(function() {
                    t.clear(id);
                    return false;
                });
            });
            $('.rule-creator-btn').click(function(e) {
                $(e.target).parent().parent().parent().find('div.rule-creator').slideToggle();
                var src = $(e.target).find('img.icon').attr('src');
                if (src.indexOf('collapsed') > 0) {
                    src = src.slice(0, -9) + 'expanded';
                } else {
                    src = src.slice(0, -8) + 'collapsed';
                }
                $(e.target).find('img.icon').attr('src', src);
                return false;
            });
        },

        termAdd: function(id) {
            var term = this.getTerm(id, 'term');
            if (!term) {
                $('#rc_term_' + id).focus();
                return;
            }
            var termid = this.addToStore(id, term, 'and', 'term');
            this.addToPrecedes(id, termid, term);
            this.displayResult(id);
            $('#rc_term_' + id).val('');
        },

        termExclude: function(id) {
            var term = this.getTerm(id, 'term');
            if (!term) {
                $('#rc_term_' + id).focus();
                return;
            }
            this.addToStore(id, term, 'not', 'term');
            this.disablePrecedes(id, true);
            this.displayResult(id);
            $('#rc_term_' + id).val('');
        },

        termOr: function(id) {
            var term = this.getTerm(id, 'term');
            if (!term) {
                $('#rc_term_' + id).focus();
                return;
            }
            var termid = this.addToStore(id, term, 'or', 'term');
            this.addToPrecedes(id, termid, term);
            this.displayResult(id);
            $('#rc_term_' + id).val('');
        },

        templateAdd: function(id) {
            var term = this.getTerm(id, 'template');
            if (!term) {
                $('#rc_template_' + id).focus();
                return;
            }
            var termid = this.addToStore(id, term, 'and', 'template');
            this.addToPrecedes(id, termid, term);
            this.displayResult(id);
            $('#rc_template_' + id).val('');
        },

        templateExclude: function(id) {
            var term = this.getTerm(id, 'template');
            if (!term) {
                $('#rc_template_' + id).focus();
                return;
            }
            this.addToStore(id, term, 'not', 'template');
            this.disablePrecedes(id, true);
            this.displayResult(id);
            $('#rc_template_' + id).val('');
        },

        precedesAdd: function(id) {
            var terms = this.getPrecedesChoices(id, 'precedes');
            if (!terms) {
                return;
            }
            this.addToStore(id, terms, 'and', 'precedes');
            this.removeFromPrecedes(id, terms);
            this.displayResult(id);
        },

        cprecedesAdd: function(id) {
            var terms = this.getPrecedesChoices(id, 'cprecedes');
            if (!terms) {
                return;
            }
            this.addToStore(id, terms, 'and', 'cprecedes');
            this.removeFromPrecedes(id, terms);
            this.displayResult(id);
        },

        addToAnswer: function(id) {
            var result = $('#rc_result_' + id).text();
            if (result === null || result === '') {
                return;
            }
            $('#id_answer_' + id).val(result);
            this.clear();
            $('#rc_btn_' + id).click();
            $('#id_fraction_' + id).val('1.0').change();
            $('#id_answer_' + id).focus();
        },

        clear: function(id) {
            var ref = 'id_' + id;
            this.store[ref] = [];
            $('#rc_term_' + id).val('');
            $('#rc_template_' + id).val('');
            $('#rc_result_' + id).text('');
            this.resetPrecedes(id);
        },

        getTerm: function(id, type) {
            // Amati limits the number of terms to a max of 6.
            if (this.getStoreLength(id) > 4) {
                $('#rc_notice_' + id).text(M.util.get_string('rulecreationtoomanyterms', 'qtype_pmatch'));
                return false;
            }
            var term = $('#rc_' + type + '_' + id).val();
            if (term === undefined || term === null || term === '') {
                return false;
            }
            term = term.trim();
            if (term === '') {
                return false;
            }
            // Amati allows single character words, but only one word as a term or template.
            if (term.indexOf(' ') > -1) {
                return false;
            }
            // Note Pmatch relies on an underscore for closely precedes, so these cannot be included
            // without escaping (\_). Also applies to | [] ? and * (* is except for templates).
            // Amati ignores apostrophies, numbers, special and extended characters etc.
            // It seems to only work with [a-z][A-Z].
            // Special care with the presence of a \ may also be required.
            if (term.indexOf('_') > -1) {
                term = term.replace(/_/g, '\\_');
            }
            if (type === 'term') {
                return term;
            } else {
                if (term.slice(-1) === '*') {
                    return term;
                } else {
                    return term + '*';
                }
            }
        },

        getPrecedesChoices: function(id, type) {
            var term1 = $('#rc_' + type + '1_' + id).val();
            if (term1 === '0') {
                $('#rc_' + type + '1_' + id).focus();
                return false;
            }
            var term2 = $('#rc_' + type + '2_' + id).val();
            if (term2 === '0') {
                $('#rc_' + type + '2_' + id).focus();
                return false;
            }
            if (term1 === term2) {
                $('#rc_' + type + '2_' + id).focus();
                return false;
            }
            return [term1, term2];
        },

        addToPrecedes: function(id, termid, term) {
            $('#rc_precedes1_' + id).append($('<option>', {value: termid}).text(term));
            $('#rc_precedes2_' + id).append($('<option>', {value: termid}).text(term));
            $('#rc_cprecedes1_' + id).append($('<option>', {value: termid}).text(term));
            $('#rc_cprecedes2_' + id).append($('<option>', {value: termid}).text(term));
        },

        disablePrecedes: function(id, type) {
            $('#rc_precedes1_' + id).prop('disabled', type);
            $('#rc_precedes2_' + id).prop('disabled', type);
            $('#rc_cprecedes1_' + id).prop('disabled', type);
            $('#rc_cprecedes2_' + id).prop('disabled', type);
        },

        resetPrecedes: function(id) {
            this.disablePrecedes(id, false);
            $('#rc_precedes1_' + id).find('option[value!="0"]').remove();
            $('#rc_precedes2_' + id).find('option[value!="0"]').remove();
            $('#rc_cprecedes1_' + id).find('option[value!="0"]').remove();
            $('#rc_cprecedes2_' + id).find('option[value!="0"]').remove();
        },

        removeFromPrecedes: function(id, terms) {
            var i;
            for (i=0; i<2; i++) {
                $('#rc_precedes1_' + id + ' option[value="' + terms[i] + '"]').remove();
                $('#rc_precedes2_' + id + ' option[value="' + terms[i] + '"]').remove();
                $('#rc_cprecedes1_' + id + ' option[value="' + terms[i] + '"]').remove();
                $('#rc_cprecedes2_' + id + ' option[value="' + terms[i] + '"]').remove();
            }
        },

        addToStore: function(id, term, op, type) {
            var ref = 'id_' + id;
            var termid = this.store[ref].length + 1;
            this.store[ref].push({termid:termid, term:term, op:op, type:type});
            return termid;
        },

        getStoreLength: function(id) {
            var ref = 'id_' + id;
            return this.store[ref].length;
        },

        getStoredResult: function(id) {
            var ref = 'id_' + id;
            var rule = '';
            // Temporary store of rule elements.
            var temp = [];
            // Clone the bit of the store we are interested in, so we can change elements.
            var mystore = this.store[ref].slice(0);
            var num = mystore.length;
            var i = 0;
            var first = 0;
            var second = 0;
            var orpos = [];
            var orcount = 0;
            if (num === 0) {
                return rule;
            }
            for (i = 0; i < num; i++) {
                var currentterm = '';
                if (mystore[i].type === 'term') {
                    if (mystore[i].op === 'and') {
                        currentterm = 'match_w(' + mystore[i].term + ')';
                    }
                    if (mystore[i].op === 'or') {
                        currentterm = 'match_w(' + mystore[i].term + ')';
                        if (i > 0) {
                            // The first item is not really an 'or'.
                            orpos.push(i);
                        }
                    }
                    if (mystore[i].op === 'not') {
                        currentterm = 'not(match_w(' + mystore[i].term + '))';
                    }
                }
                if (mystore[i].type === 'template') {
                    if (mystore[i].op === 'and') {
                        currentterm = 'match_wm(' + mystore[i].term + ')';
                    }
                    if (mystore[i].op === 'not') {
                        currentterm = 'match_wm(' + mystore[i].term + ')';
                    }
                }
                if (mystore[i].type === 'precedes') {
                    first = mystore[i].term[0] - 1;
                    second = mystore[i].term[1] - 1;
                    currentterm = ' match_w(' + mystore[first].term + ' ' + mystore[second].term + ')';
                }
                if (mystore[i].type === 'cprecedes') {
                    first = mystore[i].term[0] - 1;
                    second = mystore[i].term[1] - 1;
                    currentterm = ' match_w(' + mystore[first].term + '_' + mystore[second].term + ')';
                }
                temp.push(currentterm);
            }
            num = temp.length;
            // Simplest scenarios first.
            if (num === 0) {
                return '';
            }
            if (num === 1) {
                return temp[0];
            }
            orcount = orpos.length;
            if (orcount === 0) {
                // For term, template, precedes or closely precedes and press 'add' or 'exclude'.
                // So no 'or' pressed, e.g. a add, b add. (Type a in term, press 'add', then ...)
                rule = 'match_all(\n  ' + temp[0];
                for (i = 1; i < num; i++) {
                    rule = rule + ' ' + temp[i];
                }
                rule = rule + '\n)';
                return rule;
            }
            if (num === (orcount + 1)) {
                // For a or, b or; a add, b or, c or.
                rule = 'match_any(\n  ' + temp[0];
                for (i = 1; i < num; i++) {
                    rule = rule + ' ' + temp[i];
                }
                rule = rule + '\n)';
                return rule;
            }
            // And the more tricky scenarios.
            if (orcount === 1) {
                if (orpos[0] === 1) { // Note orpos[0] can never be 0).
                    if (num === 2) {
                        // For a add, b or.
                        rule = 'match_any(\n  ' + temp[0] + ' ' + temp[1] + '\n)';
                    } else {
                        // For a add, b or, c add.
                        rule = 'match_all(\n  match_any(\n    ' + temp[0] + ' ' + temp[1] + '\n  )\n ';
                        for (i = 2; i < num; i++) {
                            rule = rule + ' ' + temp[i];
                        }
                        rule = rule + '\n)';
                    }
                } else {
                    // For a add, b add, c or.
                    rule = 'match_all(\n    ' + temp[0];
                    for (i = 1; i < orpos[0]; i++) {
                        rule = rule + ' ' + temp[i];
                    }
                    rule = 'match_any(\n  ' + rule + '\n  )\n  ' + temp[orpos[0]] + '\n)';
                    if (num > (orpos[0] + 1)) {
                        // For a add, b add, c or, d add.
                        rule = 'match_all(\n' + rule + '\n';
                        for (i = orpos[0] + 1; i < num; i++) {
                            rule = rule + ' ' + temp[i];
                        }
                        rule = rule + '\n)';
                    }
                }
            } else if (orcount === 2) {
                if (orpos[0] === 1) {
                    rule = 'match_any(\n  ' + temp[0] + ' ' + temp[1];
                    if (orpos[1] === 2) {
                        // For a add, b or, c or, d add.
                        rule = 'match_all(\n' + rule + ' ' + temp[2] + '\n)\n';
                        for (i = 3; i < num; i++) {
                            rule = rule + ' ' + temp[i];
                        }
                        rule = rule + '\n)';
                    } else {
                        // For a add, b or, c add, d or.
                        rule = 'match_all(\n' + rule + '\n)\n';
                        for (i = 2; i < orpos[1]; i++) {
                            rule = rule + ' ' + temp[i];
                        }
                        rule = 'match_any(\n' + rule + '\n)\n ' + temp[orpos[1]];
                        if (num > (orpos[1] + 1)) {
                            // For a add, b or, c add, d or, e add.
                            rule = '\nmatch_all(\n' + rule;
                            for (i = orpos[1] + 1; i < num; i++) {
                                rule = rule + ' ' + temp[i];
                            }
                            rule = rule + '\n)';
                        }
                        rule = rule + '\n)';
                    }
                } else {
                    // For a add, b add, c or, d or/add.
                    rule = 'match_all(\n' + temp[0];
                    for (i = 1; i < orpos[0]; i++) {
                        rule = rule + ' ' + temp[i];
                    }
                    // For a add, b add, c or.
                    rule = 'match_any(\n' + rule + '\n)\n  ' + temp[orpos[0]];
                    if (orpos[1] === orpos[0] + 1) {
                        // For a add, b add, c or, d or.
                        rule = rule + ' ' + temp[orpos[1]] + '\n)\n';
                        if (num > (orpos[1] + 1)) {
                            // For a add, b add, c or, d or, e add.
                            rule = 'match_all(\n' + rule;
                            for (i = orpos[1] + 1; i < num; i++) {
                                rule = rule + ' ' + temp[i];
                            }
                            rule = rule + '\n)';
                        }
                    } else {
                        rule = 'match_all(\n' + rule + '\n)\n';
                        for (i = orpos[0] + 1; i < orpos[1]; i++) {
                            rule = rule + ' ' + temp[i];
                        }
                        // For a add, b add, c or, d add, e or.
                        rule = 'match_any(\n' + rule + '\n)\n' + temp[orpos[1]] + '\n)\n';
                        if (num > (orpos[1] + 1)) {
                            // For a add, b add, c or, d add, e or, f add.
                            rule = 'match_all(\n' + rule;
                            for (i = orpos[1] + 1; i < num; i++) {
                                rule = rule + ' ' + temp[i];
                            }
                            rule = rule + '\n)\n';
                        }
                    }
                }
            } else {
                // Currently this simple interface cannot cope with more than two 'or's,
                // though adding all or's will work.
                $('#rc_notice_' + id).text(M.util.get_string('rulecreationtoomanyors', 'qtype_pmatch'));
                return '';
            }
            return rule.trim();
        },

        displayResult: function(id) {
            var result = this.getStoredResult(id);
            $('#rc_result_' + id).text(result);
        }
    };

    return t;
});
