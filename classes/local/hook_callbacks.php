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
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
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
        global $DB, $PAGE;
        if ($PAGE->context->contextlevel === CONTEXT_COURSE) {
            // TODO lookup whether to use a survey, and if so, which one.
            $records = $DB->get_records_sql(
                'SELECT speo.id as surveypartexecutionoptionid, se.id as surveyexecutionid, ' .
                'spe.id as surveypartexecutionid, spe.surveypartid ' .
                'FROM {block_coursefeedback_surveyexecution} se ' .
                'JOIN {block_coursefeedback_surveypartexecution} spe ON se.id = spe.surveyexecutionid ' .
                'JOIN {block_coursefeedback_surveypartexecutionoption} speo ON spe.id = speo.surveypartexecutionid ' .
                'WHERE se.courseid = :courseid ',
                ['courseid' => $PAGE->course->id]
            );

            if (!$records) {
                return;
            }

            $record = array_pop($records);

            $surveypart = surveypart::get_record(['id' => $record->surveypartid]);
            if (!$surveypart) {
                return;
            }

            $templatedata = surveyitem_manager::get_templatedata_for_surveyparts([$surveypart], current_language());

            $surveydata = [
                [
                    'surveypartexecutionoptionid' => $record->surveypartexecutionoptionid,
                    'pages' => reset($templatedata),
                ],
            ];

            $PAGE->requires->js_call_amd(
                'block_coursefeedback/do_survey',
                'doSurvey',
                [$surveydata, $PAGE->course->id, 'user-notifications', true]
            );
        }
    }


    /**
     * Adds the evaluation administration overview page to evaluation admins primary navigation.
     * @param primary_extend $hook
     */
    public static function primary_extend(primary_extend $hook) {
        if (
            permission_manager::can_do_any_evaluation_administration() &&
            !has_capability('moodle/site:config', \context_system::instance())
        ) {
            $hook->primaryview->add(
                get_string('evaluationadministration', 'block_coursefeedback'),
                new moodle_url('/blocks/coursefeedback/overview.php'),
            );
        }
    }
}
