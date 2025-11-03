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
 * Display the ranking page
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Moodle includes.
require_once(__DIR__ . "/../../config.php");
require_once($CFG->libdir . "/tablelib.php");
require_once(__DIR__ . "/exportlib.php");
require_once(__DIR__ . "/forms/coursefeedback_admin_forms.php");
require_once(__DIR__ . "/locallib.php");

use block_coursefeedback\form\f_ranking_form;

// Check for admin.
require_login();
$context = context_system::instance();
require_capability("block/coursefeedback:managefeedbacks", $context);

$config = get_config("block_coursefeedback");
$PAGE->set_url(new moodle_url("/blocks/coursefeedback/ranking.php"));
$PAGE->set_context($context);

$form = new f_ranking_form();
if ($form->is_submitted() && $form->is_validated()) {
    $data = $form->get_data();

    // Checking validation here again because JS validation (show/hide downloadbuttons might not be working
    // Standard validation in form f_ranking_form was not working because the warnigs would not dissapear
    // because through download the page is not new loading
    if (!isset($data->feedback) || $data->feedback == 0) {
        $message = get_string("form_select_feedback", "block_coursefeedback");
        \core\notification::add($message, \core\output\notification::NOTIFY_ERROR);
    } else if (isset($data->downloadqu) && (!isset($data->question) || $data->question == 0)) {
        $message = get_string("form_select_question", "block_coursefeedback");
        \core\notification::add($message, \core\output\notification::NOTIFY_ERROR);
    } else {
        $rankingexport = new ranking_exporter();
        if (isset($data->downloadfb)) {
            $rankingexport->create_file($data->feedback,0,1);
        } else if (isset($data->downloadqu)) {
            $rankingexport->create_file($data->feedback, $data->question);
        } else if (isset($data->downloadfbessay)) {
		$rankingexport->create_file($data->feedback,0,0); 
        }
    }
}

$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("page_link_rankings", "block_coursefeedback"));
$PAGE->set_heading(get_string("page_link_rankings", "block_coursefeedback"));
$PAGE->navbar->add(get_string("blocks"), new moodle_url("/admin/blocks.php"));
$PAGE->navbar->add(get_string("pluginname", "block_coursefeedback"),
    new moodle_url("/admin/settings.php", ["section" => "blocksettingcoursefeedback"]));
$PAGE->navbar->add(get_string("page_link_rankings", "block_coursefeedback"),
    new moodle_url("/blocks/coursefeedback/ranking.php"));
$PAGE->requires->js_call_amd('block_coursefeedback/dynform', 'init');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("page_link_rankings", "block_coursefeedback"));
echo html_writer::div($form->render(), '', ['id' => 'formcontainer']);
$table = new html_table();
$table->id = "coursefeedback_table";
$table->head = [
    get_string("course"),
    get_string("idnumber"),
    get_string("table_html_votes", "block_coursefeedback"),
    get_string("table_html_average", "block_coursefeedback"),
];
$table->data = [];
echo html_writer::table($table);
// $OUTPUT->paging_bar();
echo '<br><b>TODO PAGINATION</b>';
echo $OUTPUT->footer();
