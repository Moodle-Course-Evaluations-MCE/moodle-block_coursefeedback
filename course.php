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

use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\output\course_event_slot_table;
use block_coursefeedback\output\survey_execution_period;

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

$survey_executions = survey_execution::get_records(['courseid' => $course->id]);
if (!$survey_executions) {
    throw new moodle_exception('no_survey_execution', 'block_coursefeedback', a: $course, debuginfo: "courseid: $course->id");
}
if (count($survey_executions) > 1) {
    // TODO: Do we need to support this in any way?
    debugging('More than one survey execution for course ' . $course->id);
}

$survey_execution = reset($survey_executions);

$model = course_feedback_data::load_from_course($course);

$table = new course_event_slot_table($model);
$survey_execution_period = new survey_execution_period(
    $survey_execution,
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

echo $renderer->render_from_template('block_coursefeedback/course_settings', [
    'survey_execution_period_context' => $survey_execution_period->export_for_template($renderer),
    'table_context' => $table->export_for_template($renderer),
]);

// Make sure AlpineJS is initialized after all other scripts have been loaded.
$renderer->init_alpine_js();

echo $OUTPUT->footer();
