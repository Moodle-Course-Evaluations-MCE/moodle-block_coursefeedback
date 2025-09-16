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

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

require_admin();
$id = required_param('id', PARAM_INT);
$surveypart = \block_coursefeedback\local\persistent\surveypart::get_record(['id' => $id], MUST_EXIST);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('surveyparts', 'block_coursefeedback'));

if ($action = optional_param('action', null, PARAM_ALPHANUMEXT)) {
    require_sesskey();
    if ($action === 'delete') {
        $surveyitem = surveyitem::get_record(['id' => required_param('deleteid', PARAM_INT)], MUST_EXIST);
        $surveyitem->delete_and_fix_sortorder();
        redirect($PAGE->url);
    }
}

echo $OUTPUT->header();

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

echo $OUTPUT->render($actionmenu);

$records = array_values(surveyitem::get_surveyitem_records_for_surveypart($surveypart->get('id')));
foreach ($records as &$record) {
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
}

echo $OUTPUT->render_from_template('block_coursefeedback/surveyitems', [
    'surveyitems' => $records,
]);

$PAGE->requires->js_call_amd('block_coursefeedback/drag-and-drop-reorder', 'init');

echo $OUTPUT->footer();
