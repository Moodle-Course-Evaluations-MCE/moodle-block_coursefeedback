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

namespace block_coursefeedback\task;

use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\survey_execution_data;
use core\exception\coding_exception;

/**
 * Task file for locking survey_executions.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class start_surveys_task extends \core\task\scheduled_task {

    /** @var int Amount of seconds surveys will be created in advance. */
    private const CREATE_SURVEYS_IN_ADVANCE_SECONDS = 2 * 60;

    /**
     * Return the task's name as shown in admin screens.
     * @return string
     */
    public function get_name() {
        return get_string('task:start_surveys', 'block_coursefeedback');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $survey_execution_ids = $DB->get_fieldset_sql(
            "SELECT se.id
            FROM {" . survey_execution::TABLE . "} se
            JOIN {" . organization::TABLE . "} o ON se.organizationid = o.id
            WHERE se.status = " . survey_execution::STATUS_PLANNED . "
                AND :time >= COALESCE(se.starttime, o.default_evaluation_starttime)",
            ['time' => time() + self::CREATE_SURVEYS_IN_ADVANCE_SECONDS],
        );

        $survey_execution_datas = survey_execution_data::load_from_survey_execution_ids($survey_execution_ids);

        foreach ($survey_execution_datas as $survey_execution_data) {
            $se = $survey_execution_data->survey_execution;

            $has_surveypart = false;

            $transaction = $DB->start_delegated_transaction();

            foreach ($survey_execution_data->events_by_id as $event) {
                $spe = $survey_execution_data->spes_by_event_id[$event->get('id')];
                $surveypart = $survey_execution_data->survey_parts_by_spe_id[$spe->get('id')] ?? null;
                if ($surveypart) {
                    $has_surveypart = true;
                } else {
                    $eventtype = $survey_execution_data->types_by_event_id[$event->get('id')] ?? null;
                    if ($eventtype && $eventtype->get('surveypartid')) {
                        $spe->set('surveypartid', $eventtype->get('surveypartid'));
                        $spe->save();
                        $has_surveypart = true;
                    }
                    // TODO Otherwise, should we delete SPE and Event?
                }
            }

            if (!$has_surveypart && $survey_execution_data->organization->get('default_surveypartid')) {
                $teaching_event = new teaching_event(record: (object) [
                    'courseid' => $se->get('courseid'),
                    'eventtypeid' => null,
                    'name' => '',
                ]);
                $teaching_event->create();
                $spe = new survey_part_execution(record: (object) [
                    'surveyexecutionid' => $se->get('id'),
                    'surveypartid' => $survey_execution_data->organization->get('default_surveypartid'),
                    'eventid' => $teaching_event->get('id'),
                ]);
                $spe->create();
                $slot = new \block_coursefeedback\local\persistent\response_slot(record: (object) [
                    'surveypartexecutionid' => $spe->get('id'),
                    'name' => '',
                    'externalid' => null,
                ]);
                $slot->create();
            }

            if (!$se->get('starttime')) {
                $se->set('starttime', $survey_execution_data->organization->get('default_evaluation_starttime'));
            }
            if (!$se->get('endtime')) {
                $se->set('endtime', $survey_execution_data->organization->get('default_evaluation_endtime'));
            }
            $se->set('status', survey_execution::STATUS_STARTED);
            $se->save();

            $transaction->allow_commit();
        }
    }
}
