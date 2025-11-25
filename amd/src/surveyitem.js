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

    constructor(surveyItemData, surveyItemRootElement) {
        this.surveyItemData = surveyItemData;
        this.surveyItemRootElement = surveyItemRootElement;
        this.initialize();
    }

    initialize() {
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
