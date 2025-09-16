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

import * as Ajax from 'core/ajax';
import Templates from "core/templates";

/**
 * Shows and submits a survey.
 *
 * @module     block_coursefeedback/do-survey
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize the survey doing.
 * @param {number} surveyPartId
 */
export function init(surveyPartId) {

}

export async function doSurvey() {
    const userNotificationsEl = document.getElementById('user-notifications');
    const {html, js} = await Templates.renderForPromise('block_coursefeedback/show_survey', {});
    Templates.prependNodeContents(userNotificationsEl, html, js);
}

