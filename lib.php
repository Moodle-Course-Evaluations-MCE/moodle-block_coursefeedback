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
 * Helper functions
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const COURSEFEEDBACK_DEFAULT = "DEFAULT";
const COURSEFEEDBACK_ALL = "ALL";

/**
 * Fixes gaps in question id ordering for a given feedback.
 *
 * @param int $feedbackid The feedback instance ID.
 * @return bool true
 * @throws dml_exception A DML specific exception is thrown for any errors.
 */
function block_coursefeedback_order_questions(int $feedbackid): bool {
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    try {
        // Get the current offset value.
        $offset = block_coursefeedback_get_questionid($feedbackid);

        // Shift all affected questions to a temporary offset.
        // We need this to make sure the (questionid, feedbackid, laguage) unique constraint is not violated
        $sql = "UPDATE {block_coursefeedback_questns}
                   SET questionid = questionid + :offset
                 WHERE coursefeedbackid = :feedbackid";

        $DB->execute($sql, [
            'offset'      => $offset,
            'feedbackid'  => $feedbackid,
        ]);

        // "mapping" generates a new consecutive ID (newquestionid) for each distinct questionid by using 'row_number()'
        // Then, the UPDATE statement joins the mapping with the target table to set:
        // - questionid = newquestionid
        // for all rows belonging to the given feedback instance.
        $sqlupdate = "WITH mapping AS (
                          SELECT questionid, row_number() OVER (ORDER BY questionid) AS newquestionid
                            FROM (
                                SELECT DISTINCT questionid
                                  FROM {block_coursefeedback_questns}
                                 WHERE coursefeedbackid = :feedbackid1
                            ) sub
                      )
                      UPDATE {block_coursefeedback_questns} t
                         SET questionid = mapping.newquestionid
                        FROM mapping
                       WHERE t.coursefeedbackid = :feedbackid2
                             AND t.questionid = mapping.questionid";

        $params = [
            'feedbackid1' => $feedbackid,
            'feedbackid2' => $feedbackid,
        ];

        $result = $DB->execute($sqlupdate, $params);
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
    $transaction->allow_commit();

    return $result;
}

/**
 * If the function returns a negative number, it indicates a false validation (i.e. use of blacklisted characters).
 *
 * @param string $feedbackname
 * @param string $heading
 * @param string $infotext
 * @param bool $returnid Should the id of the newly created record entry be returned?
 * @return int|bool - record id or false on failure.
 */
function block_coursefeedback_insert_feedback($feedbackname, $heading = null, $infotext = null, $infotextformat = null,
        $returnid = true) {
    global $DB;

    if (strpos($feedbackname, ";") === false) {
        $record = new stdClass();
        $record->name = $feedbackname;
        $record->timemodified = time();
        $record->heading = $heading;
        $record->infotext = $infotext;
        $record->infotextformat = $infotextformat;

        return $DB->insert_record("block_coursefeedback", $record, $returnid);
    } else {
        return -1;
    }
}

/**
 * If the return is a negative number, it indicates a false validation (i.e. use of blacklisted characters).
 *
 * @param int $feedbackid
 * @param string $feedbackname
 * @param string $heading
 * @param string $infotext
 * @return int|bool - Success of operation.
 */
function block_coursefeedback_edit_feedback($feedbackid, $feedbackname, $heading = null, $infotext = null, $infotextformat = null) {
    global $DB;

    if (strpos($feedbackname, ";")) {
        return -1;
    }

    if ($record = $DB->get_record("block_coursefeedback", ["id" => $feedbackid])) {
        $record->name = $feedbackname;
        $record->timemodified = time();
        $record->heading = $heading;
        $record->infotext = $infotext;
        $record->infotextformat = $infotextformat;

        return clean_param($DB->update_record("block_coursefeedback", $record), PARAM_BOOL);
    } else {
        return false;
    }
}

/**
 * If the function returns a negative number, it indicates a false validation (i.e. use of blacklisted characters).
 *
 * @param int $oldfbid
 * @param int $fbname
 * @param string $heading
 * @param string $infotext
 * @return int|false - $newid or false.
 */
