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

import Alpine from './alpinejs';
import Templates from "core/templates";
import { MultipleChoice } from "./surveyitems/multiplechoice";
import { SingleChoice } from "./surveyitems/singlechoice";
import { Text } from "./surveyitems/text";
import { Scale } from "./surveyitems/scale";
import { EmojiSurveyItem } from "./surveyitems/emoji";
import { SlotChoiceSurveyItem } from "./surveyitems/slot_choice";
import { ajaxAndHandleError } from "./util";

const surveyItemClasses = {
    'multiplechoice': MultipleChoice,
    'singlechoice': SingleChoice,
    'text': Text,
    'scalequestion': Scale,
    'emoji': EmojiSurveyItem,
    'slot_choice': SlotChoiceSurveyItem,
};

/**
 * @typedef ItemData
 * @type {Object}
 * @property {int} surveyitemid
 * @property {string} type
 */

/**
 * @typedef PageData
 * @type {Object}
 * @property {ItemData[]} items
 * @property {int} spe_id
 */

/**
 * @param {Object} data
 * @param {?number} data.courseid
 * @param {PageData[]} data.pages
 * @param {Object.<int, int>} data.default_slots
 * @returns {Object}
 */
const surveyRoot = (
    {
        courseid,
        pages,
        default_slots,
    }
) => ({
    currentPage0: 0,
    totalPages: pages.length,

    /** @type {Object.<int, int>} Currently selected slots by survey part execution id. */
    selectedSlots: structuredClone(default_slots),
    /** @type {Object.<int, string>} Currently filled values by survey item id. */
    values: {},

    /** @type {Object.<int, SurveyItem>} */
    surveyItemHandlers: {},

    canFinish: courseid && courseid > 0,

    init() {
        const page = pages[this.currentPage0];
        for (const item of page.items) {
            this.initHandlerFor(item, page);
        }
    },

    collectValuesFromCurrentPage() {
        for (const surveyItem of pages[this.currentPage0].items) {
            /** @type {SurveyItem} */
            const handler = this.surveyItemHandlers[surveyItem.surveyitemid];
            if (!handler) {
                continue;
            }

            const value = handler.getValue();
            if (value !== null) {
                this.values[surveyItem.surveyitemid] = value;
            } else {
                delete this.values[surveyItem.surveyitemid];
            }
        }
    },

    /**
     * @param {ItemData} surveyItem
     * @param {PageData} page
     */
    initHandlerFor(surveyItem, page) {
        const itemId = surveyItem.surveyitemid;
        /** @type {typeof SurveyItem} */
        const surveyItemClass = surveyItemClasses[surveyItem.type];
        if (!surveyItemClass) {
            return;
        }

        const itemRoot = this.$refs.pageContainer.querySelector(`[data-surveyitemid="${itemId}"]`);

        const alpineThis = this;
        const handler = this.surveyItemHandlers[itemId] = new surveyItemClass(surveyItem, itemRoot, {
            get slotId() {
                return alpineThis.selectedSlots[page.spe_id];
            },
            set slotId(value) {
                alpineThis.selectedSlots[page.spe_id] = value;
            }
        });

        handler.initialize();
        if (itemId in this.values) {
            handler.setValue(this.values[surveyItem.surveyitemid]);
        }
    },

    async fireBeforeNext() {
        let isPrevented = false;
        const event = {
            prevent() {
                isPrevented = true;
            }
        };

        for (const handler of Object.values(this.surveyItemHandlers)) {
            await handler.beforeNext(event);
        }
        return {isPrevented};
    },

    async changeToPage(newPageIndex) {
        // Fire beforeNext event handlers and stop if one calls prevent().
        if (newPageIndex > this.currentPage0 && (await this.fireBeforeNext()).isPrevented) {
            return;
        }

        this.collectValuesFromCurrentPage();
        this.surveyItemHandlers = {};

        // Render the new page.
        this.currentPage0 = newPageIndex;
        const newPage = pages[newPageIndex];

        const {html, js} = await Templates.renderForPromise('block_coursefeedback/survey/page', newPage);
        Templates.replaceNodeContents(this.$refs.pageContainer, html, js);

        for (const surveyItem of newPage.items) {
            this.initHandlerFor(surveyItem, newPage);
        }
    },

    async submit() {
        if (!this.canFinish) {
            return;
        }

        // Fire beforeNext event handlers and stop if one calls prevent().
        if ((await this.fireBeforeNext()).isPrevented) {
            return;
        }

        this.collectValuesFromCurrentPage();

        // Bring the values and selected slots into the format expected by the api.
        const speValues = Object.fromEntries(Object.entries(this.selectedSlots)
            .map(([speId, slotId]) => [speId, {surveypartexecutionoptionid: slotId, answers: []}]));
        for (const page of pages) {
            const spe = speValues[page.spe_id];
            if (!spe) {
                window.console.warn("No slot was selected for SPE ", page.spe_id);
                continue;
            }

            for (const item of page.items) {
                if (item.surveyitemid in this.values) {
                    spe.answers.push({
                        surveyitemid: item.surveyitemid,
                        value: JSON.stringify(this.values[item.surveyitemid]),
                    });
                }
            }
        }

        await ajaxAndHandleError({
            methodname: 'block_coursefeedback_save_survey_answers',
            args: {
                courseid,
                surveyparts: Object.values(speValues)
            }
        });

        // Replace ourselves with a thank-you message.
        const {html} = await Templates.renderForPromise('block_coursefeedback/survey/success', {});
        this.$root.outerHTML = html;
    },

    async nextPage() {
        if (this.currentPage0 + 1 < this.totalPages) {
            await this.changeToPage(this.currentPage0 + 1);
        }
    },

    async prevPage() {
        if (this.currentPage0 > 0) {
            await this.changeToPage(this.currentPage0 - 1);
        }
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('surveyRoot', surveyRoot);
});
