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
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\renderables\course_event_slot_table;
use coding_exception;
use context_course;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to create and update teaching events in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert_event extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'eventid' => new external_value(PARAM_INT, 'if updating, the id of the event to update, otherwise 0', VALUE_DEFAULT, 0),
            'surveyexecutionid' => new external_value(PARAM_INT, 'id of the survey execution to which the slot belongs'),
            'name' => new external_value(PARAM_TEXT, 'name of the (new) event'),
            'eventtypeid' => new external_value(PARAM_INT, 'id of the event type'),
            'surveypartid' => new external_value(PARAM_INT, 'id of the survey part to use'),
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
     * @param int $eventid
     * @param int $surveyexecutionid
     * @param string $name
     * @param int $eventtypeid
     * @param int $surveypartid
     * @return array
     */
    public static function execute(int $eventid, int $surveyexecutionid, string $name, int $eventtypeid, int $surveypartid): array {
        [
            'eventid' => $eventid, 'surveyexecutionid' => $surveyexecutionid, 'name' => $name, 'eventtypeid' => $eventtypeid,
            'surveypartid' => $surveypartid
        ] = self::validate_parameters(self::execute_parameters(), [
            'eventid' => $eventid, 'surveyexecutionid' => $surveyexecutionid, 'name' => $name, 'eventtypeid' => $eventtypeid,
            'surveypartid' => $surveypartid,
        ]);

        $survey_execution = survey_execution::get_record(['id' => $surveyexecutionid], MUST_EXIST);
        $courseid = $survey_execution->get('courseid');

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('block/coursefeedback:changecoursesettings', $context);

        global $DB, $OUTPUT;
        $transaction = $DB->start_delegated_transaction();

        if ($eventid) {
            $event = teaching_event::get_record(['id' => $eventid], MUST_EXIST);
            if ($event->get('courseid') !== $courseid) {
                throw new coding_exception("Teaching event '$eventid' does not belong to course '$courseid'");
            }

            $isnew = false;
        } else {
            $event = new teaching_event();
            $event->set('courseid', $courseid);

            $isnew = true;
        }

        if (
            $eventtypeid === 0
            || $eventtypeid !== $event->get('eventtypeid')
            && !eventtype::record_exists($eventtypeid)
        ) {
            throw new coding_exception("Event type '$eventtypeid' does not exist");
        }

        $event->set('name', $name);
        $event->set('eventtypeid', $eventtypeid);
        $event->save();

        $eventid = $event->get('id');

        $spe = $isnew ? null : survey_part_execution::get_record(['eventid' => $eventid]);
        if (!$spe) {
            $spe = new survey_part_execution();
            $spe->set_many([
                'surveyexecutionid' => $surveyexecutionid,
                'eventid' => $eventid,
            ]);
        }

        if ($surveypartid === 0 || $surveypartid !== $spe->get('surveypartid') && !surveypart::record_exists($surveypartid)) {
            throw new coding_exception("Survey part '$surveypartid' does not exist");
        }

        if ($spe->get('surveypartid') !== $surveypartid) {
            $spe->set('surveypartid', $surveypartid);
        }

        $spe->save();

        if ($isnew) {
            $response_slot = new response_slot();
            $response_slot->set_many([
                'surveypartexecutionid' => $spe->get('id'),
                'name' => '-',
            ]);
            $response_slot->save();
        }

        $transaction->allow_commit();

        $model = course_feedback_data::load_from_course($courseid);

        return [
            'new_table_html' => $OUTPUT->render(new course_event_slot_table($model)),
        ];
    }
}
