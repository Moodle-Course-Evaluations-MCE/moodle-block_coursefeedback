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
 * Functions and classes for block management
 *
 * @package    block_coursefeedback
 * @copyright  2023 Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2023 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



define("CFB_QUESTIONTYPE_SCHOOLGRADE", 1);
define("CFB_QUESTIONTYPE_ESSAY", 2);

/**
 * Gets a list of questiontypes.
 *
 * @param int $type The specific type of question to get. If 0, returns all question types.
 * @return array Array of all question types, an array with a single type, or an empty array if the type is invalid.
 */
function get_question_types($type = 0) {
    $questiontypes = [
        CFB_QUESTIONTYPE_SCHOOLGRADE => get_string('questiontype_schoolgrades', 'block_coursefeedback'),
        CFB_QUESTIONTYPE_ESSAY => get_string('questiontype_essay', 'block_coursefeedback'),
    ];
    if ($type === 0) {
        // The $type is not specified in funcion call -> all questiontypes are returned
        return $questiontypes;
    } else if (array_key_exists($type, $questiontypes)) {
        // Return the specified question $type
        return [$type => $questiontypes[$type]];
    }
    return [];
}

/**
 * Adds or removes the coursefeedbackblock in all courses
 */
function install_and_remove_block() {
    global $DB;
    // Check the current setting.
    $config = get_config("block_coursefeedback");
    $globalenablesetting = $config->global_enable;
    // Check if the setting contradicts the block installation state.
    $instances = $DB->get_records('block_instances', ['blockname' => 'coursefeedback']);
    if (!empty($instances)) {
        // The block exists.
        if ($globalenablesetting == 0) {
            // Remove all Blockinstances.
            foreach ($instances as $instance) {
                blocks_delete_instance($instance);
            }
        }
    } else {
        // The block doesn't exist.
        if ($globalenablesetting == 1) {
            // Add the block.
            $systemcontext = context_system::instance();
            $page = new moodle_page();
            $page->set_context($systemcontext);
            $page->blocks->add_region(BLOCK_POS_RIGHT);
            $page->blocks->add_block('coursefeedback',
                BLOCK_POS_RIGHT, 0, true, 'course-view-*');

            // Disable feedbacks for now.
            set_config("active_feedback", 0, "block_coursefeedback");
        }
    }
}

/**
 * Returns the coursefeedback results for all courses and a given feedback and question
 * Returns only the results for a specific course if $specificcourseid is provided
 *
 * @param int $questionid
 * @param int $coursefeedbackid
 * @param int $answerlimit Minimum of answers given to the specific question (courses with less are not returned)
 * @param int $showperpage Limits the results to a specific number
 * @param int $page Defines together with $showperpage which part of the results is returned
 * @return array of objects of courses and the results for the given $questionid as follows:
 * [ obj1 { ["courseid"]=>int
 *          ["enroleduserssum"]=>int    - Users enrolled in the course
 *          ["shortname"]=>string       - Shortname of the Course
 *          ["category"]=>int           - Categoryid of the course
 *          ["path"]=>string            - Categorpath of the course
 *           --- Following the amount of votes for each option (1 to 6, where one is the best and 6 the worst) ----
 *          ["one"]=>int ["two"]=>int ["three"]=>int ["four"]=>int ["five"]=>int ["six"]=>int
 *          ["avfeedbackresult"]=>int   - The average of all counted votes (excludes abstentions)
 *          ["adjanswerstotal"]=>int    - The Amount of counted votes (excludes abstentions)
 *          ["abstentions"]=>int }      - The Amount of abstentions
 * ... ]
 * @throws \moodle_exception
 */
