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

import Templates from "core/templates";
import Notification from 'core/notification';
import {MultipleChoice} from "block_coursefeedback/surveyitems/multiplechoice";
import {SingleChoice} from "block_coursefeedback/surveyitems/singlechoice";
import {Text} from "block_coursefeedback/surveyitems/text";
import {Scale} from "block_coursefeedback/surveyitems/scale";
import {getStrings} from 'core/str';
import Ajax from 'core/ajax';

/**
 * Shows and submits a survey.
 *
 * @module     block_coursefeedback/do_survey
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const surveyItemClasses = {
    'multiplechoice': MultipleChoice,
    'singlechoice': SingleChoice,
    'text': Text,
    'scalequestion': Scale,
};

/**
 *
 * @param {object[]} surveyItems
 * @param {Element} questionRoot
 * @param {object} values
 */
function createSurveyItemHandlers(surveyItems, questionRoot, values = {}) {
    const surveyItemHandlers = {};
    for (let surveyItem of surveyItems) {
        const surveyitemid = surveyItem.surveyitemid;
        const surveyItemClass = surveyItemClasses[surveyItem.type];
        if (surveyItemClass) {
            const element = questionRoot.querySelector(`[data-surveyitemid="${surveyitemid}"]`);
            surveyItemHandlers[surveyitemid] = new surveyItemClass(surveyItem, element);
            if (values[surveyitemid]) {
                surveyItemHandlers[surveyitemid].setValue(values[surveyitemid]);
            }
        }
    }
    return surveyItemHandlers;
}


/**
 * Shows the survey popup.
 * @param {object} surveys
 * @param {int} courseId
 * @returns {Promise<void>}
 */
export async function doSurvey(surveys, courseId) {
    const userNotificationsEl = document.getElementById('user-notifications');

    const survey = surveys[0];
    const surveyPartExecutionOptionId = survey.surveypartexecutionoptionid;
    const pages = survey.pages;

    let currentPage = 0;
    const values = {};
    let surveyItemHandlers = [];
    const amountPages = pages.length;
    const {html, js} = await Templates.renderForPromise('block_coursefeedback/show_survey', {
        'amount_pages': amountPages,
        'questions': pages[0],
    });
    Templates.prependNodeContents(userNotificationsEl, html, js);
    const surveyRoot = document.getElementById('block_coursefeedback-survey-root');
    const questionRoot = surveyRoot.querySelector('#block_coursefeedback-survey-content');
    const backButton = surveyRoot.querySelector('.button-back');
    const nextButton = surveyRoot.querySelector('.button-next');

    surveyItemHandlers = createSurveyItemHandlers(pages[0], questionRoot);

    const [nextStr, finishStr] = await getStrings([
        {
            key: 'next',
        }, {
            key: 'finish',
            component: 'block_coursefeedback',
        }
    ]);

    /**
     * Refreshes the text of the next / finish button.
     */
    function setNextButtonString() {
        nextButton.textContent = currentPage === amountPages - 1 ? finishStr : nextStr;
    }
    setNextButtonString();

    /**
     * Changes the page by saving current values and loading another page.
     * @param {int} delta Either 1 for forwards or -1 for backwards.
     * @returns {Promise<void>}
     */
    async function changePage(delta) {
        for (let surveyItem of pages[currentPage] || []) {
            if (surveyItemHandlers[surveyItem.surveyitemid]) {
                values[surveyItem.surveyitemid] = surveyItemHandlers[surveyItem.surveyitemid].getValue();
            }
        }

        currentPage += delta;
        if (currentPage >= amountPages) {

            const answers = [];
            for (let surveyItemId in values) {
                if (values[surveyItemId] !== null) {
                    answers.push({
                        surveyitemid: surveyItemId,
                        value: JSON.stringify(values[surveyItemId]),
                    });
                }
            }

            try {
                await Ajax.call([{
                    methodname: 'block_coursefeedback_save_survey_answers',
                    args: {
                        courseid: courseId,
                        surveyparts: [{
                            surveypartexecutionoptionid: surveyPartExecutionOptionId,
                            answers,
                        }]
                    }
                }]);
            } catch (e) {
                void Notification.exception(e);
            }
            return;
        }

        const {html, js} = await Templates.renderForPromise(
            'block_coursefeedback/survey_questions',
            {'questions': pages[currentPage]},
        );
        Templates.replaceNodeContents(questionRoot, html, js);
        surveyItemHandlers = createSurveyItemHandlers(pages[currentPage], questionRoot, values);
        surveyRoot.style.setProperty('--current-page', currentPage + 1);
        surveyRoot.style.setProperty('--current-page-text', `'${currentPage + 1}'`);
        backButton.disabled = currentPage === 0;
        setNextButtonString();
    }

    backButton.addEventListener('click', () => changePage(-1));
    nextButton.addEventListener('click', () => changePage(1));
}

