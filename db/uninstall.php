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
 * Uninstall routines
 *
 * @package    block_coursefeedback
 * @subpackage coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_block_coursefeedback_uninstall() {
    global $DB;

    $dbman = $DB->get_manager();

    $tbls = [
        "block_coursefeedback",
        "block_coursefeedback_questns",
        "block_coursefeedback_answers",
        "block_coursefeedback_uidansw",
    ]; // Tables marked for deletion.

    foreach ($tbls as &$tbl) {
        if ($dbman->table_exists($tbl)) {
            $dbman->drop_table(new xmldb_table($tbl));
            $tbl = !$dbman->table_exists($tbl);
        } else {
            $tbl = 0;
        }
    }

    return array_product($tbls);
}
