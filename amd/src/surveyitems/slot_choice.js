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

import { SurveyItem } from "block_coursefeedback/surveyitem";

/**
 * Implement SurveyItem for the slot choice item.
 *
 * @module     block_coursefeedback/surveyitems/slot_choice
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class SlotChoiceSurveyItem extends SurveyItem {

    /** @type HTMLFormElement */
    form = this.surveyItemRootElement.querySelector('form');

    inputName = `surveyitem-${this.surveyItemData.surveyitemid}`;

    initialize() {
        if (this.inputName in this.form.elements) {
            this.form.elements[this.inputName].value = this.surveyContext.slotId;
        }

        this.form.addEventListener('change', (e) => {
            this.surveyContext.slotId = parseInt(e.target.value);
        });
    }

    async beforeNext({prevent}) {
        // The slot choice is the one time we want to force users to give an answer.
        if (!this.form.checkValidity()) {
            this.form.reportValidity();
            prevent();
        }
    }

    getValue() {
        return null;
    }

    setValue() {
    }
}
