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

import {SurveyItem} from "block_coursefeedback/surveyitem";

/**
 * Implement SurveyItem for Scalequestion
 *
 * @module     block_coursefeedback/surveyitems/scale
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export class Scale extends SurveyItem {

    form = this.surveyItemRootElement.querySelector('form');

    getValue() {
        return this.form.elements[`surveyitem-${this.surveyItemData.surveyitemid}`].value;
    }

    setValue(value) {
        this.form.elements[`surveyitem-${this.surveyItemData.surveyitemid}`].value = value;
    }

}
