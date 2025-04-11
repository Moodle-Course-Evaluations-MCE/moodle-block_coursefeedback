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
 * Export functions
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

require_once($CFG->dirroot . "/blocks/coursefeedback/lib.php");
require_once($CFG->libdir . '/csvlib.class.php');
require_once(__DIR__ . "/locallib.php");

/**
 * Export feedback data for a course.
 *
 * @package block
 * @subpackage coursefeedback
 * @copyright innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_exporter {
    protected $csvexportwriter;

    public function __construct() {
        $this->csvexportwriter = new csv_export_writer();
    }

    public function create_file($courseid, $feedbackid) {
        global $DB;
        $this->csvexportwriter->set_filename(get_string("download_html_filename", "block_coursefeedback")
            . date("_Y-m-d-H-i"));
        $headrow = [
            get_string("download_thead_questions", "block_coursefeedback"),
            get_string('notif_emoji_super', 'block_coursefeedback'),
            get_string('notif_emoji_good', 'block_coursefeedback'),
            get_string('notif_emoji_ok', 'block_coursefeedback'),
            get_string('notif_emoji_neutral', 'block_coursefeedback'),
            get_string('notif_emoji_bad', 'block_coursefeedback'),
            get_string('notif_emoji_superbad', 'block_coursefeedback'),
            get_string('table_html_average', 'block_coursefeedback'),
            get_string('table_html_votes', 'block_coursefeedback'),
            get_string('table_html_nochoice', 'block_coursefeedback'),
        ];
        $this->csvexportwriter->add_data($headrow);

        // Get the counted answers for each question and each answer possibility
        $qanswercounts = block_coursefeedback_get_qanswercounts($courseid, $feedbackid);

        $questions = block_coursefeedback_get_questions_by_language($feedbackid, [current_language()],
            CFB_QUESTIONTYPE_SCHOOLGRADE);
        foreach ($questions as $question) {
            // Put questionstring in front of $answerdata and add the data to the csv file
            if ($qanswercounts[$question->questionid]) {
                $answersdata = $qanswercounts[$question->questionid];
                array_unshift($answersdata, $question->question);
                $this->csvexportwriter->add_data($answersdata);
            }
        }

        // Start the download
        $this->csvexportwriter->download_file();
    }
}

/**
 * Export essay answers for a given course.
 *
 * @package block
 * @subpackage coursefeedback
 * @copyright innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class essay_exporter {
    protected $csvexportwriter;

    public function __construct() {
        $this->csvexportwriter = new csv_export_writer();
    }

    public function create_file($courseid, $feedbackid, $questionid = null) {
        global $DB;
        $this->csvexportwriter->set_filename(get_string("download_html_filename", "block_coursefeedback")
            . "_" . get_string("questiontype_essay", "block_coursefeedback") . date("_Y-m-d-H-i"));

        // Check and get course.
        if (!($course = $DB->get_record("course", ["id" => $courseid]))) {
            throw new moodle_exception(get_string("except_invalid_courseid", "block_coursefeedback"));
        }

        // Insert headrow.
        $headrow = [
            format_string($course->fullname),
            get_string("pluginname", "block_coursefeedback"),
            block_coursefeedback_get_feedbackname($feedbackid),
            get_string("questiontype_essay", "block_coursefeedback") . " "
                . get_string("resultspage_title", "block_coursefeedback"),
        ];
        $this->csvexportwriter->add_data($headrow);
        $this->csvexportwriter->add_data([]);

        // Get needed questions.
        $questions = block_coursefeedback_get_questions_by_language(
                $feedbackid,
                current_language(),
                CFB_QUESTIONTYPE_ESSAY,
                "questionid",
                "questionid,question",
                $questionid
        );

        // Insert all textanswers for each question.
        foreach ($questions as $question) {
            $questiondata = [
                get_string("download_thead_questions", "block_coursefeedback")
                    . " " . $question->questionid .": ",
                format_string($question->question),
            ];
            $this->csvexportwriter->add_data($questiondata);

            // Get textanswers for question.
            $answers = $DB->get_records('block_coursefeedback_textans', ['course' => $courseid,
                'coursefeedbackid' => $feedbackid,
                'questionid' => $question->questionid], 'id', 'id,textanswer');
            foreach($answers as $answer) {
                $answersdata = [
                    format_string($answer->textanswer),
                ];
                $this->csvexportwriter->add_data($answersdata);
            }
            $this->csvexportwriter->add_data([]);
        }

        // Start the download
        $this->csvexportwriter->download_file();
    }
}


/**
 * Export feedback data for an entire feedback.
 *
 * @package block
 * @subpackage coursefeedback
 * @copyright innoCampus, TU Berlin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ranking_exporter {
    protected $csvexportwriter;

    public function __construct() {
        $this->csvexportwriter = new csv_export_writer();
    }

    public function create_file($feedbackid, $questionid = 0) {
        global $DB;
        $filename = clean_param(get_string("download_html_filename", "block_coursefeedback")
            . date("_Y-m-d-H-i"), PARAM_FILE);
        $this->csvexportwriter->set_filename($filename);

        $feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);
        $this->csvexportwriter->add_data([
            'Feedbackid: ' . $feedback->id,
            'Feedbackname: ' . $feedback->name,
        ]);

        // Get all questions
        $questions = block_coursefeedback_get_questions_by_language($feedback->id, [current_language()],
                CFB_QUESTIONTYPE_SCHOOLGRADE);

        if ($questionid != 0) {
            // Only display one question....
            $questions = array_filter($questions, function ($q) use ($questionid) {
                return $q->questionid == $questionid;
            });
        }

        foreach ($questions as $question) {
            // Output headings.
            $this->csvexportwriter->add_data([]);
            $this->csvexportwriter->add_data([
                $question->question,
                $question->questionid,
            ]);

            $this->csvexportwriter->add_data([
                get_string('course'),
                get_string('user'),
                get_string('name'),
                get_string('categories'),
                get_string('categorypath', 'block_coursefeedback'),
                get_string('notif_emoji_super', 'block_coursefeedback'),
                get_string('notif_emoji_good', 'block_coursefeedback'),
                get_string('notif_emoji_ok', 'block_coursefeedback'),
                get_string('notif_emoji_neutral', 'block_coursefeedback'),
                get_string('notif_emoji_bad', 'block_coursefeedback'),
                get_string('notif_emoji_superbad', 'block_coursefeedback'),
                get_string('table_html_average', 'block_coursefeedback'),
                get_string('table_html_votes', 'block_coursefeedback'),
                get_string('table_html_nochoice', 'block_coursefeedback'),
            ]);
            $courses = block_coursefeedback_get_courserankings($question->questionid, $feedbackid);

            foreach ($courses as $course) {
                $this->csvexportwriter->add_data((array) $course);
            }
        }
        $this->csvexportwriter->download_file();
    }
}
