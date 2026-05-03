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
 * Report stuff here...
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

use block_coursefeedback\local\multilang_string;
use block_coursefeedback\local\surveyitem\emoji\emoji_surveyitem;

require_login();
$context = context_system::instance();
$context = context_system::instance();
$course_id = required_param('course_id', PARAM_INT);

require_capability('block/coursefeedback:manageorganizations', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/report.php'));
$PAGE->set_heading("Reporting Heading");
echo $OUTPUT->header();

# Get the Survey id from this course. [There can be only one survey in a course ... can it?]
$survey_execution_data = $DB->get_record('block_coursefeedback_surveyexecution', ['courseid' => $course_id]);

# Get All survey parts.
if($survey_execution_data) {
    $survey_execution_parts = $DB->get_records('block_coursefeedback_surveypartexecution', ['surveyexecutionid' => $survey_execution_data->id]);
} else {
    $survey_execution_parts = [];
}

$renderdata = [];

# loop through the parts and display them each as a card!
foreach ($survey_execution_parts as $survey_execution_part) {
    # Save the survey part id [This id helps to identify the used survey questions].
    $survey_part_id = $survey_execution_part->surveypartid;
    # Get all survey questions which are connected to this part id.
    $survey_item_data = $DB->get_records('block_coursefeedback_surveyitem', ['surveypartid' => $survey_part_id], 'sortindex ASC');
    # Get the ids of all users which have submitted this part of the survey.
    $survey_anonym_user_data = $DB->get_records('block_coursefeedback_surveypartexecutionoptionresp', ['surveypartexecutionoptionid' => $survey_execution_part->id]);
    # Extract the ids.
    $user_response_ids = array_keys($survey_anonym_user_data);

    # Get all responses from these users.
    list($insql, $params) = $DB->get_in_or_equal($user_response_ids);
    $sql = "SELECT id, surveyitemid, value FROM {block_coursefeedback_surveyitemintresponse} WHERE surveypartexecutionoptionresponseid $insql";
    $responses = $DB->get_records_sql($sql, $params);
    $sql_text = "SELECT id, surveyitemid, value FROM {block_coursefeedback_surveyitemtextresponse} WHERE surveypartexecutionoptionresponseid $insql";
    $text_responses = $DB->get_records_sql($sql_text, $params);

    $questions_data = [];

    # Loop through all items of the survey.
    foreach ($survey_item_data as $item) {
        # Collect only data from singlechoice and scale questions.
        $supported_types = ['singlechoice', 'scalequestion', 'multiplechoice', 'emoji', 'text'];
        if (!in_array($item->surveyitemtype, $supported_types)) {
            continue;
        }

        # Get the question text.
        $raw_question_text = multilang_string::deserialize($item->text);
        $current_question_text = strip_tags($raw_question_text->translate());
        $current_question_text = strip_tags($raw_question_text->translate());

        $counts = [];
        $maxOptions = 0;

        # Scale question handling ...
        if ($item->surveyitemtype == 'scalequestion') {
            $mapping = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $item->id]);
            if ($mapping) {
                $scale = $DB->get_record('block_coursefeedback_scale', ['id' => $mapping->scaleid]);
                $maxOptions = $scale ? (int)$scale->optionamount : 5;
                for ($i = 1; $i <= $maxOptions; $i++) {
                    $counts[$i] = 0;
                }
            }
        } # Emoji handling ...
        else if ($item->surveyitemtype == 'emoji') {
            $emoji_record = $DB->get_record('block_coursefeedback_surveyitememojis', ['surveyitemid' => $item->id]);

            if ($emoji_record) {
                $variants = emoji_surveyitem::get_available_variants();
                $variant = $variants[$emoji_record->variant] ?? null;
                if ($variant) {
                    foreach ($variant['choices'] as $choice) {
                        $counts[(int)$choice['value']] = 0;
                    }
                    $maxOptions = count($variant['choices']);
                }
            }
        } # Singlechoice question handling ...
        else if ($item->surveyitemtype == 'singlechoice' || $item->surveyitemtype == 'multiplechoice') {
            $options = $DB->get_records('block_coursefeedback_surveyitemansweroption', ['surveyitemid' => $item->id], 'sortindex ASC');
            foreach ($options as $opt) {
                $counts[$opt->id] = 0;
            }
            $maxOptions = count($options);
        }

        $questions_data[$item->id] = [
            'type' => $item->surveyitemtype,
            'questiontext' => $current_question_text,
            'max_options' => $maxOptions,
            'counts' => $counts,
            'responses' => [],
            'n' => 0
        ];
    }

    # Count how often each answer was given by the students.
    foreach ($responses as $response) {
        $itemId = $response->surveyitemid;
        $val = (int)$response->value;

        if (isset($questions_data[$itemId])) {
            if (isset($questions_data[$itemId]['counts'][$val])) {
                $questions_data[$itemId]['counts'][$val]++;
                $questions_data[$itemId]['n']++;
            }
        }
    }

    foreach ($text_responses as $text_res) {
        $itemId = $text_res->surveyitemid;
        if (isset($questions_data[$itemId])) {
            $questions_data[$itemId]['responses'][] = ['text' => s($text_res->value)];
            $questions_data[$itemId]['n']++;
        }
    }

    # D3 plotting values here.
    foreach ($questions_data as $itemId => &$data) {
        $legend = [];

        # Scalequestion ...
        if ($data['type'] === 'scalequestion') {
            $mapping = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $itemId]);
            if ($mapping) {
                $scale = $DB->get_record('block_coursefeedback_scale', ['id' => $mapping->scaleid]);
                $data['scale_name'] = $scale ? $scale->name : '';
                $data['scale_min_name'] = strip_tags(multilang_string::deserialize($scale->minoptiontext)->translate());
                $data['scale_max_name'] = strip_tags(multilang_string::deserialize($scale->maxoptiontext)->translate());
            }
        } # Singlechoice ...
        else if ($data['type'] === 'singlechoice' || $data['type'] === 'multiplechoice') {
            $options = $DB->get_records('block_coursefeedback_surveyitemansweroption', ['surveyitemid' => $itemId], 'sortindex ASC');
            $index = 1;
            foreach ($options as $opt) {
                $legend[] = [
                    'number' => $index++,
                    'text' => strip_tags(multilang_string::deserialize($opt->text)->translate())
                ];
            }
        } # Emoji ...
        else if ($data['type'] === 'emoji') {
            $emoji_record = $DB->get_record('block_coursefeedback_surveyitememojis', ['surveyitemid' => $itemId]);
            $variants = emoji_surveyitem::get_available_variants();
            $variant = $variants[$emoji_record->variant] ?? null;

            if ($variant) {
                $data['scale_name'] = $variant['name'];
                $data['scale_min_name'] = $variant['choices'][0]['emoji'];
                $data['scale_max_name'] = end($variant['choices'])['emoji'];
            }
        }
        $data['legend'] = $legend;
    }
    $questions_for_template = [];
    $question_index = 1;

    foreach ($questions_data as $itemId => &$data) {
        # Calculate statistic values here ...
        $is_scale = ($data['type'] === 'scalequestion' || $data['type'] === 'emoji');
        $is_text = ($data['type'] === 'text');

        if ($is_text) {
            $stats = [
                'n' => $data['n'],
                'mean' => 0,
                'median' => 0,
                'stddev' => 0
            ];
        } else {
            $stats = calculate_survey_stats(array_values($data['counts']), 1);
        }

        $percents = [];
        foreach ($data['counts'] as $c) {
            $percents[] = ($stats['n'] > 0) ? round(($c / $stats['n']) * 100, 1) : 0;
        }

        $questions_for_template[] = [
            'itemid' => $itemId,
            'index' => $question_index,
            'questiontext' => $data['questiontext'],
            'n' => $stats['n'],
            'mean' => $is_scale ? $stats['mean'] : null,
            'median' => $is_scale ? $stats['median'] : null,
            'stddev' => $is_scale ? $stats['stddev'] : null,
            'has_stats' => $is_scale && ($stats['n'] > 0),
            'is_scale' => $is_scale,
            'scale_min_name' => $data['scale_min_name'] ?? '',
            'scale_max_name' => $data['scale_max_name'] ?? '',
            'legend' => $data['legend'],
            'is_text' => $is_text,
            'responses' => $data['responses'],
            'counts_length' => count($data['counts']),
            'chart_data_json' => json_encode([
                'itemid' => (int)$itemId,
                'counts' => array_values($data['counts']),
                'counts_percent' => array_map('floatval', $percents),
                'mean' => (float)$stats['mean'],
                'median' => (float)$stats['median'],
                'stddev' => (float)$stats['stddev'],
                'is_scale' => $is_scale,
                'n' => (int)$stats['n']
            ])
        ];

        $question_index++;
    }

    $renderdata = ['questions' => $questions_for_template];
}

