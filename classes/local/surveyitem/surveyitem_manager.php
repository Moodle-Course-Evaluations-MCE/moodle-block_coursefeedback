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
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem;

use block_coursefeedback\local\manager\language_manager;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\info\info;
use block_coursefeedback\local\surveyitem\multiplechoice\multiplechoice;
use block_coursefeedback\local\surveyitem\pagebreak\pagebreak;
use block_coursefeedback\local\surveyitem\scalequestion\scalequestion;
use block_coursefeedback\local\surveyitem\singlechoice\singlechoice;
use block_coursefeedback\local\surveyitem\text\text;
use core\exception\coding_exception;

/**
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveyitem_manager {

    /**
     * Returns an associative array of all surveyitemtypes.
     * @return array
     */
    public static function get_all_surveyitemtypes(): array {
        static $surveyitemtypes = [
            'singlechoice' => new singlechoice(),
            'multiplechoice' => new multiplechoice(),
            'text' => new text(),
            'pagebreak' => new pagebreak(),
            'scalequestion' => new scalequestion(),
            'info' => new info(),
        ];
        return $surveyitemtypes;
    }

    /**
     * Returns one surveyitemtype for a identifier.
     * @param string $type
     * @return surveyitemtype
     */
    public static function get_surveyitemtype(string $type): surveyitemtype {
        if (!isset(self::get_all_surveyitemtypes()[$type])) {
            throw new coding_exception('Survey element type ' .  $type . ' not found.');
        }
        return self::get_all_surveyitemtypes()[$type];
    }

    /**
     * Fetches the surveyitems for the surveyparts.
     * @param surveypart[] $surveyparts
     * @return array [int $surveypartid => [surveyitem $surveyitem]]
     */
    public static function get_surveyitems_for_surveyparts(array $surveyparts): array {
        // TODO improve performance.
        $surveyitems = [];
        foreach ($surveyparts as $surveypart) {
            $surveyitems[$surveypart->get('id')] = $surveypart->get_surveyitems();
        }
        return $surveyitems;
    }

    /**
     * Groups the sets of surveyitems by surveyitemtype.
     * @param surveyitem[][] $surveyitemsets
     * @return array [string $surveyitemtype => [surveyitem $surveyitem]]
     */
    public static function group_surveyitems_by_type(array $surveyitemsets): array {
        $surveyitems_by_type = [];
        foreach ($surveyitemsets as $surveyitemset) {
            foreach ($surveyitemset as $surveyitem) {
                $surveyitemtype = $surveyitem->get('surveyitemtype');
                if (!isset($surveyitems_by_type[$surveyitemtype])) {
                    $surveyitems_by_type[$surveyitemtype] = [];
                }
                $surveyitems_by_type[$surveyitemtype][] = $surveyitem;
            }
        }
        return $surveyitems_by_type;
    }

    /**
     * Constructs the template data for a specific surveypart.
     * @param array $surveyparts
     * @param string $language
     * @return array template data
     */
    public static function get_templatedata_for_surveyparts(array $surveyparts, string $language): array {
        $surveyitemsets = self::get_surveyitems_for_surveyparts($surveyparts);
        $surveyitems_by_type = self::group_surveyitems_by_type($surveyitemsets);

        $alladditionaldata = [];
        $alltextids = [];
        $uniquetextids = [];

        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            [$textidsperitem, $additionaldata] = self::get_surveyitemtype($surveyitemtype)
                ->load_questiondata_for($surveyitemsoftype);
            $alladditionaldata[$surveyitemtype] = $additionaldata;
            $alltextids[$surveyitemtype] = $textidsperitem;
            foreach ($textidsperitem as $textids) {
                foreach ($textids as $textid) {
                    $uniquetextids[$textid] = $textid;
                }
            }
        }
        $strings = language_manager::fetch_strings($uniquetextids, $language);
        $template_data_by_type = [];
        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            $texts = [];

            foreach ($alltextids[$surveyitemtype] as $itemid => $textids) {
                $texts[$itemid] = [];
                foreach ($textids as $key => $textid) {
                    $texts[$itemid][$key] = $strings[$textid];
                }
            }
            $template_data_by_type[$surveyitemtype] = self::get_surveyitemtype($surveyitemtype)
                ->create_question_structure($surveyitemsoftype, $texts, $alladditionaldata[$surveyitemtype]);
        }

        $return = [];

        foreach ($surveyparts as $surveypart) {
            $surveyitemset = $surveyitemsets[$surveypart->get('id')];

            $template_data = [];
            $current_page = [];

            foreach ($surveyitemset as $surveyitem) {
                $surveyitemtype = $surveyitem->get('surveyitemtype');
                if ($surveyitemtype === 'pagebreak') {
                    $template_data[] = $current_page;
                    $current_page = [];
                    continue;
                }

                $current_page[] = $template_data_by_type[$surveyitemtype][$surveyitem->get('id')];
            }
            $template_data[] = $current_page;
            $return[$surveypart->get('id')] = $template_data;
        }

        return $return;
    }

    /**
     * Gets the questiondata ('additionaldata') for all surveyitems in the given surveyparts.
     * @param surveypart[] $surveyparts
     * @return array [string $surveyitemtype => [int $surveyitemid => mixed $data]]
     */
    public static function get_questiondata_for_surveyparts(array $surveyparts): array {
        $surveyitems = self::get_surveyitems_for_surveyparts($surveyparts);
        $surveyitems_by_type = self::group_surveyitems_by_type($surveyitems);

        $alladditionaldata = [];

        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            [$textidsperitem, $additionaldata] = self::get_surveyitemtype($surveyitemtype)
                ->load_questiondata_for($surveyitemsoftype);
            $alladditionaldata[$surveyitemtype] = $additionaldata;
        }

        return $alladditionaldata;
    }
}
