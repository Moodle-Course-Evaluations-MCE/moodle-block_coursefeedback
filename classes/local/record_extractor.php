<?php
// This file is part of Moodle - https://questionpy.org
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

namespace block_coursefeedback\local;

use core\exception\coding_exception;
use core\persistent;
use Generator;
use moodle_recordset;

/**
 * Wraps a {@see moodle_recordset} and simplifies extracting records and advancing to the next row when necessary.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_extractor {

    /** @var array Tracks which prefixes have been consumed from the current row. */
    private array $consumedfromcurrent = [];

    /**
     * Initializes a new instance.
     *
     * @param moodle_recordset $recordset
     */
    public function __construct(
        /** @var moodle_recordset $recordset */
        private readonly moodle_recordset $recordset
    ) {
    }

    /**
     * Extracts the columns with the given prefix.
     *
     * This can be used to separate the result of a query with many joins into separate records.
     *
     * @param object $row
     * @param string $prefix The prefix of the columns to extract.
     * @return object|null The extracted record if at least one column is non-null, otherwise null.
     */
    public static function maybe_extract(object $row, string $prefix): ?object {
        $extracted = persistent::extract_record($row, $prefix);
        if (!((array) $extracted)) {
            // This most likely means that the prefix is wrong.
            throw new coding_exception("Got empty record from row when using prefix '$prefix'.");
        }
        if (!array_filter((array) $extracted, fn($value) => $value !== null)) {
            // The columns exist, but all are null (even the ID), so it was most likely an outer join that matched nothing.
            return null;
        }

        return $extracted;
    }

    /**
     * Gets the record with the given prefix from the same row as the last record yielded by {@see yield_records()}.
     *
     * @param string $prefix
     * @return object|null
     */
    public function get_related(string $prefix): ?object {
        if (!$this->recordset->valid()) {
            throw new coding_exception("No current row.");
        }

        $row = $this->recordset->current();
        return self::maybe_extract($row, $prefix);
    }

    /**
     * Yields records with the given prefix until reaching a row where the condition is _not_ met.
     *
     * @param string $prefix
     * @param callable|null $condition
     * @return Generator
     */
    public function yield_records(string $prefix, ?callable $condition = null): Generator {
        while (true) {
            if (in_array($prefix, $this->consumedfromcurrent, strict: true)) {
                $this->recordset->next();
                $this->consumedfromcurrent = [];
            }

            if (!$this->recordset->valid()) {
                return;
            }
            $row = $this->recordset->current();

            if ($condition && !$condition($row)) {
                return;
            }

            $record = self::maybe_extract($row, $prefix);
            $this->consumedfromcurrent[] = $prefix;
            if (!$record) {
                return;
            }
            yield $record;
        }
    }
}
