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

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\output\course_event_slot_table;
use context_course;
use core\exception\coding_exception;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to delete a response slot in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_slot extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'slotid' => new external_value(PARAM_INT, 'id of the slot to delete'),
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
     * @return array
     */
    public static function execute(int $courseid, int $slotid): array {
        global $DB, $OUTPUT;

        [ 'courseid' => $courseid, 'slotid' => $slotid ] =
            self::validate_parameters(self::execute_parameters(), [ 'courseid' => $courseid, 'slotid' => $slotid ]);

        $context = context_course::instance($courseid);
        self::validate_context($context);
        $course = get_course($courseid);
        permission_manager::require_edit_course_surveysettings($course, null);

        $transaction = $DB->start_delegated_transaction();

        $recordset = $DB->get_recordset_sql("
            SELECT se.courseid, rs.id AS rs_id, rsu.id as rsu_id
            FROM {" . response_slot::TABLE . "} rs
            JOIN {" . survey_part_execution::TABLE . "} spe ON spe.id = rs.surveypartexecutionid
            JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} rsu ON rs.id = rsu.surveypartexecutionoptionid
            WHERE rs.id = :slotid
        ", ['slotid' => $slotid]);

        try {
            $records = iterator_to_array($recordset, preserve_keys: false);
        } finally {
            $recordset->close();
        }

        if (!$records) {
            debugging("Tried to delete nonexistent slot '$slotid'");
        } else {
            if (reset($records)->courseid != $courseid) {
                throw new coding_exception("Slot '$slotid' does not belong to the course '$courseid'");
            }

            $rsu_ids = array_filter(array_unique(array_column($records, 'rsu_id')));

            if ($rsu_ids) {
                $DB->delete_records_list(response_slot_user::TABLE, 'id', $rsu_ids);
            }
            $DB->delete_records(response_slot::TABLE, ['id' => $slotid]);
        }

        $transaction->allow_commit();

        $model = survey_execution_data::load_from_course_required($course);

        return [
            'new_table_html' => $OUTPUT->render(new course_event_slot_table($model, $course)),
        ];
    }
}
