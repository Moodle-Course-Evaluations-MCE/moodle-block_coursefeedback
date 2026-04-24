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
 * @typedef SurveyContext
 * @type {object}
 * @property {int} slotId
 */

/**
 * Implement SurveyItem for Multiplechoice
 *
 * @module     block_coursefeedback/surveyitem
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export class SurveyItem {
    /** @var {any} */
    surveyItemData;
    /** @var {Element} */
    surveyItemRootElement;
    /** @var {SurveyContext} */
    surveyContext;

    constructor(surveyItemData, surveyItemRootElement, surveyContext) {
        this.surveyItemData = surveyItemData;
        this.surveyItemRootElement = surveyItemRootElement;
        this.surveyContext = surveyContext;
    }

    /**
     * This method is called immediately after the item's template has been rendered and displayed.
     */
    initialize() {
        // To overwrite.
    }

    /**
     * This method is called before the user leaves the page containing this item when going forward, including before submission.
     *
     * It is not called when going back or when advancing to the page this item is on.
     *
     * @param {Object} args
     * @param {SurveyItem~preventNext} args.prevent
     *
     * @callback SurveyItem~preventNext
     */
    // eslint-disable-next-line no-unused-vars
    async beforeNext({prevent}) {
        // To overwrite.
    }

    /**
     * @param {any} value
     * @return void
     */
    // eslint-disable-next-line no-unused-vars
    setValue(value) {
        throw new Error("Not implemented");
    }

    /**
     * @return any
     */
    getValue() {
        throw new Error('Not implemented');
    }

}
