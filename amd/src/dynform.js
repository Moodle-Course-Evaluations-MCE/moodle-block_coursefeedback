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
 * Manager for the coursefeedback blocks rankingpage (ranking.php).
 *
 * @module      block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import jQuery from 'jquery';
import * as Str from 'core/str';

/**
 * reset table
 *
 */
function resetTable() {
    jQuery('#coursefeedback_table').find('tbody').html('');
}

/**
 * Used for the ranking page
 * Initialise
 */
export const init = () => {

    let downQuest = jQuery('#id_downloadqu');
    let downFeed = jQuery('#id_downloadfb');
    let downFeedEssay = jQuery('#id_downloadfbessay');

    // Initially hide the downloadbuttons ($mform->hideIF unfortunately doesn't work properly in this case)
    downQuest.hide();
    downFeed.hide();
    downFeedEssay.hide();

    // Eventlistener for changing the selected feedback.
    jQuery('#id_feedback').change(function () {
        let selectedFeedback = jQuery('#id_feedback').val();

        // Check if a valid fb is selected
        if (selectedFeedback == '0') {
            downFeed.hide();
            downFeedEssay.hide();
        } else {
            downFeed.show();
            downFeedEssay.show();
        }

        // Get fb questions
        let promises = Ajax.call([{
            methodname: 'block_coursefeedback_get_feedback_questions',
            args: {feedbackid: selectedFeedback}
        }]);
        promises[0].done(function (data) {
            // Populate question options.
            window.console.debug("AJAX FEEDBACK DONE");
            jQuery('#id_question').html('');
            let chooseStr = Str.get_string('form_option_choose', 'block_coursefeedback');
            chooseStr.done(function (string) {
                jQuery('<option/>').val('0').html(string).appendTo('#id_question');
                data.questions.forEach(function (quest) {
                    window.console.debug(quest);
                    jQuery('<option/>').val(quest.questionid).html(quest.question).appendTo('#id_question');
                });
            });

            // Hide and reset downloadqu button and reset table
            jQuery('#id_question').val('0');
            downQuest.hide();
            resetTable();

            return;
        }).fail(function (ex) {
            window.console.debug("ajax fAIL");
            window.console.debug(ex);
        });
        window.console.debug(selectedFeedback);
    });

    // Eventlistener for changing the selected question.
    jQuery('#id_question').change(function() {
        let selectedQuestion = jQuery('#id_question').val();
        let selectedFb = jQuery('#id_feedback').val();
        // Check if a valid question is selected
        if (selectedQuestion == '0') {
            downQuest.hide();
            resetTable();
            return;
        } else {
            downQuest.show();
        }

        let answerLimit = 0;
        let showPerPage = 200;
        let page = 1;
        // Get rankings.
        let promises = Ajax.call([{
            methodname: 'block_coursefeedback_get_ranking_for_question',
            args: {
                questionid: selectedQuestion,
                feedback: selectedFb,
                answerlimit: answerLimit,
                showperpage: showPerPage,
                page: page
            }
        }]);
        promises[0].done(function (questiondata) {
            // Reset rankingtable.
            resetTable();
            let table = window.document.getElementById('coursefeedback_table');
            let tBody = table.getElementsByTagName('tbody')[0];
            // Populate rankingtable.
            questiondata.ranking.forEach(function(course) {
                let row = tBody.insertRow();
                var cell1 = row.insertCell(0);
                var cell2 = row.insertCell(1);
                var cell3 = row.insertCell(2);
                var cell4 = row.insertCell(3);
                cell1.innerHTML = course.shortname;
                cell2.innerHTML = course.courseid;
                cell3.innerHTML = course.adjanswerstotal;
                cell4.innerHTML = course.avfeedbackresult;
            });

            return;
        }).fail(function (ex) {
            window.console.debug(ex);
        });
    });

    // Don't ask if user really wants to leave the page
    window.onbeforeunload = null;
};
