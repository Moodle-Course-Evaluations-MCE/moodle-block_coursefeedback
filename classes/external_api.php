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
 * External API.
 *
 * @package     block_coursefeedback
 * @copyright   2022 onwards Felix Di Lenarda (@ innoCampus, TU Berlin)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/webservice/externallib.php");
require_once(__DIR__ . "/../lib.php");
require_once(__DIR__ . "/../locallib.php");

use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use context_system;
use stdClass;

/**
 * Block coursefeedback external functions
 *
 * @package     block_coursefeedback
 * @copyright   2022 onwards Felix Di Lenarda (@ innoCampus, TU Berlin)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_api extends \external_api {

    /**
     * Returns description of answer_question_and_get_new parameters.
     *
     * @return external_function_parameters
     */
    public static function answer_question_and_get_new_parameters() {
        $courseid = new external_value(
            PARAM_INT,
            'Courseid',
            VALUE_REQUIRED
        );
        $feedback = new external_value(
            PARAM_INT,
            'Feedback',
            VALUE_OPTIONAL
        );
        $essay = new external_value(
            PARAM_TEXT,
            'Feedback',
            VALUE_OPTIONAL
        );
        $feedbackid = new external_value(
            PARAM_INT,
            'FeedbackID',
            VALUE_REQUIRED
        );
        $questionid = new external_value(
            PARAM_INT,
            'QuestionID',
            VALUE_REQUIRED
        );
        $params = [
            'courseid' => $courseid,
            'feedback' => $feedback,
            'essay' => $essay,
            'feedbackid' => $feedbackid,
            'questionid' => $questionid,
        ];

        return new \external_function_parameters($params);
    }

    /**
     * Saves Feedback answer and returns the next feedback question if there is one.
     *
     * @param int $courseid
     * @param int $feedback given schoolgrade answer
     * @param string $essay given essay answer
     * @param int $feedbackid
     * @param int $questionid
     * @return array The next questiondetails
     * @throws moodle_exception If question doesn't exist or the wron questiontypeanswer was given
     */
    public static function answer_question_and_get_new($courseid, $feedback, $essay, $feedbackid, $questionid) {
        global $DB, $USER;

        // Validate parameter
        $params = self::validate_parameters(self::answer_question_and_get_new_parameters(),
            [
                'courseid' => $courseid,
                'feedback' => $feedback,
                'essay' => $essay,
                'feedbackid' => $feedbackid,
                'questionid' => $questionid,
            ]
        );

        // Security checks
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/coursefeedback:evaluate', $context);
        $config = get_config("block_coursefeedback");

        // Check if FB and question exist and given questiontype answer matches.
        $question = block_coursefeedback_get_questions_by_language(
                $params['feedbackid'],
                current_language(),
                null,
                'questionid',
                'questionid,questiontype',
                $params['questionid']);
        // There must be exactly one matching question.
        if (count($question) != 1) {
            throw new \moodle_exception('except_no_question', 'block_coursefeedback');
        } else if ((isset($params['essay']) && reset($question)->questiontype != CFB_QUESTIONTYPE_ESSAY)
                || (isset($params['feedback']) && reset($question)->questiontype != CFB_QUESTIONTYPE_SCHOOLGRADE)) {
            // The given answerparam has to match the questiontype.
            throw new \moodle_exception('except_wrong_questiontype', 'block_coursefeedback');
        }

        // Check if answer exist already
        if ($DB->record_exists("block_coursefeedback_uidansw",
                [
                    "userid" => $USER->id,
                    "course" => $params['courseid'],
                    "questionid" => $params['questionid'],
                    "coursefeedbackid" => $params['feedbackid'],
                ])) {
            throw new \moodle_exception('except_answer_exist', 'block_coursefeedback');
        }
        // Check if FB active and coursestart is ok
        if ($config->active_feedback != $params['feedbackid']
                || !block_coursefeedbck_coursestartcheck_good($config, $params['courseid'])
                || $config->active_feedback == 0) {
            throw new \moodle_exception('except_not_active', 'block_coursefeedback');
        }

        // Check if more than one coursefeedback blocks exist in the course or block is hidden
        $sql = "SELECT bp.visible
                  FROM {block_positions} bp
                  JOIN {block_instances} bi ON bp.blockinstanceid = bi.id
                 WHERE bp.contextid = :contextid 
                       AND bi.blockname = :blockname";
        $sqlparams = [
            'contextid' => $context->id,
            'blockname' => 'coursefeedback',
        ];
        $blocks = $DB->get_records_sql($sql, $sqlparams);
        if (count($blocks) > 1) {
            throw new \moodle_exception('except_block_duplicate', 'block_coursefeedback');
        } else if (count($blocks) == 1) {
            if (array_pop($blocks)->visible != 1) {
                throw new \moodle_exception('except_block_hidden', 'block_coursefeedback');
            }
        }

        $uidtoans = new stdClass();
        $uidtoans->userid = $USER->id;
        $uidtoans->course = $params['courseid'];
        $uidtoans->coursefeedbackid = $params['feedbackid'];
        $uidtoans->questionid = $params['questionid'];

        // Answer received, decide which answer type and then save in DB.
        $result = ['saved' => false];
        $record = new stdClass();
        $record->course = $params['courseid'];
        $record->coursefeedbackid = $params['feedbackid'];
        $record->questionid = $params['questionid'];
        $record->timemodified = time();

        // Decide which answer type and then save in respective DB-table.
        if ( isset($params['essay'])) {
            $record->textanswer = $params['essay'];
            $dbtrans = $DB->start_delegated_transaction();
            if ($DB->insert_record("block_coursefeedback_textans", $record, false, false)
                && $DB->insert_record("block_coursefeedback_uidansw", $uidtoans, false, false)) {
                $result['saved'] = true;
            }
            $dbtrans->allow_commit();
        } else if ( isset($params['feedback'])) {
            $record->answer = $params['feedback'];
            $dbtrans = $DB->start_delegated_transaction();
            if ($DB->insert_record("block_coursefeedback_answers", $record, false, false)
                && $DB->insert_record("block_coursefeedback_uidansw", $uidtoans, false, false)) {
                $result['saved'] = true;
            }
            $dbtrans->allow_commit();
        }

        // Check if there are questions left and return the resulting infos.
        if (null !== ($openquestions = block_coursefeedback_get_open_question())) {
            $result['questionstotal'] = $openquestions['questionsum'];
            $result['nextquestion'] = $openquestions['currentopenqstn']->question;
            $result['nextquestionid'] = $openquestions['currentopenqstn']->questionid;
            $result['nextquestiontype'] = $openquestions['currentopenqstn']->questiontype;
            $result['feedbackid'] = $openquestions['currentopenqstn']->coursefeedbackid;
        } else {
            $result['questionstotal'] = null;
            $result['nextquestion'] = null;
            $result['nextquestionid'] = null;
            $result['nextquestiontype'] = null;
            $result['feedbackid'] = null;
        }
        return $result;
    }

    /**
     * Returns description of answer_question_and_get_new result values.
     *
     * @return external_single_structure
     */
    public static function answer_question_and_get_new_returns() {
        $saved = new external_value(
            PARAM_BOOL,
            'Given feeback saved',
            VALUE_REQUIRED
        );
        $questionstotal = new external_value(
            PARAM_INT,
            'How many questions are in this feedback',
            VALUE_REQUIRED
        );
        $nextquestion = new external_value(
            PARAM_TEXT,
            'What is the next question',
            VALUE_REQUIRED
        );
        $nextquestionid = new external_value(
            PARAM_INT,
            'How many questions are in this feedback',
            VALUE_REQUIRED
        );
        $nextquestiontype = new external_value(
            PARAM_INT,
            'What question type is next',
            VALUE_REQUIRED
        );
        $feedbackid = new external_value(
            PARAM_INT,
            'FeedbackID',
            VALUE_REQUIRED
        );
        $params = new external_single_structure([
            'saved' => $saved,
            'questionstotal' => $questionstotal,
            'nextquestion' => $nextquestion,
            'nextquestionid' => $nextquestionid,
            'nextquestiontype' => $nextquestiontype,
            'feedbackid' => $feedbackid,
        ]);
        return $params;
    }

    /**
     * Returns description of get_feedback_questions parameters.
     *
     * @return external_function_parameters
     */
    public static function get_feedback_questions_parameters() {
        $feedbackid = new external_value(
            PARAM_INT,
            'FeedbackID',
            VALUE_REQUIRED
        );
        $params = [
            'feedbackid' => $feedbackid,
        ];

        return new \external_function_parameters($params);
    }

    /**
     * Returns the feedback questions in the current language for the Rankingformoptions.
     *
     * @param int $feedbackid The feedback id
     * @param int $questionid The question id
     * @return array The feedbackquestions
     */
    // TODO das Anzeigen der Rankings in der Weboberfläche ist nicht fertig implementiert -> CSV Download nutzen
    // TODO noch die frage übergeben und nur die fragen der jeweiligen sprache holen
    public static function get_feedback_questions($feedbackid) {

        // Validate parameter
        $params = self::validate_parameters(self::get_feedback_questions_parameters(),
            [ 'feedbackid' => $feedbackid ]
        );

        // Security checks
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/coursefeedback:managefeedbacks', $context);

        $currentlang = [current_language()];
        $questions = block_coursefeedback_get_questions_by_language($params['feedbackid'], $currentlang,
                CFB_QUESTIONTYPE_SCHOOLGRADE,
                "questionid",
                "id,questionid,question,coursefeedbackid,language");
        $result = ['questions' => []];
        foreach ($questions as $question) {
            array_push($result['questions'], (array) $question);
        }
        return $result;
    }

    /**
     * Returns description of get_feedback_questions result values.
     *
     * @return external_single_structure
     */
    // TODO questionid und coursefeedbackid überflüssig!? (optionvalue->question->id)
    // => wir müssten mit der id arbeiten und dann in get_ranking_for_question wieder auf die  feedback/questionid zugreifen
    public static function get_feedback_questions_returns() {
        $questions = new external_multiple_structure (
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Id'),
                'questionid' => new external_value(PARAM_INT, 'Questionid'),
                'question' => new external_value(PARAM_TEXT, 'Question'),
                'coursefeedbackid' => new external_value(PARAM_INT, 'CFId'),
                'language' => new external_value(PARAM_TEXT, 'lang'),
            ])
        );
        $params = new external_single_structure([
            'questions' => $questions,
        ]);
        return $params;
    }

    /**
     * Returns description of get_ranking_for_question parameters.
     *
     * @return external_function_parameters
     */
    // TODO das Anzeigen der Rankings in der Weboberfläche ist nicht fertig implementiert -> CSV Download nutzen
    public static function get_ranking_for_question_parameters() {
        $questionid = new external_value(
            PARAM_INT,
            'question.questionid',
            VALUE_REQUIRED
        );
        $feedback = new external_value(
            PARAM_INT,
            'feedback.id',
            VALUE_REQUIRED
        );
        $answerlimit = new external_value(
            PARAM_INT,
            '$answerlimit',
            VALUE_REQUIRED
        );
        $showperpage = new external_value(
            PARAM_INT,
            '$showperpage',
            VALUE_REQUIRED
        );
        $page = new external_value(
            PARAM_INT,
            '$page',
            VALUE_REQUIRED
        );
        $params = [
            'questionid' => $questionid,
            'feedback' => $feedback,
            'answerlimit' => $answerlimit,
            'showperpage' => $showperpage,
            'page' => $page,
        ];
        return new \external_function_parameters($params);
    }
    /**
     * Returns the rankings for the given question.
     *
     * @param int $questionid
     * @param int $answerlimit TODO implement
     * @param int $showperpage TODO implement
     * @param int $page TODO implement
     * @return array
     */
    // TODO das Anzeigen der Rankings in der Weboberfläche ist nicht fertig implementiert -> CSV Download nutzen
    public static function get_ranking_for_question($questionid, $feedback, $answerlimit, $showperpage, $page) {
        global $DB;

        // Validate parameter
        $params = self::validate_parameters(self::get_ranking_for_question_parameters(),
            [
                'questionid' => $questionid,
                'feedback' => $feedback,
                'answerlimit' => $answerlimit,
                'showperpage' => $showperpage,
                'page' => $page,
            ]
        );

        // Security checks
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/coursefeedback:managefeedbacks', $context);

        // TODO hole alle kurse mit mehr antworten als answerlimit
        $courses = block_coursefeedback_get_courserankings($params['questionid'], $params['feedback'], 0,
            $params['answerlimit'], $params['showperpage'], $params['page']);
        $result = ['ranking' => $courses];
        return $result;
    }

    /**
     * Returns description of get_ranking_for_question result values.
     *
     * @return external_single_structure
     */
    // TODO das Anzeigen der Rankings in der Weboberfläche ist nicht fertig implementiert -> CSV Download nutzen
    public static function get_ranking_for_question_returns() {
        $ranking = new external_multiple_structure (
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'courseid'),
                'enroleduserssum' => new external_value(PARAM_INT, 'enroleduserssum'),
                'shortname'    => new external_value(PARAM_TEXT, 'shortname'),
                'category' => new external_value(PARAM_INT, 'category'),
                'path' => new external_value(PARAM_TEXT, 'category path'),
                'one' => new external_value(PARAM_INT, 'one'),
                'two' => new external_value(PARAM_INT, 'two'),
                'three' => new external_value(PARAM_INT, 'three'),
                'four' => new external_value(PARAM_INT, 'four'),
                'five' => new external_value(PARAM_INT, 'five'),
                'six' => new external_value(PARAM_INT, 'six'),
                'avfeedbackresult' => new external_value(PARAM_FLOAT, 'Average feedback result '),
                'adjanswerstotal' => new external_value(PARAM_INT, 'Answers total without abstentions'),
                'abstentions' => new external_value(PARAM_INT, 'Abstentions'),
            ])
        );
        $params = new external_single_structure([
            'ranking' => $ranking,
        ]);
        return $params;

    }
}
