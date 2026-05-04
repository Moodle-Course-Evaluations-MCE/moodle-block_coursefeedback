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

/**
 * Editor the settings for a survey execution in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\event\survey_responses_deleted;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_execution_user;
use block_coursefeedback\output\course_event_slot_table;
use block_coursefeedback\output\survey_execution_period;
use core\exception\coding_exception;

require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE;

require_login();
$id = required_param('id', PARAM_INT);

$course = get_course($id);
$context = context_course::instance($course->id);
require_capability('block/coursefeedback:viewcoursesettings', $context);

$PAGE->set_url('/blocks/coursefeedback/course.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_course($course);

$organization = course_organization_mapping::get_instance()::get_organization_for_course($course);

if (!$organization) {
    throw new \core\exception\coding_exception('Course does not belong to an evaluation organization');
}

$model = survey_execution_data::load_from_course_required($course, $organization->get('id'));

$action = optional_param('action', false, PARAM_ALPHANUMEXT);
if ($action === 'delete_responses') {
    require_sesskey();

    if (!permission_manager::can_delete_responses($organization)) {
        throw new coding_exception('You do not have permission to do this.');
    }

    global $DB, $USER;

    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records_subquery('block_coursefeedback_surveypartexecutionoptionresp', 'id', 'id', '
    SELECT resp_set.id FROM {block_coursefeedback_surveypartexecutionoptionresp} resp_set
    JOIN {block_coursefeedback_surveypartexecutionoption} slot ON slot.id = resp_set.surveypartexecutionoptionid
    JOIN {block_coursefeedback_surveypartexecution} spe ON spe.id = slot.surveypartexecutionid
    WHERE spe.surveyexecutionid = :surveyexecutionid
    ', ['surveyexecutionid' => $model->survey_execution->get('id')]);

    $DB->delete_records_select(
        'block_coursefeedback_surveyexecution_user',
        'surveyexecutionid = :surveyexecutionid',
        ['surveyexecutionid' => $model->survey_execution->get('id')]
    );

    $event = survey_responses_deleted::create([
        'context' => $context,
        'courseid' => $course->id,
        'userid' => $USER->id,
        'other' => [
            'surveyexecutionid' => $model->survey_execution->get('id'),
        ],
    ]);
    $event->trigger();

    $transaction->allow_commit();
    redirect($PAGE->url);
}

$table = new course_event_slot_table($model, $course);

$survey_execution_period = new survey_execution_period(
    $model->survey_execution,
    $organization,
    editable: has_capability('block/coursefeedback:changecoursesurveyperiod', $context)
);

global $OUTPUT;
/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$PAGE->set_pagelayout('admin');
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('course_settings_of', 'block_coursefeedback', $course));
$PAGE->add_body_class('container');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_settings', 'block_coursefeedback'));

$num_responses = survey_execution_user::count_records(['surveyexecutionid' => $model->survey_execution->get('id')]);

echo $renderer->render_from_template('block_coursefeedback/course_settings', [
    'survey_execution_period_context' => $survey_execution_period->export_for_template($renderer),
    'table_context' => $table->export_for_template($renderer),
    'course_fullname' => $course->fullname,
    'localized_status' => $model->survey_execution->get_localized_status(),
    'num_responses' => $num_responses,
    'show_delete_responses' => $num_responses > 0 && permission_manager::can_delete_responses($organization),
    'delete_responses_url' => $PAGE->url->out(false, ['action' => 'delete_responses', 'sesskey' => sesskey()]),
]);

echo $OUTPUT->footer();
