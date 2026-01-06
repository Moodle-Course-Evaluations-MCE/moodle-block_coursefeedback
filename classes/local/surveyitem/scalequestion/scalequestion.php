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
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem\scalequestion;

use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitem\surveyitemtype;
use core\exception\coding_exception;
use core\lang_string;

/**
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scalequestion extends surveyitemtype {

    #[\Override]
    public function get_settings_mform() {
        return scalequestion_form::class;
    }

    #[\Override]
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        global $DB;
        $formdata->forceshowscale ??= false;
        $formdata->surveyitemid = $surveyitemid;
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitemid]);
        if ($record) {
            foreach ($formdata as $key => $value) {
                $record->$key = $value;
            }
            $DB->update_record('block_coursefeedback_surveyitemscalequestion', $record);
        } else {
            $DB->insert_record('block_coursefeedback_surveyitemscalequestion', $formdata);
        }
    }

    #[\Override]
    public function load_settings_mform(surveyitem $surveyitem, string $language): object {
        global $DB;
        $data = parent::load_settings_mform($surveyitem, $language);
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitem->get('id')]);
        if ($record) {
            $data->scaleid = $record->scaleid;
            $data->forceshowscale = $record->forceshowscale;
        }
        return $data;
    }

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('scalequestion', 'block_coursefeedback');
    }

    #[\Override]
    public function load_questiondata_for(array $surveyitems): array {
        global $DB;
        [$textids, $additionaldata] = parent::load_questiondata_for($surveyitems);
        $surveyitemids = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemids[] = $surveyitem->get('id');
        }
        [$insql, $params] = $DB->get_in_or_equal($surveyitemids);
        $records = $DB->get_records_sql(
            'SELECT sq.surveyitemid, sq.*, s.* FROM {block_coursefeedback_surveyitemscalequestion} sq ' .
            'JOIN {block_coursefeedback_scale} s ON sq.scaleid = s.id ' .
            "WHERE sq.surveyitemid $insql",
            $params
        );
        foreach ($records as $record) {
            $additionaldata[$record->surveyitemid] = $record;
            $textids[$record->surveyitemid]['minpole'] = $record->minoptiontextid;
            $textids[$record->surveyitemid]['maxpole'] = $record->maxoptiontextid;
            if ($record->hasnoansweroption) {
                $textids[$record->surveyitemid]['noansweroption'] = $record->noansweroptiontextid;
            }
        }
        return [$textids, $additionaldata];
    }

    #[\Override]
    public function create_question_structure(array $surveyitems, array $texts, array $additionaldata): array {
        $structure = parent::create_question_structure($surveyitems, $texts, $additionaldata);

        $lastsurveyitem = null;

        foreach ($surveyitems as $surveyitem) {
            $record = $additionaldata[$surveyitem->get('id')];
            $hasnaoption = (bool) $record->hasnoansweroption;
            $optionamount = $record->optionamount;
            $show_scale = $record->forceshowscale || (
                    !$lastsurveyitem || (
                        $additionaldata[$lastsurveyitem->get('id')]->scaleid != $record->scaleid &&
                        $lastsurveyitem->get('sortindex') === $surveyitem->get('sortindex') - 1
                    ));
            $structure[$surveyitem->get('id')] += [
                'max_pole' => $texts[$surveyitem->get('id')]['maxpole'],
                'min_pole' => $texts[$surveyitem->get('id')]['minpole'],
                'has_na_option' => $hasnaoption,
                'na_option' => $hasnaoption ? $texts[$surveyitem->get('id')]['noansweroption'] : null,
                'optionamount' => $optionamount,
                'options' => [],
                'show_scale' => $show_scale,
            ];
            for ($i = 1; $i <= $optionamount; $i++) {
                $text = null;
                if ($i == 1 || $i == $optionamount) {
                    $text = $texts[$surveyitem->get('id')][$i == 1 ? 'minpole' : 'maxpole'];
                }

                $structure[$surveyitem->get('id')]['options'][] = [
                    'id' => $i,
                    'text' => $text,
                ];
            }
            $lastsurveyitem = $surveyitem;
        }

        return $structure;
    }

    #[\Override]
    public function check_and_save_answers(array $answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            $metadata = $answer['additionaldata'];
            if (
                !is_number($answer['answer']) ||
                $answer['answer'] == 0 && !$metadata->hasnoansweroption ||
                $answer['answer'] < 0 ||
                $answer['answer'] > $metadata->optionamount
            ) {
                throw new coding_exception('Answer ' . json_encode($answer) . ' is not a valid one');
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer['respsetid'],
                'surveyitemid' => $answer['surveyitemid'],
                'value' => $answer['answer'],
            ];
        }
        $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
    }
}
