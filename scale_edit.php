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
 * Edit a survey item.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\scale;
use block_coursefeedback\local\persistent\surveypart;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();

$surveypartid = required_param('surveypartid', PARAM_INT);
$surveypart = surveypart::get_record(['id' => $surveypartid], MUST_EXIST);
permission_manager::require_permission_for_editing_surveypart($surveypart);

$defaultlanguage = language_manager::get_default_language_for_surveypart($surveypartid);

$params = ['surveypartid' => $surveypartid];
$id = optional_param('id', null, PARAM_INT);
$scale = null;
if ($id) {
    $scale = $DB->get_record('block_coursefeedback_scale', ['id' => $id], '*', MUST_EXIST);
    if ($scale->surveypartid != $surveypart->get('id')) {
        throw new \core\exception\coding_exception('Scale does not belong to surveypart');
    }
    $params['id'] = $id;
    // Load strings.
    foreach (scale::TEXT_FIELDS as $field) {
        if ($scale->{$field . 'id'} ?? false) {
            $scale->$field = language_manager::fetch_string($scale->{$field . 'id'}, $defaultlanguage);
        }
    }
}
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/scale_edit.php', $params));
$PAGE->set_context(context_system::instance());
if ($scale) {
    $title = get_string('edit_scale', 'block_coursefeedback');
} else {
    $title = get_string('new_scale', 'block_coursefeedback');
}
$PAGE->set_heading($title);
$PAGE->set_title($title);
breadcrumbs_manager::setup_edit_survey_scale($surveypart, $id);

$returnurl = new moodle_url(
    '/blocks/coursefeedback/surveyitem_edit.php',
    ['surveypartid' => $surveypartid, 'type' => 'scalequestion']
);

$mform = new \block_coursefeedback\local\form\edit_scale_form(null, ['surveypart' => $surveypart, 'id' => $id]);
$mform->set_data($scale);
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $mform->get_data()) {
    if (!$scale) {
        $scale = new stdClass();
    }

    $scale->hasnoansweroption = $data->hasnoansweroption ?? false;

    foreach ($data as $key => $value) {
        $scale->$key = $value;
    }

    foreach (scale::TEXT_FIELDS as $field) {
        if ($data->$field ?? false) {
            if ($scale && isset($scale->{$field . 'id'})) {
                language_manager::update_string($scale->{$field . 'id'}, $data->$field, $defaultlanguage);
            } else {
                $scale->{$field . 'id'} = language_manager::create_string($data->$field, $defaultlanguage);
            }
        }
    }

    if ($scale->id ?? false) {
        $DB->update_record('block_coursefeedback_scale', $scale);
    } else {
        $DB->insert_record('block_coursefeedback_scale', $scale);
    }
    redirect($returnurl);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
