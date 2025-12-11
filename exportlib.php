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
 * @package block_coursefeedback
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
        $scaleType = get_config('block_coursefeedback','scale');
        $headrow = ['Course',get_string("download_thead_questions", "block_coursefeedback")];
        if($scaleType === 'Classic'){
            array_push($headrow,
                get_string('notif_emoji_super', 'block_coursefeedback'),
                get_string('notif_emoji_good', 'block_coursefeedback'),
                get_string('notif_emoji_ok', 'block_coursefeedback'),
                get_string('notif_emoji_neutral', 'block_coursefeedback'),
                get_string('notif_emoji_bad', 'block_coursefeedback'),
                get_string('notif_emoji_superbad', 'block_coursefeedback'),
            );
        } else if($scaleType === 'Numeric'){
            $bound = get_config('block_coursefeedback','scalenumber');
            $headrow[0] = get_string("download_thead_questions", "block_coursefeedback");
            for($i=0;$i<$bound;$i++){
                $headrow[$i+1] = $i+1;
            }
        } else {
            $scaletexts = get_config('block_coursefeedback','scaletexts');
            $scales = explode(',',$scaletexts);
            $scalesize = count($scales);
            $headrow[0] = get_string("download_thead_questions", "block_coursefeedback");
            for($i=0;$i<$scalesize;$i++){
                $headrow[$i+1] = $scales[$i];
            }
        }
        array_push($headrow,get_string('table_html_average', 'block_coursefeedback'),
                get_string('table_html_votes', 'block_coursefeedback'),
                get_string('table_html_nochoice', 'block_coursefeedback'),
            );
        $this->csvexportwriter->add_data($headrow);

        // Get the counted answers for each question and each answer possibility
        $qanswercounts = block_coursefeedback_get_qanswercounts($courseid, $feedbackid);

        $questions = block_coursefeedback_get_questions_by_language($feedbackid, [current_language()],
            CFB_QUESTIONTYPE_SCHOOLGRADE);
            
        $course = $DB->get_record("course", ["id" => $courseid]);
        
        $answers = [$course->idnumber];
        foreach ($questions as $question) {
            // Put questionstring in front of $answerdata and add the data to the csv file
            if ($qanswercounts[$question->questionid]) {
                $answersdata = $qanswercounts[$question->questionid];
                array_unshift($answersdata, $question->question);
                $answers = array_merge($answers,$answersdata);
                $this->csvexportwriter->add_data($answers);
            } else {
		$this->csvexportwriter->add_data();
            }
        }

        // Start the download
        $this->csvexportwriter->download_file();
    }
}