function block_coursefeedback_copy_feedback($oldfbid, $fbname, $heading = null, $infotext = null, $infotextformat = null) {
    global $DB;
    $oldfbid = clean_param($oldfbid, PARAM_INT);
    $newid = block_coursefeedback_insert_feedback($fbname, $heading, $infotext, $infotextformat);

    if ($newid === -1) {
        return -1;
    } else if ($newid > 0 && $questions = $DB->get_records("block_coursefeedback_questns", ["coursefeedbackid" => $oldfbid])) {
        $a = $newid;
        foreach ($questions as $question) {
            if (!block_coursefeedback_insert_question(
                    $question->question, $newid, $question->questionid, $question->language, $question->questiontype)) {
                // If one fails the whole operation fails.
                $a = false;
                // Remove inserted and not correctly duplicated fb.
                block_coursefeedback_delete_feedback($newid);
                break;
            }
        }
    }
    return $a;
}

/**
 * @param int $feedbackid
 * @return bool Success of operation.
 */
function block_coursefeedback_delete_feedback($feedbackid) {
    global $DB;
    if ($DB->delete_records("block_coursefeedback_answers", ["coursefeedbackid" => $feedbackid])
            && $DB->delete_records("block_coursefeedback_questns", ["coursefeedbackid" => $feedbackid])
            && $DB->delete_records("block_coursefeedback", ["id" => $feedbackid])
            && $DB->delete_records("block_coursefeedback_uidansw", ["coursefeedbackid" => $feedbackid])) {
        // If the first fails, the second won't be executed (because of &&).
        return true;
    } else {
        // If one fails, the whole operation fails.
        return false;
    }
}

/**
 * @param string $question
 * @param int $feedbackid
 * @param int $questionid
 * @param string $language
 * @param int $questiontype
 * @param bool $returnid Return the id of the newly created record? If false, a boolean is returned.
 * @return bool|int
 */
function block_coursefeedback_insert_question($question, $feedbackid, $questionid, $language, $questiontype, $returnid = true) {
    global $DB;

    $feedbackid = intval($feedbackid);
    $questionid = intval($questionid);
    $questiontype = intval($questiontype);
    $language = preg_replace("/[^a-z\_]/", "", strtolower($language));

    if (!$DB->record_exists("block_coursefeedback_questns",
            ["coursefeedbackid" => $feedbackid, "questionid" => $questionid, "language" => $language])) {
        $languages = block_coursefeedback_get_implemented_languages($feedbackid, $questionid, true, true);
        // Ceck if language already exists.
        if ($languages && in_array($language, $languages)) {
            $record = new stdClass();
            $record->question = $question;
            $record->coursefeedbackid = $feedbackid;
            $record->questionid = $questionid;
            $record->language = $language;
            $record->timemodified = time();
            $record->questiontype = $questiontype;
            return $DB->insert_record("block_coursefeedback_questns", $record);
        }
    }

    return false;
}
/**
 * Moves a question within the course feedback block to a new position.
 *
 * @param int $feedbackid    The course feedback identifier.
 * @param int $oldposition   The current position of the question.
 * @param int $newposition   The target position for the question.
 * @return bool            True if the operation was successful.
 * @throws dml_exception   If a database error occurs.
 */
