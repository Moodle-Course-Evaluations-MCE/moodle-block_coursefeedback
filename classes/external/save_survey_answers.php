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

use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitemtype_answerdata;
use core\exception\coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to save survey responses.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_survey_answers extends external_api {

    /**
     * Returns information about the active survey (with surveypartexecution and surveypartexecutionoptions) for the given courseid.
     * @param int $courseid
     * @return array [int surveypartexecutionid => [int surveypartexecutionoptionid => object record]], where record contains
     * the surveyexecutionid, surveypartexecutionid, surveypartid and surveypartexecutionoptionid.
     */
    private static function get_active_survey_for_course(int $courseid): array {
        global $DB;
        $records = $DB->get_records_sql(
            'SELECT speo.id as surveypartexecutionoptionid, se.id as surveyexecutionid, ' .
            'spe.id as surveypartexecutionid, spe.surveypartid ' .
            'FROM {block_coursefeedback_surveyexecution} se ' .
            'JOIN {block_coursefeedback_surveypartexecution} spe ON se.id = spe.surveyexecutionid ' .
            'JOIN {block_coursefeedback_surveypartexecutionoption} speo ON spe.id = speo.surveypartexecutionid ' .
            'WHERE se.courseid = :courseid ',
            ['courseid' => $courseid]
        );

        $structure = [];

        foreach ($records as $record) {
            $structure[$record->surveyexecutionid] ??= [];
            $structure[$record->surveyexecutionid][$record->surveypartexecutionid] ??= [];
            $structure[$record->surveyexecutionid][$record->surveypartexecutionid][$record->surveypartexecutionoptionid] = $record;
        }

        if (count($structure) > 1) {
            throw new coding_exception('Multiple surveys active at the same time.');
        }

        return reset($structure) ?: [];
    }

    /**
     * Parameter description.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'surveyparts' => new external_multiple_structure(
                new external_single_structure([
                    'surveypartexecutionoptionid' => new external_value(PARAM_INT, 'id of the surveypartexecutionoption chosen'),
                    'answers' => new external_multiple_structure(
                        new external_single_structure([
                            'surveyitemid' => new external_value(PARAM_INT),
                            'value' => new external_value(PARAM_RAW, 'the actual value as json, depending on the surveyitemtype'),
                        ]),
                    ),
                ]),
            ),
        ]);
    }

    /**
     * Function to save survey response.
     * @param int $courseid
     * @param array $submittedsurveyparts
     * @return array[]
     */
    public static function execute(int $courseid, array $submittedsurveyparts): array {
        [
            'courseid' => $courseid,
            'surveyparts' => $submittedsurveyparts,
        ] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'surveyparts' => $submittedsurveyparts,
        ]);

        global $DB;

        $context = \core\context\course::instance($courseid);
        self::validate_context($context);
        require_capability('block/coursefeedback:filloutsurvey', $context);

        $submittedsurveyparts_by_surveypartexecutionoptionid = [];
        foreach ($submittedsurveyparts as $submittedsurveypart) {
            $submittedsurveyparts_by_surveypartexecutionoptionid[$submittedsurveypart['surveypartexecutionoptionid']]
                = $submittedsurveypart;
        }

        $answers_by_surveypartexecutionid = [];
        $surveypartexecutionids_by_surveypartid = [];

        $transaction = $DB->start_delegated_transaction();

        $surveypartexecutions = self::get_active_survey_for_course($courseid);
        foreach ($surveypartexecutions as $surveypartexecutionoptions) {
            foreach ($surveypartexecutionoptions as $record) {
                if (isset($submittedsurveyparts_by_surveypartexecutionoptionid[$record->surveypartexecutionoptionid])) {
                    if (isset($answers_by_surveypartexecutionid[$record->surveypartexecutionid])) {
                        throw new coding_exception('Multiple different options chosen at the same time.');
                    }
                    $answers_by_surveypartexecutionid[$record->surveypartexecutionid] =
                        $submittedsurveyparts_by_surveypartexecutionoptionid[$record->surveypartexecutionoptionid];
                }
                $surveypartexecutionids_by_surveypartid[$record->surveypartid] ??= [];
                $surveypartexecutionids_by_surveypartid[$record->surveypartid][] = $record->surveypartexecutionid;
            }
        }

        $surveyparts = surveypart::get_surveyparts_by_id(array_keys($surveypartexecutionids_by_surveypartid));

        $data_by_surveyitemtype = [];
        foreach ($surveyparts as $surveypart) {
            $surveyitems = $surveypart->get_surveyitems();
            $questiondata = surveyitem_manager::get_questiondata_for_surveyparts([$surveypart]);
            foreach ($surveypartexecutionids_by_surveypartid[$surveypart->get('id')] as $surveypartexecutionid) {
                $answers = $answers_by_surveypartexecutionid[$surveypartexecutionid]['answers'];

                $answers_by_surveyitemid = [];
                foreach ($answers as $answer) {
                    $answers_by_surveyitemid[$answer['surveyitemid']] = $answer['value'];
                }

                $surveypartexecutionoptionid =
                    $answers_by_surveypartexecutionid[$surveypartexecutionid]['surveypartexecutionoptionid'];
                $respsetid = $DB->insert_record('block_coursefeedback_surveypartexecutionoptionresp', [
                    'surveypartexecutionoptionid' => $surveypartexecutionoptionid,
                ]);
                foreach ($surveyitems as $surveyitem) {
                    if (!isset($answers_by_surveyitemid[$surveyitem->get('id')])) {
                        // TODO what should we do here?
                        continue;
                    }
                    $surveyitemtype = $surveyitem->get('surveyitemtype');
                    $data_by_surveyitemtype[$surveyitemtype] ??= [];
                    $data_by_surveyitemtype[$surveyitemtype][] = new surveyitemtype_answerdata(
                        $respsetid,
                        $surveyitem->get('id'),
                        json_decode($answers_by_surveyitemid[$surveyitem->get('id')], depth: 10, flags: JSON_THROW_ON_ERROR),
                        $questiondata[$surveyitemtype][$surveyitem->get('id')],
                    );
                }
            }
        }

        foreach ($data_by_surveyitemtype as $surveyitemtype => $data) {
            $surveyitemtypelib = surveyitem_manager::get_surveyitemtype($surveyitemtype);
            $surveyitemtypelib->check_and_save_answers($data);
        }

        // TODO is this problematic? Should there be retries, or moving the start of the transaction to later?
        $transaction->allow_commit();

        return [];
    }

    /**
     * TODO
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
