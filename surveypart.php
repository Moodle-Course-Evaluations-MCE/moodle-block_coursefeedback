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
 * List survey part.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
$id = required_param('id', PARAM_INT);
$surveypart = surveypart::get_record(['id' => $id], MUST_EXIST);
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
permission_manager::require_permission_for_editing_surveypart($surveypart);

$title = $surveypart->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);
breadcrumbs_manager::setup_survey($surveypart);

if ($action = optional_param('action', null, PARAM_ALPHANUMEXT)) {
    require_sesskey();
    if ($action === 'delete') {
        $surveyitem = surveyitem::get_record(['id' => required_param('deleteid', PARAM_INT)], MUST_EXIST);
        $surveyitem->delete_and_fix_sortorder();
        redirect($PAGE->url);
    }
    if ($action === 'reorder') {
        $orderingjson = required_param('ordering', PARAM_RAW);
        $ordering = json_decode($orderingjson);
        $surveypart->reorder_surveyitems($ordering);
        redirect($PAGE->url);
    }
}

echo $OUTPUT->header();

$context = [];

$actionmenu = new \core\output\action_menu();
$actionmenu->set_menu_trigger(
    get_string('add_surveyitem', 'block_coursefeedback'),
    'btn btn-primary'
);
$actionmenu->set_menu_left();

foreach (surveyitem_manager::get_all_surveyitemtypes() as $type => $class) {
    $actionmenu->add_secondary_action(
        new \core\output\action_link(
            new \moodle_url(
                '/blocks/coursefeedback/surveyitem_edit.php',
                ['type' => $type, 'surveypartid' => $id, 'sesskey' => sesskey()]
            ),
            $class->get_name(),
        )
    );
}

$scale_url = new moodle_url('/blocks/coursefeedback/scales.php', ['surveypartid' => $surveypart->get('id')]);
$context['id'] = $surveypart->get('id');
$context['action_url'] = $PAGE->url->out_omit_querystring();
$context['add_element_menu'] = $actionmenu->export_for_template($OUTPUT);
$context['has_scales'] = (bool) $DB->count_records('block_coursefeedback_scale', ['surveypartid' => $surveypart->get('id')]);
$context['scale_url'] = $scale_url->out(false);
$context['sesskey'] = sesskey();

$records = array_values(surveyitem::get_surveyitem_records_for_surveypart($surveypart->get('id')));
foreach ($records as $record) {
    $actionmenu = new \core\output\action_menu();

    // If item is editable.
    if (surveyitem_manager::get_surveyitemtype($record->surveyitemtype)->get_settings_mform()) {
        $editstr = get_string('edit');
        $actionmenu->add_secondary_action(new \core\output\action_link(
            new \moodle_url(
                '/blocks/coursefeedback/surveyitem_edit.php',
                ['type' => $record->surveyitemtype, 'surveypartid' => $id, 'id' => $record->id]
            ),
            $editstr,
            null,
            null,
            new \core\output\pix_icon('t/edit', $editstr),
        ));
    }

    $deletestr = get_string('delete');
    $actionmenu->add_secondary_action(new \core\output\action_link(
        new \moodle_url(
            $PAGE->url,
            ['action' => 'delete', 'deleteid' => $record->id, 'sesskey' => sesskey()],
        ),
        $deletestr,
        null,
        ['class' => 'text-danger'],
        new pix_icon('t/delete', $deletestr),
    ));

    $actionmenu->set_kebab_trigger();
    $record->actionmenu = $actionmenu->export_for_template($OUTPUT);
    $record->type = $record->surveyitemtype;
    $record->text = format_text($record->text, $record->format ?? FORMAT_HTML);
    $record->itemid = $record->id;
}

$context['surveyitems'] = $records;

echo $OUTPUT->render_from_template('block_coursefeedback/edit_survey', $context);

$PAGE->requires->js_call_amd('block_coursefeedback/drag_and_drop_reorder', 'init');

echo $OUTPUT->footer();
