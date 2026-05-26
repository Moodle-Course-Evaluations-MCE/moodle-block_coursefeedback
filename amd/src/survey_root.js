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
import { DropdownList } from "./surveyitems/dropdown";

const surveyItemClasses = {
    'multiplechoice': MultipleChoice,
    'singlechoice': SingleChoice,
    'dropdown': DropdownList,
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
 * The current (as of the last ` collectValuesFromCurrentPage ` call) values by SPE id and survey item id.
 * @typedef SavedSpes
 * @type {Object.<number, Object<number, any>>}
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
    selectedSlots: {...default_slots},
    /** @type {SavedSpes} Currently filled values by survey item id. */
    values: {},

    /** @type {Object.<int, SurveyItem>} */
    surveyItemHandlers: {},

    canFinish: courseid && courseid > 0,

    isSubmitting: false,

    init() {
        void Templates.prefetchTemplates([
            'block_coursefeedback/survey/page', 'block_coursefeedback/survey/success',
            'block_coursefeedback/surveyitems/emoji', 'block_coursefeedback/surveyitems/multiplechoice',
            'block_coursefeedback/surveyitems/scalequestion', 'block_coursefeedback/surveyitems/singlechoice',
            'block_coursefeedback/surveyitems/slot_choice', 'block_coursefeedback/surveyitems/text',
            'block_coursefeedback/surveyitems/dropdown',
        ]);

        const page = pages[this.currentPage0];
        for (const item of page?.items ?? []) {
            this.initHandlerFor(item, page);
        }
    },

    collectValuesFromCurrentPage() {
        const page = pages[this.currentPage0];
        for (const surveyItem of page.items) {
            /** @type {SurveyItem} */
            const handler = this.surveyItemHandlers[surveyItem.surveyitemid];
            if (!handler) {
                continue;
            }

            const value = handler.getValue();
            if (value !== null) {
                if (!(page.spe_id in this.values)) {
                    this.values[page.spe_id] = {};
                }
                this.values[page.spe_id][surveyItem.surveyitemid] = value;
            } else if (page.spe_id in this.values) {
                delete this.values[page.spe_id][surveyItem.surveyitemid];
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
        if (page.spe_id in this.values && itemId in this.values[page.spe_id]) {
            // If value isn't primitive, it will be wrapped by a reactive proxy, which we need to unwrap first.
            const value = Alpine.raw(this.values[page.spe_id][surveyItem.surveyitemid]);
            handler.setValue(value);
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
        const apiValues = [];
        for (const [speId, speValues] of Object.entries(this.values)) {
            const slotId = this.selectedSlots[speId] ?? null;
            if (!slotId) {
                window.console.warn("No slot was selected for SPE ", speId);
                continue;
            }

            let apiSpeValues = apiValues.find(value => value.surveypartexecutionoptionid === slotId);
            if (!apiSpeValues) {
                apiSpeValues = {surveypartexecutionoptionid: slotId, answers: []};
                apiValues.push(apiSpeValues);
            }

            for (const [surveyItemId, value] of Object.entries(speValues)) {
                apiSpeValues.answers.push({
                    surveyitemid: parseInt(surveyItemId),
                    value: JSON.stringify(value),
                });
            }
        }

        this.isSubmitting = true;
        try {
            await ajaxAndHandleError({
                methodname: 'block_coursefeedback_save_survey_answers',
                args: {
                    courseid,
                    surveyparts: apiValues
                }
            });
        } finally {
            this.isSubmitting = false;
        }

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