/**
 * Export essay answers for a given course.
 *
 * @package block_coursefeedback
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
        /*$headrow = [
            format_string($course->idnumber),
            get_string("pluginname", "block_coursefeedback"),
            block_coursefeedback_get_feedbackname($feedbackid),
            get_string("questiontype_essay", "block_coursefeedback") . " "
                . get_string("resultspage_title", "block_coursefeedback"),
        ];*/
        $feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);
        $this->csvexportwriter->add_data([
            'Feedbackid: ' . $feedback->id,
            'Feedbackname: ' . $feedback->name,
        ]);
        //$this->csvexportwriter->add_data($headrow);

        // Get needed questions.
        $questions = block_coursefeedback_get_questions_by_language(
                $feedbackid,
                current_language(),
                CFB_QUESTIONTYPE_ESSAY,
                "questionid",
                "questionid,question",
                $questionid
        );
        // Output headers
        $data = [];
        array_push($data,'Question number', get_string('download_thead_questions','block_coursefeedback'),
        get_string('course'), 'Answer');
        $this->csvexportwriter->add_data($data);
        // Insert all textanswers for each question.
        foreach ($questions as $question) {
            $questionnumber = 0;
            $answerdata[] = ++$questionnumber;
            $answerdata[] = format_string($question->question);
            //$this->csvexportwriter->add_data($answerdata);

            // Get textanswers for question.
            $answers = $DB->get_records('block_coursefeedback_textans', ['course' => $courseid,
                'coursefeedbackid' => $feedbackid,
                'questionid' => $question->questionid], 'id', 'id,textanswer');
            foreach($answers as $answer) {
                array_push($answerdata,$course->idnumber,block_coursefeedback_format_essay($answer->textanswer));
		$this->csvexportwriter->add_data($answerdata);
                //$this->csvexportwriter->add_data($answersdata);
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
 * @package block_coursefeedback
 * @copyright innoCampus, TU Berlin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ranking_exporter {
    protected $csvexportwriter;

    public function __construct() {
        $this->csvexportwriter = new csv_export_writer();
    }

    public function create_file($feedbackid, $questionid = 0, $mode=1) {
        global $DB;
        $filename = clean_param(get_string("download_html_filename", "block_coursefeedback")
            . date("_Y-m-d-H-i"), PARAM_FILE);
        $this->csvexportwriter->set_filename($filename);

        $feedback = $DB->get_record("block_coursefeedback", ["id" => $feedbackid]);
        $this->csvexportwriter->add_data([
            'Feedbackid: ' . $feedback->id,
            'Feedbackname: ' . $feedback->name,
        ]);
        if($mode==1){ // ranking questions
        // Get all questions
        $questions = block_coursefeedback_get_questions_by_language($feedback->id, [current_language()],
                CFB_QUESTIONTYPE_SCHOOLGRADE);

        if ($questionid != 0) {
            // Only display one question....
            $questions = array_filter($questions, function ($q) use ($questionid) {
                return $q->questionid == $questionid;
            });
        }
        
        // Output headings.
        $data = [];
        array_push($data,'Question number', get_string('download_thead_questions','block_coursefeedback'),
        get_string('course'), get_string('numusers','block_coursefeedback'));
        $scaleType = get_config('block_coursefeedback','scale');
	    if($scaleType === 'Classic') {
	        array_push($data,get_string('notif_emoji_super', 'block_coursefeedback'),
                get_string('notif_emoji_good', 'block_coursefeedback'),
                get_string('notif_emoji_ok', 'block_coursefeedback'),
                get_string('notif_emoji_neutral', 'block_coursefeedback'),
                get_string('notif_emoji_bad', 'block_coursefeedback'),
                get_string('notif_emoji_superbad', 'block_coursefeedback'),
            );
	    } else if($scaleType === 'Numeric') {
	        $numvalues = get_config('block_coursefeedback','scalenumber'); // Gets the number of values from config
	        $data = array_merge($data,range(1,$numvalues));
	    } else {
	        $data = array_merge($data,explode(',',get_config('block_coursefeedback','scaletexts'))); // Gets the values from config
	    }
	    array_push($data,get_string('table_html_average', 'block_coursefeedback'),
            get_string('table_html_votes', 'block_coursefeedback'),
            get_string('table_html_nochoice', 'block_coursefeedback'),
        );
        $this->csvexportwriter->add_data($data);
        
        // Output data
        foreach ($questions as $question) {
            $questionnumber = 0;
            $courses = block_coursefeedback_get_courserankings($question->questionid, $feedbackid);
            $answerdata[] = ++$questionnumber;
            $answerdata[] = $question->question;
            foreach ($courses as $course) {
                // Forces to select some fields, since the sql query is hard-coded
                $answerdata[] = $course->idnumber;
                $answerdata[] = $course->enroleduserssum;
                if($scaleType === 'Classic') {
                    $answerdata[] = $course->one;
                    $answerdata[] = $course->two;
                    $answerdata[] = $course->three;
                    $answerdata[] = $course->four;
                    $answerdata[] = $course->five;
                    $answerdata[] = $course->six;
                } else if($scaleType === 'Numeric') {
                    $answerdata[] = $course->one;
                    $answerdata[] = $course->two;
                    $answerdata[] = $course->three;
                    $answerdata[] = $course->four;
                } else {
                    $answerdata[] = $course->one;
                    $answerdata[] = $course->two;
                    $answerdata[] = $course->three;
                    $answerdata[] = $course->four;
                    $answerdata[] = $course->five;
                }
                $answerdata[] = $course->avfeedbackresult;
                $answerdata[] = $course->adjanswerstotal;
                $answerdata[] = $course->abstentions;
                $this->csvexportwriter->add_data($answerdata);
            }
        }
        $this->csvexportwriter->download_file();
        } else { // essay questions
        $this->csvexportwriter->set_filename(get_string("download_html_filename", "block_coursefeedback")
            . "_" . get_string("questiontype_essay", "block_coursefeedback") . date("_Y-m-d-H-i"));
	    $questions = block_coursefeedback_get_questions_by_language(
                $feedbackid,
                current_language(),
                CFB_QUESTIONTYPE_ESSAY);

        // Output header
        $data = [];
        array_push($data,'Question number', get_string('download_thead_questions','block_coursefeedback'),
        get_string('course'), get_string('numusers','block_coursefeedback'));
        $this->csvexportwriter->add_data($data);
        // Insert all textanswers for each question.
        foreach ($questions as $question) {
            $questionnumber = 0;
            $answerdata[] = ++$questionnumber;
            $answerdata[] = format_string($question->question);
            /*$questiondata = [
                'Course',get_string("download_thead_questions", "block_coursefeedback")
                ." id: ",
                format_string($question->question)
            ];*/

            // Get textanswers for question.
            $courses = block_coursefeedback_get_courseessay($question->questionid, $feedbackid);
            foreach ($courses as $course) {
		        array_push($answerdata,$course->idnumber,$question->questionid,block_coursefeedback_format_essay($course->textanswer));
		        $this->csvexportwriter->add_data($answerdata);
	        }
        }
	    $this->csvexportwriter->download_file();
    }
    }
}
