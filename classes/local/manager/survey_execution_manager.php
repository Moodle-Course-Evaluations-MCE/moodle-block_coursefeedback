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

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use core\exception\coding_exception;

/**
 * Contains methods managing test data for now. Will be generalized or binned.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution_manager {

    /**
     * Ensures that the event types for lectures and exercises exist.
     *
     * @param organization $org
     * @return array
     */
    private function ensure_eventtypes(organization $org): array {
        global $DB;
        $lectures = eventtype::get_records_select(
            $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text('?') . ' AND organizationid = ?',
            ['Vorlesung', $org->get('id')],
        );
        $lecture = reset($lectures);
        if (!$lecture) {
            $lecture = new eventtype();
            $lecture->set_many([
                'name' => 'Vorlesung',
                'active' => true,
                'organizationid' => $org->get('id'),
            ]);
            $lecture->create();
        }

        $exercises = eventtype::get_records_select(
            $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text('?') . ' AND organizationid = ?',
            ['Übung', $org->get('id')]
        );
        $exercise = reset($exercises);
        if (!$exercise) {
            $exercise = new eventtype();
            $exercise->set_many([
                'name' => 'Übung',
                'active' => true,
                'organizationid' => $org->get('id'),
            ]);
            $exercise->create();
        }

        return [$lecture->get('id'), $exercise->get('id')];
    }

    /**
     * Creates a survey execution and sub-resources.
     *
     * @param int $courseid
     * @param int $surveypartid
     * @return void
     */
    public function create_survey_execution(int $courseid, int $surveypartid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $org = course_organization_mapping::get_instance()::get_organization_for_course($courseid);

        $surveypart = surveypart::get_record(['id' => $surveypartid], MUST_EXIST);

        $execution = survey_execution::get_record(['courseid' => $courseid, 'organizationid' => $org->get('id')]);
        if ($execution) {
            throw new coding_exception("Course $courseid already has a survey execution.");
        }

        $execution = (new survey_execution())->set_many([
            'courseid' => $courseid,
            'organizationid' => $org->get('id'),
            'starttime' => 0,
            // 10 years.
            'endtime' => time() + 365 * 24 * 60 * 60,
        ])->create();

        [$lectureid, $exerciseid] = $this->ensure_eventtypes($org);

        $events = teaching_event::get_records(['courseid' => $courseid]);
        if (!$events) {
            $events[] = (new teaching_event())->set_many([
                'courseid' => $courseid,
                'eventtypeid' => $lectureid,
                'name' => 'Vorlesung für Lineare Algebra I',
            ])->create();

            $events[] = (new teaching_event())->set_many([
                'courseid' => $courseid,
                'eventtypeid' => $exerciseid,
                'name' => 'Übung für Lineare Algebra I',
            ])->create();
        }

        foreach ($events as $event) {
            $spe = survey_part_execution::get_record([
                'surveyexecutionid' => $execution->get('id'),
                'eventid' => $event->get('id'),
            ]);
            if (!$spe) {
                $spe = (new survey_part_execution())->set_many([
                    'surveyexecutionid' => $execution->get('id'),
                    'eventid' => $event->get('id'),
                    'surveypartid' => $surveypart->get('id'),
                ])->create();

                $slot = response_slot::get_record(['surveypartexecutionid' => $spe->get('id')], IGNORE_MULTIPLE);
                if (!$slot) {
                    (new response_slot())->set_many([
                        'surveypartexecutionid' => $spe->get('id'),
                        'name' => '-',
                    ])->create();
                }
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Deletes a survey execution and all sub-resources.
     *
     * @param int $surveyexecutionid
     * @return void
     */
    public function delete_survey_execution(int $surveyexecutionid): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $recordset = $DB->get_recordset_sql("
            SELECT se.id AS se_id, spe.id AS spe_id, slot.id AS slot_id, spe.eventid as spe_eventid
            FROM {" . survey_execution::TABLE . "} se
            LEFT JOIN {" . survey_part_execution::TABLE . "} spe ON se.id = spe.surveyexecutionid
            LEFT JOIN {" . response_slot::TABLE . "} slot ON spe.id = slot.surveypartexecutionid
            WHERE se.id = :surveyexecutionid
        ", ['surveyexecutionid' => $surveyexecutionid]);

        if (!$recordset || !$recordset->valid()) {
            debugging("Tried to delete nonexistent survey execution with ID '$surveyexecutionid'");
            return;
        }

        $records = iterator_to_array($recordset, preserve_keys: false);

        $DB->delete_records_list(response_slot_user::TABLE, 'surveypartexecutionoptionid', array_column($records, 'slot_id'));
        $DB->delete_records_list(response_slot::TABLE, 'id', array_column($records, 'slot_id'));
        $DB->delete_records_list(survey_part_execution::TABLE, 'id', array_column($records, 'spe_id'));
        $DB->delete_records_list(teaching_event::TABLE, 'id', array_column($records, 'spe_eventid'));
        $DB->delete_records(survey_execution::TABLE, ['id' => $surveyexecutionid]);

        $transaction->allow_commit();
    }
}
