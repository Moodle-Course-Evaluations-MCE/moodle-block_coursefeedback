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
 * Functions for use by the `upgrade.php`.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a new non-null field, setting it to the given default on all existing records. Xmldb can't do this natively.
 *
 * @param database_manager $dbman
 * @param xmldb_table $table
 * @param xmldb_field $field
 * @param mixed $default
 * @return void
 */
function add_nonnull_field_with_default(database_manager $dbman, xmldb_table $table, xmldb_field $field, mixed $default): void {
    global $DB;

    // We can't add a new non-null field without a default, but TEXT fields can't have defaults (for some reason).
    // So we add as nullable, then set our default and change to non-null.

    $field->setNotNull(false);
    $dbman->add_field($table, $field);
    $DB->execute("UPDATE {$table->getName()} SET {$field->getName()} = :default", ['default' => $default]);

    $field->setNotNull(XMLDB_NOTNULL);
    $dbman->change_field_notnull($table, $field);
}