function block_coursefeedback_move_question(int $feedbackid, int $oldposition, int $newposition): bool {
    global $DB;

    if ($oldposition === $newposition) {
        return true;
    }

    $transaction = $DB->start_delegated_transaction();

    try {
        // Reordering questions by updating their questionid values.
        // Making use of a temporary offset to avoid unique constraint conflicts and performing
        // the update in a transaction.

        // Get the current offset value.
        $offset = block_coursefeedback_get_questionid($feedbackid);

        // Step 1: Shift all affected questions to a temporary offset.
        $sql = "UPDATE {block_coursefeedback_questns}
                   SET questionid = questionid + :offset
                 WHERE coursefeedbackid = :feedbackid
                       AND questionid BETWEEN LEAST(:oldposition::bigint, :newposition::bigint)
                                     AND GREATEST(:oldposition1::bigint, :newposition1::bigint)";
        $DB->execute($sql, [
            'offset'      => $offset,
            'feedbackid'  => $feedbackid,
            'oldposition' => $oldposition,
            'newposition' => $newposition,
            'oldposition1' => $oldposition,
            'newposition1' => $newposition,
        ]);

        // Step 2: Adjust positions of intermediate questions.
        if ($oldposition < $newposition) {
            // Moving to a higher position: shift intermediate questions one step down, considering the offset.
            $sql = "UPDATE {block_coursefeedback_questns}
                       SET questionid = questionid - (1 + :offset)
                     WHERE coursefeedbackid = :feedbackid
                           AND questionid BETWEEN (:oldposoffset + 1) AND :newposoffset";
        } else if ($oldposition > $newposition) {
            // Moving to a lower position: shift intermediate questions one step up, considering the offset.
            $sql = "UPDATE {block_coursefeedback_questns}
                    SET questionid = questionid + (1 - :offset)
                    WHERE coursefeedbackid = :feedbackid
                      AND questionid BETWEEN :newposoffset AND (:oldposoffset - 1)";
        }
        $DB->execute($sql, [
            'newposoffset' => $newposition + $offset,
            'oldposoffset' => $oldposition + $offset,
            'offset'       => $offset,
            'feedbackid'   => $feedbackid,
        ]);

        // Step 3: Move the target question from its temporary offset position to the new position.
        $sql = "UPDATE {block_coursefeedback_questns}
                   SET questionid = :newposition
                 WHERE coursefeedbackid = :feedbackid
                       AND questionid = :oldposoffset";
        $DB->execute($sql, [
            'oldposoffset' => $oldposition + $offset,
            'newposition'  => $newposition,
            'feedbackid'   => $feedbackid,
        ]);
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
    $transaction->allow_commit();

    return true;
}

/**
 * Updates an existing question in a feedback.
 *
 * This function updates the text, type, and modification time of a question
 * in the feedback if the specified language is supported.
 *
 * @param int $feedbackid
 * @param int $questionid
 * @param string $question The updated question text.
 * @param string $language The language code of the question.
 * @param int $questiontype The typeid of the question.
 * @return bool True if the update was successful, false otherwise.
 */
function block_coursefeedback_update_question($feedbackid, $questionid, $question, $language, $questiontype) {
    global $DB;

    $feedbackid = intval($feedbackid);
    $questionid = intval($questionid);
    $questiontype = intval($questiontype);
    if (in_array($language, block_coursefeedback_get_implemented_languages($feedbackid, $questionid))) {
        $record = $DB->get_record("block_coursefeedback_questns", ["coursefeedbackid" => $feedbackid,
            "questionid" => $questionid,
            "language" => $language]);
        $record->question = $question;
        $record->timemodified = time();
        $record->questiontype = $questiontype;
        return clean_param($DB->update_record("block_coursefeedback_questns", $record), PARAM_BOOL);
    }

    return false;
}

/**
 *
 * @param int $feedbackid
 * @param int $questionid
 * @param String|COURSEFEEDBACK_ALL $language (default is all languages)
 * @return bool Success of operation
 */
function block_coursefeedback_delete_question($feedbackid, $questionid, $language = COURSEFEEDBACK_ALL) {
    global $DB;

    $feedbackid = intval($feedbackid);
    $questionid = intval($questionid);

    if ($language == COURSEFEEDBACK_ALL) {
        $success = $DB->delete_records("block_coursefeedback_questns", ["coursefeedbackid" => $feedbackid,
            "questionid" => $questionid]);
    } else if (array_key_exists($language, get_string_manager()->get_list_of_translations())) {
        $success = $DB->delete_records("block_coursefeedback_questns", ["coursefeedbackid" => $feedbackid,
            "questionid" => $questionid,
            "language" => $language]);
    } else {
        $success = false;
    }
    $success = clean_param($success, PARAM_BOOL);

    if ($success) {
        block_coursefeedback_order_questions($feedbackid);
    }

    return $success;
}

/**
 * @param int $feedbackid
 * @param array|string $language Array of language codes or language code
 * @return int|bool - number of deleted records or false on fail
 */
function block_coursefeedback_delete_questions($feedbackid, $languages) {
    global $DB;

    $feedbackid = intval($feedbackid);

    if (!is_array($languages)) {
        $languages = [$languages];
    } // Ensure array.
    $implemented = block_coursefeedback_get_implemented_languages($feedbackid);
    $conditions = ["coursefeedbackid" => $feedbackid];
    $succeeded = 0;

    foreach ($languages as $langcode) {
        $conditions["language"] = $langcode;
        if (in_array($langcode, $implemented) && $DB->delete_records("block_coursefeedback_questns", $conditions)) {
            $succeeded++;
        }
    }

    if ($succeeded > 0) {
        block_coursefeedback_order_questions($feedbackid);
    }

    return $succeeded;
}

/**
 * @param int $feedbackid
 * @return bool Succes of operation
 */
function block_coursefeedback_delete_answers($feedbackid) {
    global $DB;
    $conditions = ["coursefeedbackid" => intval($feedbackid)];
    $dbtrans = $DB->start_delegated_transaction();
    try {
        $DB->delete_records("block_coursefeedback_uidansw", ["coursefeedbackid" => $feedbackid]);
        $DB->delete_records("block_coursefeedback_answers", $conditions);
        $DB->delete_records("block_coursefeedback_textans", $conditions);
        $dbtrans->allow_commit();
        return true;
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $dbtrans->rollback($e);
        return false;
    }
}

/**
 * Get all language codes for which questions are well-defined (question in default language exists)
 *
 * @param int $feedbackid | COURSEFEEDBACK_DEFAULT
 * @param bool $codesonly
 * @return array Language codes
 */
function block_coursefeedback_get_combined_languages($feedbackid = COURSEFEEDBACK_DEFAULT, $codesonly = true) {
    global $DB;

    // Clean params.
    if ($feedbackid === COURSEFEEDBACK_DEFAULT) {
        $feedbackid = get_config("block_coursefeedback", "active_feedback");
    } else {
        $feedbackid = intval($feedbackid);
    }
    $codesonly = clean_param($codesonly, PARAM_BOOL);

    $count = block_coursefeedback_get_questioncount($feedbackid);
    $select = "coursefeedbackid = :fid GROUP BY language HAVING COUNT(language) = :count";
    $params = ["fid" => $feedbackid, "count" => $count];
    $langs = $DB->get_records_select("block_coursefeedback_questns", $select, $params, "", "language");
    $langs = array_keys($langs);

    if ($langs && !$codesonly) {
        $listoflanguages = get_string_manager()->get_list_of_translations();
        $languages = [];
        foreach ($langs as $langcode) {
            $languages[$langcode] = isset($listoflanguages[$langcode])
                ? $listoflanguages[$langcode]
                : get_string("adminpage_html_notinstalled", "block_coursefeedback", $langcode);
        }
        $langs = $languages;
    }
    return ($langs ? $langs : []);
}

/**
 * @param int $feedbackid
 * @param int $questionid
 * @param bool $codesonly
 * @param bool $inverted
 * @return array - All languages of the feedback, which are listed in database. Array data type depends on input parameters.
 */
function block_coursefeedback_get_implemented_languages($feedbackid, $questionid = null, $langcodesonly = true, $inverted = false) {
    global $DB;

    $feedbackid = intval($feedbackid);

    $sql = "SELECT language
              FROM {block_coursefeedback_questns}
             WHERE coursefeedbackid = :fid ";
    if (is_int($questionid) && $questionid > 0) {
        $sql .= "AND questionid = :qid ";
    }
    $sql .= "GROUP BY language";

    $implemented = $DB->get_fieldset_sql($sql, ["fid" => $feedbackid, "qid" => $questionid]);
    if (!$implemented) {
        $implemented = [];
    }
    $installed = get_string_manager()->get_list_of_translations();

    if ($langcodesonly) {
        $languages = ($inverted)
            ? array_diff(array_keys($installed), $implemented)
            : $implemented;
    } else if ($inverted) { // Case !$langcodesonly && $inverted.
        foreach ($implemented as $i) {
            unset($installed[$i]);
        }
        $languages = $installed;
    } else {
        // Case !$langcodesonly && !$inverted.
        $languages = [];
        foreach ($implemented as $i) {
            $languages[$i] = $installed[$i];
        }
    }

    return $languages;
}

/**
 * Computes the next available (unused) questionid for a given feedback.
 *
 * @param int $feedbackid The feedback ID.
 * @return int The next available question ID.
 * @throws dml_exception If the database query fails.
 */
function block_coursefeedback_get_questionid(int $feedbackid): int {
    global $DB;
    $sql = "SELECT COALESCE(MAX(questionid), 0) + 1 
              FROM {block_coursefeedback_questns} 
             WHERE coursefeedbackid = :feedbackid";
    return $DB->get_field_sql($sql, ["feedbackid" => $feedbackid]);
}

/**
 * Calculates how many questions a feedback has.
 *
 * @param int $feedbackid The feedback ID.
 * @return int The number of questions in this feedback.
 * @throws dml_exception If the database query fails.
 */
function block_coursefeedback_get_questioncount(int $feedbackid): int {
    global $DB;
    $sql = "SELECT COUNT(DISTINCT questionid) 
              FROM {block_coursefeedback_questns} 
             WHERE coursefeedbackid = :feedbackid";
    return $DB->get_field_sql($sql, ["feedbackid" => $feedbackid]);
}

/**
 * @param int $feedbackid - If no record is found or if left blank "untitled" will be returned.
 * @return string - Feedback name.
 */
function block_coursefeedback_get_feedbackname($feedbackid = null) {
    global $DB;

    if (is_number($feedbackid)) {
        $name = $DB->get_field("block_coursefeedback", "name", ["id" => $feedbackid]);
    }

    if (empty($name)) {
        $name = get_string("untitled", "block_coursefeedback");
    }

    return htmlentities($name);
}
/**
 * This function gets the amount of votes for each answeroption (1-6) and each schoolgrade question.
 * It calculates the amount of counted choices, the amount of abstentions and the average rating for each question
 *
 * @param int $courseid
 * @param string $sort
 * @return array - 2-dimensional array of answers, ordered by question id as follows:
 * [ "<questionid>" =>
 *      [ "1" => <answercount>, ..., "6" => <answercount>, "average" =>..., "choicessum" =>..., "abstentions" => ... ]]
 * @throws moodle_exception
 */
function block_coursefeedback_get_qanswercounts($course, $feedbackid) {
    global $DB;
    $answers = [];
    $course = clean_param($course, PARAM_INT);

    if ($course <= 0) {
        throw new moodle_exception("invalidcourseid");
    }
    // Get all the questions of the feedback
    $questions = block_coursefeedback_get_questions_by_language($feedbackid, [current_language()],
            CFB_QUESTIONTYPE_SCHOOLGRADE);
    $params = ["fid" => $feedbackid, "course" => $course];
    // For each question and each answerpossibility, count the amount of the given answers
    foreach ($questions as $question) {
        $choicessum = 0;
        $avsum = 0;
        $questionid = $question->questionid;
        $params["qid"] = $questionid;
        $sql = "SELECT answer,COUNT(*) AS count
                  FROM {block_coursefeedback_answers}
                 WHERE coursefeedbackid = :fid 
                       AND questionid = :qid 
                       AND course = :course
              GROUP BY answer";

        // Create array for the question and fill in zeros for each answeroption
        $answers[$questionid] = array_fill(1, 6, 0);

        $abstentions = 0;
        // If answers for question exist, replace the zero at the right index and calculate average and choicessum
        $results = $DB->get_records_sql($sql, $params);
        foreach ($results as $answer) {
            // Abstentions are not counted for average and choicessum
            if ($answer->answer == 0) {
                $abstentions = $answer->count;
            } else {
                $answers[$questionid][$answer->answer] = $answer->count;
                $choicessum += $answer->count;
                $avsum += $answer->answer * $answer->count;
            }
        }
        // Calculate choices and average for each question
        $average = $choicessum > 0 ? ($avsum / $choicessum) : 0;
        $answers[$questionid]['average'] = number_format($average, 2);
        $answers[$questionid]['choicessum'] = $choicessum;
        $answers[$questionid]['abstentions'] = $abstentions;
    }
    return $answers;
}


/**
 * Returns an array of questions in a well defined lang for the given feedback
 *
 * @param int $coursfeedback_id - Feedback Id of questions to be shown
 * @param array|string $languages - array or string of language codes (sorted by priority)
 * @param int|null $questiontype - Which questiontype to get, null if all questions wanted
 * @param string $sort
 * @param string $fields
 * @param int|null $questionid
 * @return array - array of question objects
 */
function block_coursefeedback_get_questions_by_language($feedbackid,
        $languages,
        $questiontype = null,
        $sort = "questionid",
        $fields = "questionid,question,coursefeedbackid,questiontype",
        $questionid = null) {
    global $DB, $USER, $COURSE, $CFG;
    $feedbackid = intval($feedbackid);

    // If no languagearray was given (only languagestring instead), build array from default languages.
    if (!is_array($languages)) {
        $fbdefaultlang = get_config("block_coursefeedback", "default_language");
        $languages = [$languages];
        $languages[] = $USER->lang;
        $languages[] = $COURSE->lang;
        $languages[] = $CFG->lang;
        $languages[] = $fbdefaultlang;
    }

    $fblanguages = block_coursefeedback_get_combined_languages($feedbackid);

    $conditions = ["coursefeedbackid" => $feedbackid];
    // Only get the questions needed (which questiontype and which questionlanguage).
    if ($fblanguages && $language = current(array_intersect($languages, $fblanguages))) {
        $conditions["language"] = $language;
    } else if ($fblanguages) {
        $conditions["language"] = $fblanguages[0];
    }
    // Do we need all questions or only a specific type?
    if (!is_null($questiontype)) {
        $conditions['questiontype'] = $questiontype;
    }

    // Do we need all questions or only a specific id?
    if (!is_null($questionid)) {
        $conditions['questionid'] = $questionid;
    }
    $questions = $DB->get_records("block_coursefeedback_questns",
            $conditions,
            $sort,
            $fields);

    // If the search is for a specific questionid, only one entry is allowed to be found.
    if (isset($questionid)) {
        if (count($questions) != 1) {
            throw new moodle_exception(get_string("except_invalid_questionid", "block_coursefeedback"));
        }
    }

    return $questions;
}

/**
 * @param string $feedbackid
 * @return multitype:
 */
function block_coursefeedback_get_question_ids($feedbackid = COURSEFEEDBACK_DEFAULT) {
    global $DB;

    if ($feedbackid === COURSEFEEDBACK_DEFAULT) {
        $feedbackid = get_config("block_coursefeedback", "default_language");
    }
    $feedbackid = intval($feedbackid);

    $select = "coursefeedbackid = ? GROUP BY questionid ORDER BY questionid";

    return $DB->get_fieldset_select("block_coursefeedback_questns", "questionid", $select, [$feedbackid]);
}

/**
 * @param int $feedbackid
 * @param bool $return
 * @return array - Array of strings with error messages if editing is not allowed (may be empty).
 */
function block_coursefeedback_get_editerrors($feedbackid) {
    $feedbackid = intval($feedbackid);
    $perm = [];

    // This feedback is currently active -> editing not possible.
    if ($feedbackid == get_config("block_coursefeedback", "active_feedback")) {
        $perm["erroractive"] = get_string("perm_html_erroractive", "block_coursefeedback");
    }

    // There is already at least one answer for this specific feedback -> editing not possible
    if (block_coursefeedback_answers_exist($feedbackid)) {
        $perm["answersexists"] = get_string("perm_html_answersexists", "block_coursefeedback");
    }
    return $perm;
}

/**
 * Sets the feedback with the given ID as active by updating the configuration setting.
 * Deletes the user-ID answers of the previously active feedback if they exist.
 *
 * @param int $feedbackid
 * @return bool - false, if specified feedback doesn"t exists
 */
function block_coursefeedback_set_active($feedbackid) {
    global $DB;
    if ($feedbackid == 0 || $DB->record_exists("block_coursefeedback", ["id" => $feedbackid])) {
        $oldfeedbackid = get_config("block_coursefeedback", "active_feedback");
        if (block_coursefeedback_answers_exist($oldfeedbackid)) {
            // If answers for the last FB exist -> delete the saved userids.
            // It will not be possible to reactivate a FB for which answers exist
            $DB->delete_records("block_coursefeedback_uidansw", ["coursefeedbackid" => $oldfeedbackid]);
        }
        set_config("active_feedback", $feedbackid, "block_coursefeedback");
        return true;
    } else {
        return false;
    }
}

/**
 * Prints standard header for coursefeedback question administration
 *
 * @param bool $editable
 * @param int|NULL $feedbackid
 */
function block_coursefeedback_print_header($editable = false, $feedbackid = null) {
    global $CFG, $OUTPUT;

    $editable = clean_param($editable, PARAM_BOOL);

    $div = html_writer::start_tag("div", ["style" => "margin-left:3em;margin-bottom:1em;"]);
    if ($editable) {
        $url1 = block_coursefeedback_adminurl("questions", "new", $feedbackid);
        $url2 = block_coursefeedback_adminurl("questions", "dlang", $feedbackid);
        $div .= html_writer::link($url1, get_string("page_link_newquestion", "block_coursefeedback")) . "<br/>"
            . html_writer::link($url2, get_string("page_link_deletelanguage", "block_coursefeedback")) . "<br/>";
    }
    $url1 = block_coursefeedback_adminurl("feedback", "view");
    $url2 = new moodle_url("/" . $CFG->admin . "/settings.php", ["section" => "blocksettingcoursefeedback"]);
    $div .= html_writer::link($url1, get_string("page_link_backtofeedbackview", "block_coursefeedback")) . "<br/>"
        . html_writer::link($url2, get_string("page_link_backtoconfig", "block_coursefeedback")) . "<br/>"
        . html_writer::end_div();
    echo $OUTPUT->box($div);

    if (is_int($feedbackid)) {
        $notes = block_coursefeedback_validate($feedbackid, true);
        if (!empty($notes)) {
            $p = html_writer::tag("p", get_string("page_html_intronotifications", "block_coursefeedback"));
            echo $OUTPUT->notification($p . html_writer::alist($notes));
        }
    }
}

/**
 * Prints notification box for coursefeedback question administration.
 *
 * @param array $errors
 * @param int $feedbackid
 */
function block_coursefeedback_print_noperm_page($errors, $feedbackid) {
    global $OUTPUT;

    $html = html_writer::tag("h4",
        get_string("perm_header_editnotpermitted", "block_coursefeedback"),
        ["style" => "text-align:center;"])
        . html_writer::alist($errors, ["style" => "margin-left:3em;margin-right:3em;"]);

    if (isset($errors["answersexists"])) {
        $html .= html_writer::tag("p",
            get_string("perm_html_danswerslink", "block_coursefeedback", $feedbackid),
            ["style" => "margin-left:3em;margin-right:3em;"]);
    } else if (isset($errors["erroractive"])) {
        $html .= html_writer::tag("p",
            get_string("perm_html_duplicatelink", "block_coursefeedback", $feedbackid),
            ["style" => "margin-left:3em;margin-right:3em;"]);
    }
    echo $OUTPUT->box($html);
}

/**
 * @param int $feedbackid
 * @param string $value - Displayed text
 */
function block_coursefeedback_create_activate_button($feedbackid, $value = "") {
    global $DB;
    if ($DB->record_exists("block_coursefeedback_answers", ["coursefeedbackid" => $feedbackid])) {
        // Reactivation of FB's for whom answers exist is not possible.
        return get_string("page_html_wasactive", "block_coursefeedback", $feedbackid);
    }

    if (!is_string($value) || $value === "") {
        $value = get_string("page_link_use", "block_coursefeedback");
    }
    $url = block_coursefeedback_adminurl("feedback", "activate", $feedbackid);
    return html_writer::link($url, $value);
}

/**
 * @param string $langcode
 * @return string - Gives the human readable language string
 */
function block_coursefeedback_get_language($langcode) {
    $list = get_string_manager()->get_list_of_translations();
    $language = (isset($list[$langcode])) ? $list[$langcode] : "[undefined]";

    return $language;
}

/**
 * Checks if there are questions to display for coursefeedback
 *
 * @param string $feedbackid
 * @return boolean
 */
function block_coursefeedback_questions_exist($feedbackid = COURSEFEEDBACK_DEFAULT) {
    global $CFG, $COURSE, $USER;

    $config = get_config("block_coursefeedback");
    $feedbackid = ($feedbackid === COURSEFEEDBACK_DEFAULT) ? $config->active_feedback : intval($feedbackid);
    $langs = block_coursefeedback_get_combined_languages($feedbackid);

    return in_array($USER->lang, $langs)
        || in_array($COURSE->lang, $langs)
        || in_array($CFG->lang, $langs)
        || in_array($config->default_language, $langs);
}

/**
 * Check if there are answers for this coursefeedback
 *
 * @param string $feedbackid
 * @return boolean
 */
function block_coursefeedback_answers_exist($feedbackid) {
    global $DB;
    return $DB->record_exists("block_coursefeedback_answers", ["coursefeedbackid" => $feedbackid]);
}

/**
 * Checks feedback on useableness
 *
 * @param int $feedbackid
 * @param boolean $returnerrors
 * @return multitype:array boolean
 */
function block_coursefeedback_validate($feedbackid, $returnerrors = false) {
    $notifications = [];
    $feedbackid = intval($feedbackid);
    $defaultlang[] = get_config("block_coursefeedback", "default_language");
    if ($feedbackid > 0) {
        $langs = block_coursefeedback_get_combined_languages($feedbackid);
        if (empty($langs)) {
            $notifications[] = get_string("page_html_norelations", "block_coursefeedback");
        } else if (!array_intersect($langs, $defaultlang)) {
            $notifications[] = get_string("page_html_servedefaultlang",
                "block_coursefeedback",
                $defaultlang);
        }
    }
    if ($returnerrors) {
        return $notifications;
    } else {
        return empty($notifications);
    }
}
function format($string) {
    return format_text(stripslashes($string), FORMAT_PLAIN);
}

/**
 * @param string $mode feedback|question|questions
 * @param string $action view|edit|delete
 * @param array $other params of the url.
 * @return moodle_url to admin.php with given params.
 */
function block_coursefeedback_adminurl($mode, $action, $fid = null, array $other = []) {
    $url = new moodle_url("/blocks/coursefeedback/admin.php");
    $params = array_merge($other, ["mode" => $mode, "action" => $action]);
    if (is_number($fid)) {
        $params["fid"] = $fid;
    }
    $url->params($params);

    return $url;
}

/**
 * Returns the next open quesiton to answer if there is one
 *
 * @return array|null
 * @throws \moodle_exception
 */
function block_coursefeedback_get_open_question() {
    global $DB, $COURSE, $USER;
    $config = get_config("block_coursefeedback");
    $currentlang[] = current_language();
    // Check if FB is active just in case
    if (isset($config->active_feedback) && $config->active_feedback != 0) {
        $questions = block_coursefeedback_get_questions_by_language($config->active_feedback, $currentlang);
        foreach ($questions as $question) {
            $params = [
                "userid" => $USER->id,
                "course" => $COURSE->id, "questionid" => $question->questionid,
                "coursefeedbackid" => $config->active_feedback,
            ];
            if (!$DB->record_exists("block_coursefeedback_uidansw", $params)) {
                // Diese Frage ist noch offen;
                return [
                    'currentopenqstn' => $question,
                    'questionsum' => count($questions),
                ];
            }
        }
        // Keine offene Fragen vorhanden
        return null;
    }
    return null;
}

/**
 * Check if since_coursestart setting is enabled and if the coursesatart was to long ago
 *
 * @param object $config
 * @param int $courseid
 * @return bool
 */
function block_coursefeedbck_coursestartcheck_good($config, $courseid) {
    // if setting not activated don't check for coursestart
    if ($config->since_coursestart_enabled) {
        $course = get_course($courseid);
        $startdate = $course->startdate;
        $timepassed = (time() - $startdate);
        if ($timepassed > $config->since_coursestart || $startdate > time()) {
            return false;
        }
    }
    return true;
}

/**
 * Return all feedbacks with answers for this course
 *
 * @param int $courseid
 * @return array
 */
function block_coursefeedbck_get_fbsfor_course($courseid) {
    global $DB;
    $sql = "SELECT DISTINCT cf.id, cf.name, ans.course
              FROM {block_coursefeedback_answers} ans
              JOIN {block_coursefeedback} cf ON ans.coursefeedbackid = cf.id
             WHERE ans.course = ?";
    $answerredfbs = $DB->get_records_sql($sql, [$courseid]);

    return $answerredfbs;
}

/**
 * This function extends the course navigation with a coursefeeedbackresults link
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course
 * @param stdClass $context The context of the course
 */
function block_coursefeedback_extend_navigation_course(
        navigation_node $parentnode,
        stdClass $course,
        context_course $context) {
    // Only Trainers and only if there are feedbacks to display
    if (has_capability('block/coursefeedback:viewanswers', $context)
            && !empty($fbs = block_coursefeedbck_get_fbsfor_course($course->id))) {
        // Add Coursefeedbacksnode to the "more" navigation dropdown
        $parentnode->add(
            get_string('resultspage_nav_extension', 'block_coursefeedback'),
            $url = new moodle_url('/blocks/coursefeedback/results.php',
                ['course' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            'block_coursefeedback',
            'feedback_results',
            new pix_icon('i/courseevent', '')
        );
    }
};
