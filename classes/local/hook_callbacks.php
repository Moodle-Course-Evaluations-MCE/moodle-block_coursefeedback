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

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\user_organization_cache_manager;
use block_coursefeedback\output\survey;
use core\hook\navigation\primary_extend;
use core\hook\output\after_standard_main_region_html_generation;
use moodle_url;

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
        if ($PAGE->context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        // TODO: Probably cache the result of this rather large query.
        $course_data = survey_execution_data::load_from_course($PAGE->course);
        if (!$course_data) {
            return;
        }

        $now = time();
        $is_active = $course_data->survey_execution->get('starttime') <= $now
            && $now < $course_data->survey_execution->get('endtime');
        if (!$is_active) {
            return;
        }

        $survey = survey::for_course($course_data);
        if ($survey->is_empty()) {
            debugging("There is an active survey, but it's empty.");
            return;
        }

        $renderer = $PAGE->get_renderer('block_coursefeedback');

        // We add the survey HTML to the end of the page, it'll move itself to the notification area.
        $hook->add_html($renderer->render($survey));
    }

    /**
     * Adds the evaluation administration overview page to evaluation admins primary navigation.
     * @param primary_extend $hook
     */
    public static function primary_extend(primary_extend $hook) {
        if (has_capability('moodle/site:config', \context_system::instance())) {
            return;
        }
        if (permission_manager::can_do_any_evaluation_administration()) {
            $hook->primaryview->add(
                get_string('evaluationadministration', 'block_coursefeedback'),
                new moodle_url('/blocks/coursefeedback/overview.php'),
            );
        } else if (user_organization_cache_manager::get_instance()->is_user_evaluation_coordinator()) {
            $hook->primaryview->add(
                get_string('evaluationadministration', 'block_coursefeedback'),
                new moodle_url('/blocks/coursefeedback/organizations.php'),
            );
        }
    }
}
