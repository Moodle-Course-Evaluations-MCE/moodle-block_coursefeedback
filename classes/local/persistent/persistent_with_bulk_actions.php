<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\record_extractor;
use coding_exception;
use core\persistent;

/**
 * Extends {@see persistent} with bulk insert, delete, and diff methods.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_with_bulk_actions extends persistent {

    /**
     * Insert multiple instances at once, probably more efficiently than calling insert() multiple times.
     *
     * @param static[] $persistents
     * @return void
     */
    public static function bulk_insert(array $persistents): void {
        if (!$persistents) {
            return;
        }

        foreach ($persistents as $persistent) {
            if ($persistent->get('id') >= 1) {
                throw new coding_exception('Cannot insert persistent that already has an ID.');
            }

            $persistent->before_create();

            $now = time();
            $persistent->raw_set('timecreated', $now);
            $persistent->raw_set('timemodified', $now);
            global $USER;
            $persistent->raw_set('usermodified', $USER->id);
        }

        global $DB;
        $DB->insert_records(static::TABLE, array_map(fn($persistent) => $persistent->to_record(), $persistents));

        foreach ($persistents as $persistent) {
            $persistent->after_create();
        }
    }

    /**
     * Delete multiple instances at once, probably more efficiently than calling delete() multiple times.
     *
     * @param static[] $persistents
     * @return void
     */
    public static function bulk_delete(array $persistents): void {
        if (!$persistents) {
            return;
        }

        foreach ($persistents as $persistent) {
            if ($persistent->get('id') <= 0) {
                throw new coding_exception('Cannot delete persistent that has no ID.');
            }
            $persistent->before_delete();
        }

        global $DB;
        $DB->delete_records_list(static::TABLE, 'id', array_map(fn($persistent) => $persistent->get('id'), $persistents));

        foreach ($persistents as $persistent) {
            $persistent->after_delete(true);
            $persistent->raw_set('id', 0);
        }
    }

    /**
     * Ensures that when filtering by `$conditions`, exactly the persistents with the given `$values` are present.
     *
     * @param array $conditions
     * @param string $value_field
     * @param array $values
     * @return void
     */
    public static function diff_create_delete(array $conditions, string $value_field, array $values): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $existing = static::get_records($conditions);
        $existing_values = array_map(fn($persistent) => $persistent->get($value_field), $existing);

        $objs_to_remove = array_filter($existing, fn($persistent) => !in_array($persistent->get($value_field), $values));

        $values_to_add = array_filter($values, fn($value) => !in_array($value, $existing_values));
        $objs_to_add = array_map(fn($value) => new static(0, (object)[
            ...$conditions,
            $value_field => $value,
        ]), $values_to_add);

        static::bulk_delete($objs_to_remove);
        static::bulk_insert($objs_to_add);

        $transaction->allow_commit();
    }

    /**
     * Like {@see \moodle_database::record_exists()}, but for a persistent.
     *
     * @param array<string, mixed> $conditions
     * @return string
     */
    public static function record_exists_cond(array $conditions): string {
        global $DB;
        return $DB->record_exists(static::TABLE, $conditions);
    }

    /**
     * Shorthand for {@see record_extractor::maybe_extract()} and {@see persistent::__construct()}.
     *
     * @param object $record
     * @param string $prefix
     * @return static|null
     */
    public static function extract(object $record, string $prefix): ?static {
        $record = record_extractor::maybe_extract($record, $prefix);
        return $record ? new static(record: $record) : null;
    }
}
