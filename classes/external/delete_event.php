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

use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\output\course_event_slot_table;
use coding_exception;
use context_course;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_recordset;

/**
 * External api to delete teaching events in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_event extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'eventid' => new external_value(PARAM_INT, 'id of the event to delete'),
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
     * From the recordset extracts the IDs of the SPEs, slots, and slot users that are part of the event.
     *
     * @param moodle_recordset $recordset
     * @return array
     */
    private static function extract_courseid_and_ids_to_delete(moodle_recordset $recordset): array {
        $spe_ids = [];
        $rs_ids = [];
        $rsu_ids = [];

        foreach ($recordset as $row) {
            if ($row->spe_id) {
                $spe_ids[$row->spe_id] = null;
            }
            if ($row->rs_id) {
                $rs_ids[$row->rs_id] = null;
            }
            if ($row->rsu_id) {
                $rsu_ids[$row->rsu_id] = null;
            }
        }

        return [array_keys($spe_ids), array_keys($rs_ids), array_keys($rsu_ids)];
    }

    /**
     * Does the thing.
     *
     * @param int $courseid
     * @param int $eventid
     * @return array
     */
    public static function execute(int $courseid, int $eventid): array {
        global $DB, $OUTPUT;

        [ 'courseid' => $courseid, 'eventid' => $eventid ] =
            self::validate_parameters(self::execute_parameters(), [ 'courseid' => $courseid, 'eventid' => $eventid ]);

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('block/coursefeedback:changecoursesettings', $context);

        $transaction = $DB->start_delegated_transaction();

        $recordset = $DB->get_recordset_sql("
            SELECT te.courseid, spe.id AS spe_id, rs.id AS rs_id, rsu.id as rsu_id
            FROM {" . teaching_event::TABLE . "} te
            LEFT JOIN {" . survey_part_execution::TABLE . "} spe ON spe.eventid = te.id
            LEFT JOIN {" . response_slot::TABLE . "} rs ON spe.id = rs.surveypartexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} rsu ON rs.id = rsu.surveypartexecutionoptionid
            WHERE te.id = :eventid
        ", ['eventid' => $eventid]);

        try {
            if (!$recordset || !$recordset->valid()) {
                debugging("Tried to delete nonexistent event '$eventid'");
                $spe_ids = [];
                $rs_ids = [];
                $rsu_ids = [];
            } else {
                $first_row = $recordset->current();
                if ($first_row->courseid != $courseid) {
                    throw new coding_exception("Event '$eventid' does not belong to the course '$courseid'");
                }

                [$spe_ids, $rs_ids, $rsu_ids] = self::extract_courseid_and_ids_to_delete($recordset);
            }
        } finally {
            $recordset?->close();
        }

        if ($rsu_ids) {
            $DB->delete_records_list(response_slot_user::TABLE, 'id', $rsu_ids);
        }
        if ($rs_ids) {
            $DB->delete_records_list(response_slot::TABLE, 'id', $rs_ids);
        }
        if ($spe_ids) {
            $DB->delete_records_list(survey_part_execution::TABLE, 'id', $spe_ids);
        }

        $DB->delete_records(teaching_event::TABLE, ['id' => $eventid]);

        $transaction->allow_commit();

        $course = get_course($courseid);
        $model = survey_execution_data::load_from_course_required($course);

        return [
            'new_table_html' => $OUTPUT->render(new course_event_slot_table($model, $course)),
        ];
    }
}
