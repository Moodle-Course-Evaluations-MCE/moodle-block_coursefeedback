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

use block_coursefeedback\local\surveyitem\info\info;
use block_coursefeedback\local\surveyitem\multiplechoice\multiplechoice;
use block_coursefeedback\local\surveyitem\pagebreak\pagebreak;
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

    public static function get_all_surveyitemtypes(): array {
        static $surveyitemtypes = [
            // 'singlechoice' => null,
            'multiplechoice' => new multiplechoice(),
            'text' => new text(),
            'pagebreak' => new pagebreak(),
            'info' => new info(),
        ];
        return $surveyitemtypes;
    }

    public static function get_surveyitemtype(string $type): surveyitemtype {
        if (!isset(self::get_all_surveyitemtypes()[$type])) {
            throw new coding_exception('Survey element type ' .  $type . ' not found.');
        }
        return self::get_all_surveyitemtypes()[$type];
    }

}
