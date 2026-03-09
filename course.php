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

use block_coursefeedback\local\course_event_slot_table;
use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\survey_execution_period_editable;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$id = required_param('id', PARAM_INT);

$course = get_course($id);
$context = context_course::instance($course->id);
require_capability('block/coursefeedback:viewcoursesettings', $context);

$PAGE->set_url('/blocks/coursefeedback/course.php', ['id' => $id]);
$PAGE->set_context($context);
$title = get_string('course_settings', 'block_coursefeedback', $course);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$survey_executions = survey_execution::get_records(['courseid' => $course->id]);
if (!$survey_executions) {
    throw new \core\exception\moodle_exception('no_survey_execution', 'block_coursefeedback', debuginfo: "Course ID: $course->id");
}
if (count($survey_executions) > 1) {
    // TODO: Do we need to support this in any way?
    debugging('More than one survey execution for course ' . $course->id);
}

$survey_execution = reset($survey_executions);

$starttime_editable = new survey_execution_period_editable($survey_execution, 'starttime');
$endtime_editable = new survey_execution_period_editable($survey_execution, 'endtime');

$model = course_feedback_data::load_from_course($course);

$table = new course_event_slot_table($model);

$PAGE->requires->js_call_amd('block_coursefeedback/course_settings', 'init');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/course_settings', [
    'starttime_context' => $starttime_editable->export_for_template($OUTPUT),
    'endtime_context' => $endtime_editable->export_for_template($OUTPUT),
    'table_context' => $table->export_for_template($OUTPUT),
]);

echo $OUTPUT->footer();
