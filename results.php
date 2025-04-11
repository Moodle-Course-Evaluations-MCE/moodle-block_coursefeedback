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
 * Display answered feedbacks for a course.
 *
 * @package    block
 * @subpackage coursefeedback
 * @copyright  2022 Felix Di Lenarda (@ innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/lib.php");
require_once($CFG->libdir . "/tablelib.php");

$courseid = required_param("course", PARAM_INT);
$lang = optional_param("lang", $USER->lang, PARAM_ALPHA);

if ($courseid == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot . "/");
}
require_login($courseid);
$context = context_course::instance($courseid);
require_capability("block/coursefeedback:viewanswers", $context);
$config = get_config("block_coursefeedback");

$fbs = block_coursefeedbck_get_fbsfor_course($courseid);

if (count($fbs) == 1) {
    // If only one Feedback, redirect to the Feedbacks view.php site
    $feedback = current($fbs);
    $url = new moodle_url('/blocks/coursefeedback/view.php', ['course' => $courseid, 'feedback' => $feedback->id]);
    redirect($url);
} else {
    // Several feedbacks with answers available -> Show table with links.
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $PAGE->set_title(get_string('resultspage_title', 'block_coursefeedback'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url(new moodle_url("/blocks/coursefeedback/results.php", ["course" => $courseid]));
    $PAGE->set_context($context);
    $PAGE->set_pagelayout("incourse");
    $title = get_string('pluginname', 'block_coursefeedback');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('resultspage_headline', 'block_coursefeedback'));
    echo html_writer::span(get_string('resultspage_howto', 'block_coursefeedback'));

    // Create table with all the Links for different Feedbackresults
    $table = new flexible_table('coursefeedback-results-table');
    $columnsnheadings = [
        get_string("idnumber"),
        get_string("name"),
        get_string("table_header_questions", "block_coursefeedback"),
        get_string("modified")];
    $table->define_columns($columnsnheadings);
    $table->define_headers($columnsnheadings);
    $table->define_baseurl($PAGE->url);
    $table->setup();
    foreach ($fbs as $feedback) {
        $feedbackrecord = $DB->get_record("block_coursefeedback", ["id" => $feedback->id]);
        $url = new moodle_url('/blocks/coursefeedback/view.php', ['course' => $courseid, 'feedback' => $feedback->id]);
        $namelink = html_writer::tag('div', html_writer::link($url, $feedbackrecord->name));
        $languages = block_coursefeedback_get_combined_languages($feedback->id, false);
        $questioncount = $DB->count_records_select("block_coursefeedback_questns",
            "coursefeedbackid = :fid AND language = :curlang GROUP BY language",
            ["fid" => $feedback->id, "curlang" => current(array_keys($languages))]);
        $table->add_data([$feedback->id, $namelink, $questioncount, $feedbackrecord->timemodified]);
    }
    $table->finish_output();
    echo $OUTPUT->footer();
}

