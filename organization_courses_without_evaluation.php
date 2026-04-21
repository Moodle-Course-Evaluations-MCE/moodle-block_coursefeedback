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
 * Edit courses without evaluation.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\survey_execution_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\table\courses_without_evaluation_table;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);

permission_manager::require_manage_organization($organization);
\block_coursefeedback\local\manager\breadcrumbs_manager::setup_organization_courses_without_evaluation($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_courses_without_evaluation.php', ['id' => $id]));
$PAGE->set_context($context);

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($action) {
    require_sesskey();
    switch ($action) {
        case 'create-default':
            $courseids = required_param_array('selected', PARAM_INT);
            foreach ($courseids as $courseid) {
                $course = get_course($courseid);
                $coursecatids = organization_category::get_all_recursive_coursecatids($organization->get('id'));
                if (!in_array($course->category, $coursecatids)) {
                    throw new \core\exception\coding_exception('Try to create survey for course not in category');
                }
                (new survey_execution_manager())->create_empty_survey_execution($courseid);
            }
            redirect($PAGE->url);
    }
}

$title = get_string('list_of_courses_without_evaluation', 'block_coursefeedback') . ': ' . $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]);

$table = new courses_without_evaluation_table(course_semester_mapping::SELECTED_SEMESTER, $organization);

echo $OUTPUT->header();

$table->out(0, false);

echo $OUTPUT->footer();
