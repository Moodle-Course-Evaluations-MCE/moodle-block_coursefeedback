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
 * Display the administration page for creating/editing surveys
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Moodle includes.
require_once(__DIR__ . "/../../config.php");
require_once($CFG->libdir . "/tablelib.php");
require_once(__DIR__ . "/forms/coursefeedback_admin_forms.php");
require_once(__DIR__ . "/lib.php");

// Check for admin.
require_login();
$context = context_system::instance();
require_capability("block/coursefeedback:managefeedbacks", $context);

global $languagemenu, $formtype;

$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

$action = required_param("action", PARAM_ALPHA);
$mode = required_param("mode", PARAM_ALPHA);

$fid = intval(optional_param("fid", 0, PARAM_INT));
$qid = intval(optional_param("qid", null, PARAM_INT));
$language = optional_param("lng", null, PARAM_ALPHA);

$errormsg = "";
$statusmsg = "";

// Initialize forms.($data->unwantedlang)
// && isset($data->template)
if (!isset($forms)) {
    $forms["questions"]["dlang"] = new coursefeedback_delete_language_form("?mode=questions&action=dlang&fid={$fid}", $fid);
    $forms["feedback"]["danswers"] = new coursefeedback_delete_answers_form("?mode=feedback&action=danswers&fid={$fid}", $fid);
    foreach (["feedback", "questions", "question"] as $i) {
        foreach (["new", "edit", "delete"] as $j) {
            $formtype = "coursefeedback_{$i}_{$j}_form";
            $link = "?mode={$i}&action={$j}";
            if ($fid >= 0) {
                $link .= "&fid=" . $fid;
            }
            if ($qid >= 0) {
                $link .= "&qid=" . $qid;
            }
            if ($language) {
                $link .= "&lng=" . $language;
            }
            $forms[$i][$j] = new $formtype($link, $fid, $qid, $language);
        }
    }
}

$form = &$forms[$mode][$action];

// ACTIVATE FEEDBACK Process subbmitted data (Actions are defined by GET).
if ($mode == "feedback" && $action == "activate") {
    $notifications = block_coursefeedback_validate($fid, true);
    if (!empty($notifications)) {
        $errormsg = html_writer::tag("div",
            get_string("page_html_intronotifications", "block_coursefeedback"),
            ["style" => "margin-bottom:.5em"]
        );
        $encl = [html_writer::start_tag("div"), html_writer::end_tag("div")];
        $errormsg .= $encl[0] . join($encl[1] . $encl[0], $notifications) . $encl[1];
    } else if (block_coursefeedback_set_active($fid)) {
        if ($fid != 0) {
            $statusmsg = get_string("page_html_activated", "block_coursefeedback", $fid);
        } else {
            $statusmsg = get_string("page_html_nofeedbackactive", "block_coursefeedback");
        }
    } else {
        $errormsg = get_string("therewereerrors", "admin");
    }
    $action = "view";
}

