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

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_form;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

require_admin();

$id = optional_param('id', null, PARAM_INT);
$surveypartid = required_param('surveypartid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUMEXT);
$surveyitemtype = surveyitem_manager::get_surveyitemtype($type);

$params = ['surveypartid' => $surveypartid, 'type' => $type];
$surveypart = surveypart::get_record(['id' => $surveypartid], MUST_EXIST);

$surveyitem = null;
if ($id) {
    $params['id'] = $id;
    $surveyitem = surveyitem::get_record(['id' => $id], MUST_EXIST);
    if ($surveyitem->get('surveypartid') !== $surveypartid || $surveyitem->get('surveyitemtype') !== $type) {
        // Generic error message.
        throw new coding_exception('Could not load surveyitem');
    }
}

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveyitem_edit.php', $params));
if ($id) {
    $title = get_string('edit_surveyitem', 'block_coursefeedback');
} else {
    $title = get_string('new_surveyitem', 'block_coursefeedback');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->navbar->add($title, new moodle_url($PAGE->url));

$returnurl = new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $surveypartid]);

$mformclass = $surveyitemtype->get_settings_mform();

if (!$mformclass) {
    if ($id) {
        throw new coding_exception('Cannot edit item of type ' . $type);
    }

    require_sesskey();
    $sortindex = surveyitem::count_records(['surveypartid' => $surveypartid]);
    $surveyitem = new surveyitem();
    $surveyitem->set('surveypartid', $surveypartid);
    $surveyitem->set('surveyitemtype', $type);
    $surveyitem->set('sortindex', $sortindex);
    $surveyitem->save();
    redirect($returnurl);
}

/** @var surveyitem_form $mform */
$mform = new $mformclass($PAGE->url);
$language = \block_coursefeedback\local\manager\language_manager::get_default_language_for_surveypart($surveypartid);

if ($surveyitem) {
    $data = $surveyitemtype->load_settings_mform($surveyitem, $language);
    $mform->set_data($data);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if (($data = $mform->get_data()) && isset($data->submitbutton)) {
    if (!$surveyitem) {
        $sortindex = surveyitem::count_records(['surveypartid' => $surveypartid]);
        $surveyitem = new surveyitem();
        $surveyitem->set('surveypartid', $surveypartid);
        $surveyitem->set('surveyitemtype', $type);
        $surveyitem->set('sortindex', $sortindex);
    }

    if (isset($data->text)) {
        $textid = $surveyitem->get('textid');
        if ($textid) {
            $DB->execute('UPDATE {block_coursefeedback_texttranslation} SET text = :text WHERE textid = :textid', [
                'text'  => $data->text['text'],
                'format' => $data->text['format'],
                'textid' => $textid,
            ]);
        } else {
            $textid = $DB->get_field_sql('SELECT max(id) + 1 FROM {block_coursefeedback_texttranslation}') ?: 1;
            $DB->insert_record('block_coursefeedback_texttranslation', [
                'textid' => $textid,
                'text' => $data->text['text'],
                'format' => $data->text['format'],
            ]);
            $surveyitem->set('textid', $textid);
        }
    }

    $surveyitem->save();
    $surveyitemtype->save_settings_mform($surveyitem->get('id'), $data, $language);
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
