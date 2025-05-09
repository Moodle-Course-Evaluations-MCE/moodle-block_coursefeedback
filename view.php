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
 * Display homepage of the given feedbacks for this course  (Survey analysis).
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/lib.php");
require_once(__DIR__ . "/exportlib.php");
require_once(__DIR__ . "/locallib.php");

$courseid = required_param("course", PARAM_INT);
$feedbackid = required_param("feedback", PARAM_INT);
$download = optional_param("download", null, PARAM_ALPHA);

if (!($course = $DB->get_record("course", ["id" => $courseid]))) {
    throw new moodle_exception(get_string("except_invalid_courseid", "block_coursefeedback"));
}

require_login($course);
$context = context_course::instance($course->id);
require_capability("block/coursefeedback:viewanswers", $context);
$feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);

$statusmsg = "";
$errormsg = "";

if (!empty($download)) {
    require_capability("block/coursefeedback:download", $context);
    $export = new feedback_exporter();
    $export->create_file($courseid, $feedbackid);
}

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot);
}

\block_coursefeedback\event\coursefeedback_viewed::create(["context" => $context])->trigger();

$PAGE->set_url(new moodle_url("/blocks/coursefeedback/view.php", ["course" => $course->id, "feedback" => $feedbackid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("page_link_viewresults", "block_coursefeedback"));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string("page_html_viewnavbar", "block_coursefeedback"));

$link = "";

$currentlang = current_language();

// Get only schoolgrade questions for now.
$questions = block_coursefeedback_get_questions_by_language($feedbackid, $currentlang,
        CFB_QUESTIONTYPE_SCHOOLGRADE);

if ($questions) {
    $answers = block_coursefeedback_get_qanswercounts($courseid, $feedbackid);
    $table = new html_table();
    $table->id = "coursefeedback_table";
    $table->size = array_fill(0, 8, "10%");
    $j = 0;
    foreach ($questions as $question) {
        $table->data[$j] = new html_table_row();
        $table->data[$j]->attributes = ["class" => "coursefeedback_table_headrow"];
        $c11 = new html_table_cell();
        $c11->colspan = 9;
        $c11->style = "padding-bottom:1em;";
        $c11->text = html_writer::tag("span",
            get_string("form_header_question", "block_coursefeedback", $question->questionid)
            . ": ", ["style" => "font-weight: bold; font-size: 1.5rem"]);
        $c11->text .= html_writer::tag("span", format_string($question->question), ["style" => "font-size: 1.5rem"]);
        $table->data[$j++]->cells = [$c11];
        $table->data[$j] = new html_table_row();
        $table->data[$j]->attributes = ["class" => "coursefeedback_table_sdescrow"];
        $c21 = new html_table_cell();
        $c22 = new html_table_cell();
        $c23 = new html_table_cell();
        $c24 = new html_table_cell();
        $c25 = new html_table_cell();
        $c26 = new html_table_cell();
        $c27 = new html_table_cell();
        $c27->colspan = 3;

        $c21->text = get_string("notif_emoji_super", "block_coursefeedback");
        $c22->text = get_string("notif_emoji_good", "block_coursefeedback");
        $c23->text = get_string("notif_emoji_ok", "block_coursefeedback");
        $c24->text = get_string("notif_emoji_neutral", "block_coursefeedback");
        $c25->text = get_string("notif_emoji_bad", "block_coursefeedback");
        $c26->text = get_string("notif_emoji_superbad", "block_coursefeedback");

        $table->data[$j++]->cells = [$c21, $c22, $c23, $c24, $c25, $c26, $c27];

        $table->data[$j] = new html_table_row();
        $table->data[$j]->attributes = ["class" => "coursefeedback_table_descrow"];
        for ($i = 1; $i <= 9; $i++) {
            $cn = "c3" . $i;
            ${$cn} = new html_table_cell();
            ${$cn}->style = "font-weight:bold;";
        }
        $c31->text = '&#128512;';
        $c32->text = '&#128522;';
        $c33->text = '&#128578;';
        $c34->text = '&#128528;';
        $c35->text = '&#128533;';
        $c36->text = '&#128544;';
        $c37->text = get_string("table_html_average", "block_coursefeedback");
        $c38->text = get_string("table_html_votes", "block_coursefeedback");
        $c39->text = get_string("table_html_nochoice", "block_coursefeedback");
        $c31->style = "font-size: 1.5rem;";
        $c32->style = "font-size: 1.5rem;";
        $c33->style = "font-size: 1.5rem;";
        $c34->style = "font-size: 1.5rem;";
        $c35->style = "font-size: 1.5rem;";
        $c36->style = "font-size: 1.5rem;";
        $table->data[$j++]->cells = [$c31, $c32, $c33, $c34, $c35, $c36, $c37, $c38, $c39];

        $question->answers = $answers[$question->questionid];
        $table->data[$j] = new html_table_row();
        $table->data[$j]->attributes = ["class" => "coursefeedback_table_graderow"];
        for ($i = 1; $i <= 6; $i++) {
            $cn = "c4" . $i;
            ${$cn} = new html_table_cell();
            ${$cn}->text = $question->answers[$i];
        }
        $c47 = new html_table_cell();
        $c48 = new html_table_cell();
        $c49 = new html_table_cell();
        $c47->text = $question->answers['average'];
        $c48->text = $question->answers['choicessum'];
        $c49->text = $question->answers['abstentions'];
        $table->data[$j++]->cells = [$c41, $c42, $c43, $c44, $c45, $c46, $c47, $c48, $c49];
        $table->data[$j] = new html_table_row();
        $table->data[$j]->attributes = ["class" => "coursefeedback_table_blankrow"];
        $table->data[$j++]->style = "height:3em;border:none;";
    }

    $html = html_writer::table($table);
    $params = ["course" => $course->id, "feedback" => $feedbackid, "download" => "csv"];
    $link = html_writer::link(new moodle_url("/blocks/coursefeedback/view.php", $params),
        get_string("page_link_download", "block_coursefeedback", "CSV"));
} else if ($feedbackid > 0) {
    $html = get_string("page_html_noquestions", "block_coursefeedback");
} else {
    redirect(new moodle_url("/course/view.php", ["id" => $course->id]),
        get_string("page_html_nofeedbackactive", "block_coursefeedback"));
}

// Process Essayquestions if there are any.
if ($essayquestions = block_coursefeedback_get_questions_by_language($feedbackid, $currentlang,
    CFB_QUESTIONTYPE_ESSAY)) {
    // Render the essay answers
    $renderer = $PAGE->get_renderer('block_coursefeedback');
    $essayhtml = $renderer->render_essay_questions($essayquestions, $courseid, $feedbackid);
}

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("pluginname", "block_coursefeedback") . ": "
        . format_string($feedback->name) . ", "
        . get_string("page_link_viewresults", "block_coursefeedback"));

if ($errormsg !== "") {
    echo $OUTPUT->notification($errormsg);
} else if ($statusmsg !== "") {
    echo $OUTPUT->notification($statusmsg, "notifysuccess");
}
echo $OUTPUT->box_start("generalbox coursefeedbackbox");

// Output schoolgrade quetions section.
echo html_writer::tag('h3', get_string("questiontype", "block_coursefeedback") . ": "
        . get_string("questiontype_schoolgrades", "block_coursefeedback"));
if ($link > "") {
    echo $link . "<br/>";
}
echo html_writer::tag("span", get_string("page_html_viewintro", "block_coursefeedback"), ["id" => "viewintro"])
    . $OUTPUT->box_end()
    . $OUTPUT->box($html);

// Output Essay qustions section.
if (isset($essayhtml)) {
    echo html_writer::tag('h3', get_string("questiontype", "block_coursefeedback")
        . ": " . get_string("questiontype_essay", "block_coursefeedback"));
    echo $OUTPUT->box($essayhtml);
}

echo $OUTPUT->footer();
