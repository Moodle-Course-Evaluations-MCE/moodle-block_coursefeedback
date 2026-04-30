<?php
// This file is part of the QuestionPy Moodle plugin - https://questionpy.org
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

use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use core_user\fields;

/**
 * A class combining all the course feedback data for a single course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_feedback_data {

    /**
     * Private constructor, use {@see load_from_course()}.
     *
     * @param object $course
     * @param survey_execution $survey_execution
     * @param array<int, teaching_event> $events_by_id
     * @param array<int, eventtype> $types_by_event_id
     * @param array<int, survey_part_execution> $spes_by_event_id
     * @param array<int, surveypart> $survey_parts_by_spe_id
     * @param array<int, response_slot[]> $slots_by_spe_id
     * @param array<int, array<int, object>> $users_by_slot_id
     */
    private function __construct(
        /** @var object $course */
        public readonly object $course,
        /** @var survey_execution $survey_execution */
        public readonly survey_execution $survey_execution,
        /** @var array<int, teaching_event> $events_by_id */
        public readonly array $events_by_id,
        /** @var array<int, eventtype> $types_by_event_id */
        public readonly array $types_by_event_id,
        /** @var array<int, survey_part_execution> $spes_by_event_id */
        public readonly array $spes_by_event_id,
        /** @var array<int, surveypart> $survey_parts_by_spe_id */
        public readonly array $survey_parts_by_spe_id,
        /** @var array<int, response_slot[]> $slots_by_spe_id */
        public readonly array $slots_by_spe_id,
        /** @var array<int, array<int, object>> $users_by_slot_id */
        public readonly array $users_by_slot_id,
    ) {
    }

    /**
     * Loads the course feedback data for the given course.
     *
     * @param object|int $course_or_id
     * @return self
     */
    public static function load_from_course(object|int $course_or_id): self {
        global $DB;

        $course = is_int($course_or_id) ? get_course($course_or_id) : $course_or_id;

        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $event_fields = teaching_event::get_sql_fields('te', 'te_');
        $event_type_fields = eventtype::get_sql_fields('et', 'et_');
        $spe_fields = survey_part_execution::get_sql_fields('spe', 'spe_');
        $sp_fields = surveypart::get_sql_fields('sp', 'sp_');
        $slot_fields = response_slot::get_sql_fields('rs', 'rs_');
        $user_fields_sql = fields::for_name()
            ->including('id')
            ->get_sql(alias: 'u', namedparams: true, prefix: 'u_');

        $recordset = $DB->get_recordset_sql("
            SELECT $se_fields, $event_fields, $event_type_fields, $spe_fields, $sp_fields, $slot_fields $user_fields_sql->selects
            FROM {" . survey_execution::TABLE . "} se
            LEFT JOIN {" . teaching_event::TABLE . "} te ON se.courseid = te.courseid
            LEFT JOIN {" . eventtype::TABLE . "} et ON te.eventtypeid = et.id
            LEFT JOIN {" . survey_part_execution::TABLE . "} spe ON se.id = spe.surveyexecutionid AND te.id = spe.eventid
            LEFT JOIN {" . surveypart::TABLE . "} sp ON spe.surveypartid = sp.id
            LEFT JOIN {" . response_slot::TABLE . "} rs ON spe.id = rs.surveypartexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} ru ON rs.id = ru.surveypartexecutionoptionid
            LEFT JOIN {user} u ON ru.userid = u.id
            $user_fields_sql->joins
            WHERE se.courseid = :courseid
            ORDER BY se.id, te.id, spe.id, rs.id, ru.id
        ", ['courseid' => $course->id, ...$user_fields_sql->params]);

        try {
            if (!$recordset || !$recordset->valid()) {
                throw new moodle_exception(
                    'no_survey_execution',
                    'block_coursefeedback',
                    a: $course,
                    debuginfo: "courseid: $course->id"
                );
            }

            $row = $recordset->current();

            $survey_execution = new survey_execution(record: survey_execution::extract_record($row, 'se_'));

            $events_by_id = [];
            $event_types_by_id = [];
            $types_by_event_id = [];
            $spes_by_event_id = [];
            $survey_parts_by_spe_id = [];
            $slots_by_spe_id = [];
            $users_by_slot_id = [];

            $record_extractor = new record_extractor($recordset);

            foreach ($record_extractor->yield_records('te_') as $event_record) {
                $events_by_id[$event_record->id] = new teaching_event(record: $event_record);

                // Avoid creating multiple instances of the same event type.
                $type = $event_types_by_id[$event_record->eventtypeid] ?? null;
                if (!$type) {
                    $et_record = $record_extractor->get_related('et_');
                    if (!$et_record) {
                        throw new coding_exception("Event '$event_record->id' has eventtypeid '$event_record->eventtypeid' that "
                            . "doesn't exist");
                    }
                    $type = $event_types_by_id[$event_record->eventtypeid] = new eventtype(record: $et_record);
                }
                $types_by_event_id[$event_record->id] = $type;

                $spe_record = $record_extractor->get_related('spe_');
                if (!$spe_record) {
                    // This event has no survey part execution.
                    continue;
                }

                $spes_by_event_id[$event_record->id] = new survey_part_execution(record: $spe_record);
                $sp_record = $record_extractor->get_related('sp_');
                if ($spe_record->surveypartid && !$sp_record) {
                    throw new coding_exception("Survey part execution '$spe_record->id' has surveypartid "
                         . "'$spe_record->surveypartid' that doesn't exist.");
                }
                $survey_parts_by_spe_id[$spe_record->id] = new surveypart(record: $sp_record);

                foreach ($record_extractor->yield_records('rs_', fn($row) => $row->spe_id === $spe_record->id) as $slot_record) {
                    $slots_by_spe_id[$spe_record->id][] = new response_slot(record: $slot_record);

                    foreach ($record_extractor->yield_records('u_', fn($row) => $row->rs_id === $slot_record->id) as $user) {
                        $users_by_slot_id[$slot_record->id][$user->id] = $user;
                    }
                }
            }

            return new self(
                course: $course,
                survey_execution: $survey_execution,
                events_by_id: $events_by_id,
                types_by_event_id: $types_by_event_id,
                spes_by_event_id: $spes_by_event_id,
                survey_parts_by_spe_id: $survey_parts_by_spe_id,
                slots_by_spe_id: $slots_by_spe_id,
                users_by_slot_id: $users_by_slot_id,
            );
        } finally {
            $recordset?->close();
        }
    }
}
