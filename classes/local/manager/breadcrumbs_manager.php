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
 * Breadcrumbs manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use moodle_url;

/**
 * Breadcrumbs manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class breadcrumbs_manager {

    /**
     * Sets up the root 'Surveys' navigation node.
     * @return \navigation_node
     */
    public static function setup_surveys(): \navigation_node {
        global $PAGE;
        $PAGE->navbar->includesettingsbase = true;
        $node = $PAGE->navigation->add(
            get_string('surveys', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveyparts.php'),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for a specific survey.
     * @param surveypart $surveypart
     * @return \navigation_node
     */
    public static function setup_survey(surveypart $surveypart): \navigation_node {
        $parent = self::setup_surveys();
        $node = $parent->add(
            $surveypart->get('name'),
            new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $surveypart->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for editing a survey.
     * @param surveypart|null $surveypart
     * @return \navigation_node
     */
    public static function setup_edit_survey(?surveypart $surveypart): \navigation_node {
        $parent = self::setup_surveys();
        $params = [];
        if ($surveypart) {
            $params['id'] = $surveypart->get('id');
        }
        $node = $parent->add(
            get_string($surveypart ? 'edit_surveypart' : 'new_surveypart', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveypart_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for editing a surveyitem.
     * @param surveypart $surveypart
     * @param surveyitem|null $surveyitem
     * @return \navigation_node
     */
    public static function setup_edit_surveyitem(surveypart $surveypart, ?surveyitem $surveyitem): \navigation_node {
        $parent = self::setup_survey($surveypart);
        $params = ['surveypartid' => $surveypart->get('id')];
        if ($surveyitem) {
            $params['id'] = $surveyitem->get('id');
        }
        $node = $parent->add(
            get_string($surveyitem ? 'edit_surveyitem' : 'new_surveyitem', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveyitem_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for the scales overview.
     * @param surveypart $surveypart
     * @return \navigation_node
     */
    public static function setup_survey_scales(surveypart $surveypart): \navigation_node {
        $parent = self::setup_survey($surveypart);
        $node = $parent->add(
            get_string('scales', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/scales.php', ['surveypartid' => $surveypart->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the edit scale navigation node.
     * @param surveypart $surveypart
     * @param int|null $scaleid
     * @return \navigation_node
     */
    public static function setup_edit_survey_scale(surveypart $surveypart, ?int $scaleid): \navigation_node {
        $parent = self::setup_survey($surveypart);
        $params = ['surveyitemid' => $surveypart->get('id')];
        if ($scaleid) {
            $params['id'] = $scaleid;
        }
        $node = $parent->add(
            get_string($scaleid ? 'edit_scale' : 'new_scale', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/scale_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }
}