echo $OUTPUT->render_from_template('block_coursefeedback/report', $renderdata);
echo $OUTPUT->footer();


/**
 * Calculates statistic stuff ...
 * * @param array $counts Array with answer counts [value/index => count]
 * @param int $min_scale start values of the min scale [always 1!?]
 * @return array
 */
function calculate_survey_stats($counts, $min_scale = 1) {
    $n = array_sum($counts);

    if ($n === 0) {
        return [
            'n' => 0,
            'mean' => 0,
            'median' => 0,
            'stddev' => 0
        ];
    }

    # Mean calculation.
    $sum = 0;
    foreach ($counts as $index => $count) {
        $value = $index + $min_scale;
        $sum += $value * $count;
    }
    $mean = round($sum / $n, 2);

    # Median calculation.
    $median = 0;
    $middle = $n / 2;
    $curr_sum = 0;
    foreach ($counts as $index => $count) {
        $curr_sum += $count;
        if ($curr_sum >= $middle) {
            $median = $index + $min_scale;
            break;
        }
    }

    # Standard deviation calculation.
    $varianceSum = 0;
    foreach ($counts as $index => $count) {
        $value = $index + $min_scale;
        $varianceSum += $count * pow($value - $mean, 2);
    }
    $stdDev = round(sqrt($varianceSum / $n), 2);

    return [
        'n' => $n,
        'mean' => $mean,
        'median' => $median,
        'stddev' => $stdDev
    ];
}