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
 * Edit a survey part.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\name_only_form;
use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
require_capability('block/coursefeedback:managesurveysglobally', \context_system::instance());

$id = optional_param('id', null, PARAM_INT);

$params = [];
$surveypart = null;
if ($id) {
    $params['id'] = $id;
    $surveypart = surveypart::get_record(['id' => $id], MUST_EXIST);
}

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveypart_edit.php', $params));
if ($id) {
    $title = get_string('edit_surveypart', 'block_coursefeedback');
} else {
    $title = get_string('new_surveypart', 'block_coursefeedback');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_heading($title);
$PAGE->set_title($title);

\block_coursefeedback\local\manager\breadcrumbs_manager::setup_edit_survey($surveypart);

$returnurl = new moodle_url('/blocks/coursefeedback/surveyparts.php');

$mform = new name_only_form($PAGE->url);
if ($surveypart) {
    $mform->set_data($surveypart->to_record());
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    if ($id) {
        $surveypart = new surveypart($id, $data);
        $surveypart->update();
    } else {
        $surveypart = new surveypart(0, $data);
        $surveypart->create();
    }
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
$mform->display();

echo html_writer::div('', '', ['id' => 'block_coursefeedback-surveyanchor']);
echo $OUTPUT->footer();
