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
 * Display Feedbackinfo.
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
require_once($CFG->libdir . "/completionlib.php");

$feedbackid = required_param("feedback", PARAM_INT);
$courseid = required_param("course", PARAM_INT);
$lang = optional_param("lang", $USER->lang, PARAM_ALPHA);

if (!$context = context_course::instance($courseid)) {
    print_error("nocontext");
}
if ($courseid == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot . "/");
}
require_login($courseid);

$config = get_config("block_coursefeedback");
$feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);

// Only show site if the given feedback is also active right now.
if ($config->active_feedback != $feedbackid && $config->active_feedback != 0) {
    redirect(new moodle_url($CFG->wwwroot));
}

$url = new moodle_url("/blocks/coursefeedback/feedbackinfo.php", ["feedback" => $feedbackid, "course" => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("infopage_headline_feedbackinfo", "block_coursefeedback"));
$PAGE->set_heading(get_string("infopage_headline_feedbackinfo", "block_coursefeedback"));
$PAGE->navbar->add(get_string("pluginname", "block_coursefeedback"));

$infotext = '';

if ($config->since_coursestart_enabled) {
    // Only show if "since_coursestart" setting is enabled.
    $infotext .= html_writer::tag("p",
        get_string('infopage_html_coursestartcountd', 'block_coursefeedback',
        (ceil($config->since_coursestart / 86400))));
}
$infotext .= format_text($feedback->infotext, $feedback->infotextformat);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("infopage_headline_feedbackinfo", "block_coursefeedback") . ': ' . $feedback->name);
echo $OUTPUT->box($infotext);
echo $OUTPUT->footer();