// PROCESS any form subbmitted data (Actions defined by POST).
if (isset($form) && get_parent_class($form) === "coursefeedbackform" && $form->is_submitted()) {
    $data = $form->get_data();
    $trigger = $mode . key($forms[$mode][$action]->_form->exportValue("submits"));

    switch ($trigger) {
        case "feedbackadd":
            if ($form->is_validated()) {
                if ($data->name && isset($data->template)) {
                    if ($DB->record_exists("block_coursefeedback", ["id" => intval($data->template)])) {
                        // Copying / duplicating feedback.
                        if (block_coursefeedback_copy_feedback($data->template, $data->name, $data->heading,
                                $data->infotext['text'], $data->infotext['format'])) {
                            $statusmsg = get_string("changessaved");
                        } else {
                            $errormsg = get_string("therewereerrors", "admin");
                        }
                    } else {
                        // Creating new fedback.
                        switch (block_coursefeedback_insert_feedback($data->name, $data->heading,
                                $data->infotext['text'], $data->infotext['format'])) {
                            case -1:
                                $errormsg = get_string("semicolonerror", "block_coursefeedback");
                                break;
                            case 0:
                                $errormsg = get_string("therewereerrors", "admin");
                                break;
                            default:
                                $statusmsg = get_string("changessaved");
                        }
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "feedbackdelete":
            if ($form->is_validated()) {
                if ($data->confirm === "1" && isset($data->template)) {
                    if (block_coursefeedback_delete_feedback($data->template)) {
                        $statusmsg = get_string("deletedcourse",
                            "moodle",
                            get_string("pluginname", "block_coursefeedback") . " (" . $fid . ")");
                    } else {
                        $errormsg = get_string("deletednot", "moodle", $text);
                    }
                } else if ($data->confirm === "0") {
                    $statusmsg = get_string("cancelled");
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "feedbackedit":
            if ($form->is_validated()) {
                if ($data->name && isset($data->template)) {
                    if (block_coursefeedback_edit_feedback($data->template, $data->name, $data->heading,
                            $data->infotext['text'], $data->infotext['format'])) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("error");
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "feedbackdanswers":
            if ($form->is_validated()) {
                if ($data->confirm === "1") {
                    if (isset($data->template)) {
                        if (block_coursefeedback_delete_answers($data->template)) {
                            $statusmsg = get_string("page_html_answersdeleted", "block_coursefeedback");
                        } else {
                            $errormsg = get_string("therewereaerrors", "admin");
                        }
                    } else {
                        $errormsg = get_string("therewereaerrors", "admin");
                    }
                } else if ($data->confirm === "0") {
                    $statusmsg = get_string("cancelled");
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "questionsadd":
            if ($form->is_validated()) {
                if ($data->questiontext && $data->newlang && isset($data->feedbackid, $data->questionid, $data->questiontype)) {
                    if (block_coursefeedback_insert_question($data->questiontext,
                            $data->feedbackid,
                            $data->questionid,
                            $data->newlang,
                            $data->questiontype)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("error");
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "questionsmove":
            if ($form->is_validated()) {
                if (isset($data->position) && isset($data->template) && isset($data->questionid)) {
                    if (block_coursefeedback_move_question($data->template, $data->questionid, $data->position)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("therewereerrors", "admin");
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "questionsdlang":
            if ($form->is_validated()) {
                if (isset($data->unwantedlang) && isset($data->template)) {
                    if (block_coursefeedback_delete_questions($data->template, $data->unwantedlang)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("therewereerrors", "admin");
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
            }
            break;
        case "questiondelete":
        case "questionsdelete":
            if ($form->is_validated()) {
                if ($data->confirm === "1" && isset($data->language) && isset($data->template) && isset($data->questionid)) {
                    if (block_coursefeedback_delete_question($data->template, $data->questionid, $data->language)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("therewereerrors", "admin");
                    }
                } else if ($data->confirm === "0") {
                    $statusmsg = get_string("cancelled");
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $action = "view";
                $mode = "questions";
            }
            break;
        case "questionadd":
            // Adding a new language translation to an existing question.
            if ($form->is_validated()) {
                if ($data->questiontext && $data->newlanguage && $data->template && $data->questionid && $data->questiontype) {
                    if (block_coursefeedback_insert_question($data->questiontext, $data->template, $data->questionid,
                            $data->newlanguage, $data->questiontype)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("therewereerrors", "admin");
                    }
                }
                $mode = "questions";
                $action = "view";
            }
            break;
        case "questionedit":
            if ($form->is_validated()) {
                if ($data->questiontext && isset($data->template) && isset($data->questionid) && $data->language) {
                    if (block_coursefeedback_update_question($data->template, $data->questionid, $data->questiontext,
                            $data->language)) {
                        $statusmsg = get_string("changessaved");
                    } else {
                        $errormsg = get_string("therewereerrors", "admin");
                    }
                } else {
                    $errormsg = get_string("therewereerrors", "admin");
                }
                $mode = "questions";
                $action = "view";
            }
            break;
        case "questioncancel":
        case "questionscancel":
        case "feedbackcancel":
            $statusmsg = get_string("cancelled");
        default:
            // This occurs most times on "cancel".
            if ($mode === "question") {
                $mode = "questions";
            }
            $action = "view";
            break;
    }
}

// PROCESS link was clicked check for editerrors
/*
* $MODE
* Has to be "feedback" or "question" and depends on which data is to be edited.
*/

$checkresult = block_coursefeedback_get_editerrors($fid);
$editable = empty($checkresult);
$allowedactions = [
    "feedbackdanswers",
    "feedbacknew",
    "feedbackview",
]; // Allowed actions, even if the feedback is active or is answered by users.

if (!$editable and !in_array($mode . $action, $allowedactions)) {
    // Break current event!
    $action = "view";
    $mode = "questions";
}

$PAGE->set_url(new moodle_url("/blocks/coursefeedback/admin.php",
    ["action" => $action, "mode" => $mode, "fid" => $fid, "qid" => $qid, "lng" => $language]));
$PAGE->set_title(get_string("page_headline_admin", "block_coursefeedback"));
$PAGE->set_heading(get_string("page_headline_admin", "block_coursefeedback"));
$PAGE->navbar->add(get_string("blocks"), new moodle_url("/admin/blocks.php"));
$PAGE->navbar->add(get_string("pluginname", "block_coursefeedback"),
    new moodle_url("/admin/settings.php", ["section" => "blocksettingcoursefeedback"]));
$PAGE->navbar->add(get_string("page_headline_admin", "block_coursefeedback"),
    new moodle_url("/blocks/coursefeedback/admin.php", ["mode" => "feedback", "action" => "view"]));

// Start output.
echo $OUTPUT->header();

/*
 * NOTIFICATION HANDLING
 */
if (!empty($errormsg)) {
    echo $OUTPUT->notification($errormsg);
} else if (!empty($statusmsg)) {
    echo $OUTPUT->notification($statusmsg, "notifysuccess");
}

// VIEW show feeedbacks or questions
if ($action === "view") {
    // FB-view and Q-view are the only modes for hard coded output.
    $displayform = false; // display form anyway?
    $html = "";
    echo "<h2 class=\"main\">" . get_string("page_headline_admin", "block_coursefeedback") . "</h2>";

    echo "<fieldset>";

    if ($mode === "feedback") {
        $alink = block_coursefeedback_adminurl("feedback", "new");
        $slink = new moodle_url("/" . $CFG->admin . "/settings.php", ["section" => "blocksettingcoursefeedback"]);
        echo $OUTPUT->box("<div style=\"margin-left:3em;\">"
            . html_writer::link($alink, get_string("page_link_newtemplate", "block_coursefeedback")) . "<br />"
            . html_writer::link($slink, get_string("page_link_backtoconfig", "block_coursefeedback")) . "</div>");
        $active = get_config("block_coursefeedback", "active_feedback");
        $table = new html_table();
        $table->head = [
            get_string("idnumber"),
            get_string("name"),
            get_string("modified"),
            get_string("action"),
            get_string("table_header_languages", "block_coursefeedback"),
            get_string("table_header_questions", "block_coursefeedback"),
            get_string("active"),
        ];
        $table->align = ["left", "left", "left", "left", "left", "left", "center"];
        $table->size = ["5%", "30%", "8%", "8%", "8%", "8%", "8%"];
        $table->data = [];

        $table->data[] = [
            "",
            get_string("table_html_nofeedback", "block_coursefeedback"),
            "",
            "",
            "",
            "",
            ($active == 0) ? "X" : block_coursefeedback_create_activate_button(0),
        ];

        if ($feedbacks = $DB->get_records("block_coursefeedback", null, "id")) {
            foreach ($feedbacks as $feedback) {
                $languages = block_coursefeedback_get_combined_languages($feedback->id, false);
                if (!empty($languages)) {
                    $langtext = join(", ", $languages);
                    $q = $DB->count_records_select("block_coursefeedback_questns",
                        "coursefeedbackid = :fid AND language = :curlang GROUP BY language",
                        ["fid" => $feedback->id, "curlang" => current(array_keys($languages))]);
                } else {
                    $langtext = "&nbsp;";
                    $q = 0;
                }
                $feedback->name = format_string($feedback->name);
                $feedback->timemodified = date('d.m.Y', $feedback->timemodified);
                $url1 = block_coursefeedback_adminurl("feedback", "new", $feedback->id);
                $url2 = block_coursefeedback_adminurl("feedback", "edit", $feedback->id);
                $url3 = block_coursefeedback_adminurl("questions", "view", $feedback->id);
                $url4 = block_coursefeedback_adminurl("feedback", "delete", $feedback->id);
                $text1 = html_writer::link($url1, get_string("duplicate")) . "<br/>"
                    . html_writer::link($url2, get_string("edit")) . "<br/>"
                    . html_writer::link($url3, get_string("page_link_showlistofquestions", "block_coursefeedback")) . "<br/>"
                    . html_writer::link($url4, get_string("delete"));
                $text2 = ($active == $feedback->id) ? "X" : block_coursefeedback_create_activate_button($feedback->id) . "<br/>";
                $table->data[] = [$feedback->id, $feedback->name, $feedback->timemodified, $text1, $langtext, $q, $text2];
            }
        }

        $html = html_writer::tag("h4",
            get_string("page_headline_listoffeedbacks", "block_coursefeedback"), ["class" => "main"])
            . html_writer::table($table);
    } else if ($mode === "questions") {
        block_coursefeedback_print_header($editable, $fid);

        if ($editable && $questions = block_coursefeedback_get_question_ids($fid)) {
            $requiredlangs = block_coursefeedback_get_implemented_languages($fid);
            $html = "<h4 class=\"main\">"
                . get_string("page_headline_listofquestions", "block_coursefeedback",
                    block_coursefeedback_get_feedbackname($fid))
                . "</h4>";

            $table = new html_table();
            $table->head = [
                get_string("idnumber"),
                get_string("questiontype", "block_coursefeedback"),
                get_string("language"),
                get_string("question"),
                get_string("action"),
            ];
            $table->align = ["left", "left", "left", "left", "left"];
            $table->size = ["5%", "*", "*", "*", "*"];
            $table->data = [];

            foreach ($questions as $questionid) {
                $listing = "";
                $languages = "";
                $links = "";
                $questiontypestr = "";

                if ($requiredlangs) {
                    foreach ($requiredlangs as $language) {
                        if ($question = $DB->get_record("block_coursefeedback_questns", [
                                "coursefeedbackid" => $fid,
                                "questionid" => $questionid,
                                "language" => $language,
                            ])) {
                            // If not defined yet, get the questiontype
                            if (empty($questiontypestr)) {
                                $questiontypearray = get_question_types($question->questiontype);
                                // Extract the questiontype string from the array
                                if (isset($questiontypearray[$question->questiontype])) {
                                    $questiontypestr = $questiontypearray[$question->questiontype];
                                }
                            }
                            $question = format_string($question->question);
                            $listing .= "<div>";
                            if (strlen($question) > 50 && $p = strpos($question, " ", 50)) {
                                $listing .= str_replace(" ", "&nbsp;", substr($question, 0, $p) . "&nbsp;[...]");
                            } else {
                                $listing .= str_replace(" ", "&nbsp;", $question);
                            }
                            $listing .= "</div>\n";
                            $languages .= html_writer::tag("span", $language, ["style" => "padding:0px;"]) . "<br/>\n";
                            $url1 = block_coursefeedback_adminurl("question", "edit", $fid,
                                ["qid" => $questionid, "lng" => $language]);
                            $url2 = block_coursefeedback_adminurl("question", "delete", $fid,
                                ["qid" => $questionid, "lng" => $language]);
                            $links .= html_writer::tag("span", html_writer::link($url1, get_string("edit")),
                                ["style" => "padding:0px;"])
                                . html_writer::tag("span", "&nbsp;&#124;&nbsp;", ["style" => "padding:0px;"])
                                . html_writer::tag("span", html_writer::link($url2, get_string("delete")),
                                ["style" => "padding:0px;"]) . "<br />";
                        } else {
                            $listing .= html_writer::span(get_string("table_html_undefinedlang",
                                "block_coursefeedback", $language) . "<br/>", "notifyproblem",
                                ["style" => "padding:0px;"]);
                            $languages .= html_writer::tag("span", $language,
                                ["style" => "padding:0px;text-decoration:line-through;"]) . "<br/>";
                            $url = new moodle_url("/blocks/coursefeedback/admin.php", [
                                "mode" => "question",
                                "action" => "new",
                                "fid" => $fid,
                                "qid" => $questionid,
                                "lng" => $language,
                            ]);
                            $links .= html_writer::tag("span", html_writer::link($url, get_string("add")),
                                ["style" => "padding:0px;"]) . "<br />";
                        }
                    }
                }

                $other = ["qid" => $questionid];
                $url1 = block_coursefeedback_adminurl("questions", "edit", $fid, $other);
                $url2 = block_coursefeedback_adminurl("questions", "delete", $fid, $other);
                $url3 = block_coursefeedback_adminurl("question", "new", $fid, $other);
                $listing .= "<br/>" . get_string("page_html_editallquestions", "block_coursefeedback") . ": "
                    . html_writer::link($url1, get_string("move"))
                    . " &#124; "
                    . html_writer::link($url2, get_string("delete"))
                    . " &#124; "
                    . html_writer::link($url3, get_string("page_link_newlanguage", "block_coursefeedback"));
                $table->data[] = [$questionid, $questiontypestr, $languages, $listing, $links];
            }
            $html .= html_writer::table($table);
        } else if (!$editable) {
            block_coursefeedback_print_noperm_page($checkresult, $fid);
        } else {
            $url = block_coursefeedback_adminurl("questions", "new", $fid);
            $html = html_writer::tag("h4",
                html_writer::link($url, get_string("page_link_noquestion", "block_coursefeedback")),
                ["style" => "text-align:center;"]);
        }
    } else {
        print_error("Wrong parameters");
    }

    if ($html > "") {
        echo $OUTPUT->box($html);
    }

    echo "</fieldset>";
} else {
    // Action -> EDIT or NEW.
    $form = &$forms[$mode][$action]; // Reset form.
    if ($action === "edit") {
        if ($mode === "feedback") {
            if ($feedback = $DB->get_record("block_coursefeedback", ['id' => $fid], '*', IGNORE_MISSING)) {
                // Copying existing FB -> set_data.
                $form->set_data($feedback);
            }
        } else if ($mode === "questions") {
            $form->_form->getElement("position")->setSelected($qid);
        } else if ($mode === "question") {
            $question = $DB->get_field("block_coursefeedback_questns", "question",
                ["coursefeedbackid" => $fid, "questionid" => $qid, "language" => $language]);
            $form->_form->getElement("questiontext")->setValue(format($question));
        }
    } else if ($action === "new" && $mode === "feedback"
            && $feedback = $DB->get_record("block_coursefeedback", ['id' => $fid], '*', IGNORE_MISSING)) {
        $form->set_data($feedback);
    }

    // Check if editing/cobying is allowed.
    if (($editable or in_array($mode . $action, $allowedactions))) {
        $form->display();
    } else {
        block_coursefeedback_print_header();
        echo "<fieldset>";
        block_coursefeedback_print_noperm_page($checkresult, $fid);
        echo "</fieldset>";
    }
}
echo $OUTPUT->footer();
