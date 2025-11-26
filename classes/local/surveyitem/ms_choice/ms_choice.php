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
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\ms_choice;

use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitem\surveyitemtype;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ms_choice extends surveyitemtype {

    #[\Override]
    public function get_settings_mform() {
        return ms_choice_form::class;
    }

    #[\Override]
    public function save_settings_mform(int $surveyitemid, object $formdata, string $language): void {
        global $DB;
        $existingoptions = $DB->get_records(
            'block_coursefeedback_surveyitemansweroption',
            ['surveyitemid' => $surveyitemid],
            'sortindex ASC',
            'sortindex, id, textid'
        );
        $index = 0;
        for ($i = 1; $i <= $formdata->choices_amount; $i++) {
            if ($formdata->{'answer' . $i}) {
                $index++;
                if (isset($existingoptions[$i])) {
                    language_manager::update_string($existingoptions[$i]->textid, $formdata->{'answer' . $i}, $language);
                    if ($existingoptions[$i]->sortindex != $index) {
                        $existingoptions[$i]->sortindex = $index;
                        $DB->update_record('block_coursefeedback_surveyitemansweroption', $existingoptions[$i]);
                    }
                } else {
                    $textid = language_manager::create_string($formdata->{'answer' . $i}, $language);
                    $DB->insert_record('block_coursefeedback_surveyitemansweroption', [
                        'surveyitemid' => $surveyitemid,
                        'textid' => $textid,
                        'sortindex' => $index,
                    ]);
                }
            } else if (isset($existingoptions[$i])) {
                $DB->delete_records('block_coursefeedback_surveyitemansweroption', ['id' => $existingoptions[$i]->id]);
            }
        }
    }

    #[\Override]
    public function load_settings_mform(surveyitem $surveyitem, string $language): object {
        global $DB;
        $data = parent::load_settings_mform($surveyitem, $language);
        $existingoptions = $DB->get_records(
            'block_coursefeedback_surveyitemansweroption',
            ['surveyitemid' => $surveyitem->get('id')],
            'sortindex ASC',
            'sortindex, id, textid'
        );
        $data->choices_amount = count($existingoptions);
        for ($i = 1; $i <= $data->choices_amount; $i++) {
            $data->{'answer' . $i} = language_manager::fetch_string($existingoptions[$i]->textid, $language);
        }
        return $data;
    }

    #[\Override]
    public function load_questiondata_for(array $surveyitems): array {
        global $DB;
        [$textids, $additionaldata] = parent::load_questiondata_for($surveyitems);
        $surveyitemids = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemids[] = $surveyitem->get('id');
        }
        [$insql, $params] = $DB->get_in_or_equal($surveyitemids, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select(
            'block_coursefeedback_surveyitemansweroption',
            "surveyitemid $insql",
            $params,
            'sortindex'
        );
        foreach ($records as $record) {
            $textids[$record->surveyitemid]['option_' . $record->id] = $record->textid;
            if (!isset($additionaldata[$record->surveyitemid])) {
                $additionaldata[$record->surveyitemid] = [];
            }
            $additionaldata[$record->surveyitemid][] = $record;
        }

        return [$textids, $additionaldata];
    }

    #[\Override]
    public function create_question_structure(array $surveyitems, array $texts, array $additionaldata): array {
        $template_data = parent::create_question_structure($surveyitems, $texts, $additionaldata);
        foreach ($surveyitems as $surveyitem) {
            $template_data[$surveyitem->get('id')]['options'] = [];
            foreach ($additionaldata[$surveyitem->get('id')] as $option) {
                $template_data[$surveyitem->get('id')]['options'][] = [
                    'optiontext' => $texts[$surveyitem->get('id')]['option_' . $option->id],
                    'optionid' => $option->id,
                ];
            }
        }
        return $template_data;
    }
}
