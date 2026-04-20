<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\external;

use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\output\course_event_slot_table;
use coding_exception;
use context_course;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to create and update response slots.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert_slot extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slotid' => new external_value(PARAM_INT, 'if updating, the id of the slot to update, otherwise 0', VALUE_DEFAULT, 0),
            'surveypartexecutionid' => new external_value(PARAM_INT, 'id of the survey part execution to which the slot belongs'),
            'name' => new external_value(PARAM_TEXT, 'name of the (new) slot'),
        ]);
    }

    /**
     * Returns description of method return.
     * @return external_function_parameters
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'new_table_html' => new external_value(PARAM_RAW),
        ]);
    }

    /**
     * Does the thing.
     *
     * @param int $slotid
     * @param int $surveypartexecutionid
     * @param string $name
     * @return array
     */
    public static function execute(int $slotid, int $surveypartexecutionid, string $name): array {
        global $DB;
        [
            'slotid' => $slotid, 'surveypartexecutionid' => $surveypartexecutionid, 'name' => $name
        ] = self::validate_parameters(self::execute_parameters(), [
            'slotid' => $slotid, 'surveypartexecutionid' => $surveypartexecutionid, 'name' => $name,
        ]);

        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $spe_fields = survey_part_execution::get_sql_fields('spe', 'spe_');
        $records = $DB->get_records_sql("
            SELECT $se_fields, $spe_fields
            FROM {" . survey_part_execution::TABLE . "} spe
            INNER JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
            WHERE spe.id = :surveypartexecutionid
        ", ['surveypartexecutionid' => $surveypartexecutionid]);

        if (count($records) !== 1) {
            throw new coding_exception("Survey part execution '$surveypartexecutionid' not found or ambiguous.");
        }

        $row = reset($records);
        $survey_execution = new survey_execution(record: survey_execution::extract_record($row, 'se_'));

        $courseid = $survey_execution->get('courseid');

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('block/coursefeedback:changecoursesettings', $context);

        global $DB, $OUTPUT;
        $transaction = $DB->start_delegated_transaction();

        if ($slotid) {
            $slot = response_slot::get_record(['id' => $slotid], MUST_EXIST);
            if ($slot->get('surveypartexecutionid') !== $surveypartexecutionid) {
                throw new coding_exception("Slot '$slotid' does not belong to survey part execution '$surveypartexecutionid'");
            }
        } else {
            $slot = new response_slot();
            $slot->set('surveypartexecutionid', $surveypartexecutionid);
        }

        $slot->set('name', $name);
        $slot->save();

        $transaction->allow_commit();

        $model = course_feedback_data::load_from_course($courseid);

        return [
            'new_table_html' => $OUTPUT->render(new course_event_slot_table($model)),
        ];
    }
}
