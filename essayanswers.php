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
 * Display site of the given essayanswers for a specific question and a given course  (Survey analysis).
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
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
$questionid = optional_param("question", null, PARAM_INT);
$download = optional_param("download", null, PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);        // how many per page

// Check course.
if (!($course = $DB->get_record("course", ["id" => $courseid]))) {
    throw new moodle_exception(get_string("except_invalid_courseid", "block_coursefeedback"));
}

require_login($course);
$context = context_course::instance($course->id);
require_capability("block/coursefeedback:viewanswers", $context);
$feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);

// Provide CSV-download.
if (!empty($download)) {
    require_capability("block/coursefeedback:download", $context);
    $export = new essay_exporter();
    $export->create_file($courseid, $feedbackid, $questionid);
}

// Param "question" is only optional for download.
if (empty($questionid)) {
    throw new moodle_exception(get_string("except_invalid_questionid", "block_coursefeedback"));
}

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot);
}

\block_coursefeedback\event\coursefeedback_viewed::create(["context" => $context])->trigger();

$baseurl = new moodle_url("/blocks/coursefeedback/essayanswers.php", [
    "course" => $course->id,
    "feedback" => $feedbackid,
    "question" => $questionid,
    "page" => $page,
    "perpage" => $perpage]);

// How many non-empty essayanswers for this question?
$select = "course = :course
       AND coursefeedbackid = :coursefeedbackid 
       AND questionid = :questionid 
       AND textanswer IS NOT NULL 
       AND textanswer <> ''";
$params = [
    'course' => $courseid,
    'coursefeedbackid' => $feedbackid,
    'questionid' => $questionid,
];
$anscount = $DB->count_records_select('block_coursefeedback_textans', $select, $params);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("page_link_viewresults", "block_coursefeedback"));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string("page_html_viewnavbar", "block_coursefeedback"));

// Render the answers for this question.
$renderer = $PAGE->get_renderer('block_coursefeedback');
$essayhtml = $renderer->render_essay_answers($courseid, $feedbackid, $questionid, $anscount, $page, $perpage);

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("pluginname", "block_coursefeedback") . ": "
    . format_string($feedback->name));
echo $OUTPUT->box($essayhtml);
echo $OUTPUT->paging_bar($anscount, $page, $perpage, $baseurl);
echo $OUTPUT->footer();
