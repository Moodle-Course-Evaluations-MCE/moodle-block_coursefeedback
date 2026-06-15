/*
 * This file is part of Moodle - https://questionpy.org
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */

import Alpine from './alpinejs-lazy';
import Ajax from 'core/ajax';

document.addEventListener('alpine:init', () => {
    Alpine.data('surveyExecutionPeriod', (
        {
            survey_execution_id: surveyExecutionId,
            starttime: {iso: initialStarttime},
            endtime: {iso: initialEndtime},
            default_starttime: {iso: defaultStarttime},
            default_endtime: {iso: defaultEndtime},
        }
    ) => {
        return {
            surveyExecutionId,
            starttime: initialStarttime,
            endtime: initialEndtime,

            defaultStarttime,
            defaultEndtime,

            editing: false,
            error: null,

            get isDefault() {
                return this.starttime === this.defaultStarttime && this.endtime === this.defaultEndtime;
            },

            async save() {
                try {
                    const result = await Ajax.call([{
                        methodname: 'block_coursefeedback_update_survey_execution',
                        args: {
                            surveyexecutionid: this.surveyExecutionId,
                            starttime: this.starttime,
                            endtime: this.endtime,
                        }
                    }])[0];

                    this.editing = false;
                    this.$root.outerHTML = result.html;
                } catch (error) {
                    this.error = error.message;
                }
            },

            cancel() {
                this.starttime = initialStarttime;
                this.endtime = initialEndtime;
                this.editing = false;
                this.error = null;
            },

            resetToDefault() {
                this.editing = true;
                this.starttime = defaultStarttime;
                this.endtime = defaultEndtime;
                // If the initial values aren't the default, focus the submit button to hint that the user needs to submit.
                if (defaultStarttime !== initialStarttime || defaultEndtime !== initialEndtime) {
                    this.$nextTick(() => {
                        this.$refs.submitButton.focus();
                    });
                }
            }
        };
    });
});