function block_coursefeedback_get_courserankings(
        $questionid, $coursefeedbackid, $specificcourseid = 0,  $answerlimit = 0, $showperpage = 0, $page = 0) {
    global $DB;
    // Get courseids and the amount of answers in this course for the current question.
    $params = [
        'feedbackid' => $coursefeedbackid,
        'feedbackid2' => $coursefeedbackid,
        'questionid' => $questionid,
        'questionid2' => $questionid,
        'answerlimit' => $answerlimit,
        'specificcourseid' => $specificcourseid,
        'specificcourseid2' => $specificcourseid,
    ];
    $sql = "
        SELECT course.courseid, cenrol.enroleduserssum, c.shortname, c.category, cc.path,  
               answer.one, answer.two, answer.three, answer.four, answer.five, answer.six, 
               ROUND((CAST(answer.answersum as decimal(10,2)) / (NULLIF((course.answerstotal - abstentions), 0))), 3) as avfeedbackresult,
               (course.answerstotal - abstentions) as adjanswerstotal, answer.abstentions
    -- Initially get all courses with answers for the specific question and count the answers --
          FROM ( SELECT course as courseid, count(*) as answerstotal 
                   FROM {block_coursefeedback_answers}
                  WHERE questionid = :questionid 
                    AND coursefeedbackid = :feedbackid
               GROUP BY course
                 HAVING count(*) > :answerlimit ) course
    -- Count the amount of users enrolled in each course --    
     LEFT JOIN ( SELECT ce.courseid, SUM(users) as enroleduserssum 
                   FROM ( SELECT enrol.id, enrol.courseid, userenrolments.users 
                            FROM {enrol} enrol
                            JOIN ( SELECT enrolid, COUNT(*) AS users 
                                     FROM {user_enrolments}
                                 GROUP BY enrolid ) userenrolments ON enrol.id = userenrolments.enrolid    
                        ) ce
                 GROUP BY ce.courseid ) cenrol ON cenrol.courseid = course.courseid
    -- Join the rest of the coursefields as c --    
     LEFT JOIN {course} c ON course.courseid = c.id
    -- Join the course category fields for each course --
     LEFT JOIN {course_categories} cc ON cc.id = c.category
    -- Join the calculated answers for each course --
     LEFT JOIN ( SELECT course, 
                        SUM(CASE WHEN answer = 1 THEN 1 ELSE 0 END) AS one,
                        SUM(CASE WHEN answer = 2 THEN 1 ELSE 0 END) AS two,
                        SUM(CASE WHEN answer = 3 THEN 1 ELSE 0 END) AS three,
                        SUM(CASE WHEN answer = 4 THEN 1 ELSE 0 END) AS four,
                        SUM(CASE WHEN answer = 5 THEN 1 ELSE 0 END) AS five,
                        SUM(CASE WHEN answer = 6 THEN 1 ELSE 0 END) AS six,
                        SUM(CASE WHEN answer = 0 THEN 1 ELSE 0 END) AS abstentions,
                        SUM(answer) AS answersum
                   FROM {block_coursefeedback_answers}
                  WHERE coursefeedbackid = :feedbackid2 
                    AND questionid = :questionid2
               GROUP BY course ) answer ON course.courseid = answer.course
         WHERE (:specificcourseid = 0 OR c.id = :specificcourseid2)";

    if ($showperpage != 0 && $page < 0) {
        $limitnum = $showperpage * ($page - 1);
        $limitfrom = $showperpage;
        $courserecords = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

    } else {
        $courserecords = $DB->get_records_sql($sql, $params);

    }
    return $courserecords;
}

/**
 * Formats an essay answer with proper newlines that are compatible with HTML display and
 * CSV export.
 *
 * @param string The essay answer
 * @return string The essay answer in which all newline characters have been replaced by HTML
 * newline <br> for proper display and easy identification in CSV files.
 */
function block_coursefeedback_format_essay($answer):string {
    return str_replace(["\r\n","\n","\r"],'',format_text($answer,FORMAT_PLAIN,['newline' => false])); 

/**
 * Returns the coursefeedback results essays for all courses and a given feedback and question
 * Returns only the results for a specific course if $specificcourseid is provided
 *
 * @param int $questionid
 * @param int $coursefeedbackid
 * @param int $answerlimit Minimum of answers given to the specific question (courses with less are not returned)
 * @param int $showperpage Limits the results to a specific number
 * @param int $page Defines together with $showperpage which part of the results is returned
 * @return array of objects of courses and the results for the given $questionid as follows:
 * [ obj1 { ["courseid"]=>int
 *          ["enroleduserssum"]=>int    - Users enrolled in the course
 *          ["shortname"]=>string       - Shortname of the Course
 *          ["category"]=>int           - Categoryid of the course
 *          ["path"]=>string            - Categorpath of the course
 *           --- Following the amount of votes for each option (1 to 6, where one is the best and 6 the worst) ----
 *          ["one"]=>int ["two"]=>int ["three"]=>int ["four"]=>int ["five"]=>int ["six"]=>int
 *          ["avfeedbackresult"]=>int   - The average of all counted votes (excludes abstentions)
 *          ["adjanswerstotal"]=>int    - The Amount of counted votes (excludes abstentions)
 *          ["abstentions"]=>int }      - The Amount of abstentions
 * ... ]
 * @throws \moodle_exception
 */
function block_coursefeedback_get_courseessay(
        $questionid, $coursefeedbackid, $specificcourseid = 0,  $answerlimit = 0, $showperpage = 0, $page = 0) {
    global $DB;
    // Get courseids and the amount of answers in this course for the current question.
    $params = [
        'feedbackid' => $coursefeedbackid,
        'feedbackid2' => $coursefeedbackid,
        'questionid' => $questionid,
        'questionid2' => $questionid,
        'answerlimit' => $answerlimit,
        'specificcourseid' => $specificcourseid,
        'specificcourseid2' => $specificcourseid
    ];
    $sql = "
	select a.id as answerid, c.id, a.textanswer from mdl_course c, mdl_block_coursefeedback_textans a where a.course=c.id and a.coursefeedbackid=:feedbackid and a.questionid=:questionid";
    if ($showperpage != 0 && $page < 0) {
        $limitnum = $showperpage * ($page -1);
        $limitfrom = $showperpage;
        $courserecords = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    } else {
        $courserecords = $DB->get_records_sql($sql, $params);
    }
    return $courserecords;
}
