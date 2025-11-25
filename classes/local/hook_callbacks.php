<?php
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

namespace block_coursefeedback\local;

use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\hook\output\after_standard_main_region_html_generation;

/**
 * Place for hook callbacks.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Hook callback after_standard_main_region_html_generation.
     * Calls JS to show survey to user.
     * @param after_standard_main_region_html_generation $hook
     */
    public static function after_standard_main_region_html_generation(after_standard_main_region_html_generation $hook) {
        global $PAGE;
        if ($PAGE->context->contextlevel === CONTEXT_COURSE) {
            // TODO lookup whether to use a survey, and if so, which one.
            $surveypart = surveypart::get_record();
            $templatedata = surveyitem_manager::get_templatedata_for_surveypart(
                $surveypart,
                language_manager::get_default_language_for_surveypart($surveypart->get('id'))
            );
            $PAGE->requires->js_call_amd('block_coursefeedback/do_survey', 'doSurvey', [$templatedata]);
        }
    }
}
