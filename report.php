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

/**
 * Report stuff here...
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

use block_coursefeedback\local\js_util;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_login();
$surveypartexecutionoptionid = required_param('id', PARAM_INT);
$slot = response_slot::get_record(['id' => $surveypartexecutionoptionid], MUST_EXIST);
$surveypartexecution = survey_part_execution::get_record(['id' => $slot->get('surveypartexecutionid')], MUST_EXIST);
$surveyexecution = survey_execution::get_record(['id' => $surveypartexecution->get('surveyexecutionid')], MUST_EXIST);

$context = context_course::instance($surveyexecution->get('courseid'));
$organization = organization::get_record(['id' => $surveyexecution->get('organizationid')], MUST_EXIST);

require_admin();

$PAGE->set_context($context);

$title = get_string('reporting', 'block_coursefeedback');

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/report.php'), ['id' => $surveypartexecutionoptionid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$data = surveyitem_manager::export_report_for_slot($slot);
js_util::js_call_amd('block_coursefeedback/report', 'init', [$data]);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/report/report', [
    'questions' => $data,
]);

echo $OUTPUT->footer();
