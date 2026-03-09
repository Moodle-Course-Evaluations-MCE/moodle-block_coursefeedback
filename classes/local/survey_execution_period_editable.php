<?php
// This file is part of the QuestionPy Moodle plugin - https://questionpy.org
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

use block_coursefeedback\local\persistent\survey_execution;
use context_course;
use core\exception\coding_exception;
use core\invalid_persistent_exception;
use core\output\inplace_editable;
use core_date;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * A {@link inplace_editable} for the survey execution period of a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution_period_editable extends inplace_editable {

    /**
     * Initializes a new instance.
     *
     * @param survey_execution $survey_execution
     * @param string $property_key
     * @throws \coding_exception
     */
    public function __construct(survey_execution $survey_execution, string $property_key) {
        $context = context_course::instance($survey_execution->get('courseid'));
        $editable = has_capability('block/coursefeedback:changecoursesurveyperiod', $context);

        $timestamp = $survey_execution->get($property_key);
        $displayvalue = userdate_htmltime($timestamp, get_string('strftimedatetimeshort', 'langconfig'));
        $timezone = core_date::get_user_timezone_object();
        $isovalue = (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format(DateTimeInterface::ATOM);

        parent::__construct(
            component: 'block_coursefeedback',
            itemtype: "survey_execution_$property_key",
            itemid: $survey_execution->get('id'),
            editable: $editable,
            displayvalue: $displayvalue,
            value: $isovalue
        );
    }

    /**
     * Updates a survey execution period with a new value.
     *
     * @param string $itemtype `survey_execution_starttime` or `survey_execution_endtime`.
     * @param string $itemid The ID of the survey execution item to update.
     * @param string $newvalue The new value in ISO 8601 format.
     * @return static|null
     */
    public static function update(string $itemtype, string $itemid, string $newvalue): ?static {
        global $PAGE;
        require_login();

        $property_key = str_replace('survey_execution_', '', $itemtype);

        if (!is_number($itemid)) {
            throw new coding_exception("Invalid itemid: '$itemid'");
        }

        $survey_execution = survey_execution::get_record(['id' => intval($itemid)], MUST_EXIST);
        /** @var context_course $context */
        $context = context_course::instance($survey_execution->get('courseid'));
        $PAGE->set_context($context);
        require_capability('block/coursefeedback:changecoursesurveyperiod', $context);

        $datetime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $newvalue);
        if (!$datetime) {
            throw new coding_exception("Invalid value: '$newvalue'");
        }

        $survey_execution->set($property_key, $datetime->getTimestamp());
        $survey_execution->update();

        return new static($survey_execution, $property_key);
    }
}
