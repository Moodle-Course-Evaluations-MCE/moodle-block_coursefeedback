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

/**
 * Utility function dealing with languages.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class js_util {

    /**
     * Load an AMD module and eventually call its method without a parameter size warning.
     * {@see \page_requirements_manager::js_call_amd}
     *
     * This function creates a minimal inline JS snippet that requires an AMD module and eventually calls a single
     * function from the module with given arguments. If it is called multiple times, it will create multiple
     * snippets.
     *
     * @param string $fullmodule The name of the AMD module to load, formatted as <component name>/<module name>.
     * @param string $func Optional function from the module to call, defaults to just loading the AMD module.
     * @param array $params The params to pass to the function (will be serialized into JSON).
     */
    public static function js_call_amd($fullmodule, $func = null, $params = []) {
        global $PAGE;

        $modulepath = explode('/', $fullmodule);

        $modname = clean_param(array_shift($modulepath), PARAM_COMPONENT);
        foreach ($modulepath as $module) {
            $modname .= '/' . clean_param($module, PARAM_ALPHANUMEXT);
        }

        $functioncode = [];
        if ($func !== null) {
            $func = clean_param($func, PARAM_ALPHANUMEXT);

            $jsonparams = [];
            foreach ($params as $param) {
                $jsonparams[] = json_encode($param);
            }
            $strparams = implode(', ', $jsonparams);

            $functioncode[] = "amd.{$func}({$strparams});";
        }

        $functioncode[] = "M.util.js_complete('{$modname}');";

        $initcode = implode(' ', $functioncode);
        $js = "M.util.js_pending('{$modname}'); require(['{$modname}'], function(amd) {{$initcode}});";

        $PAGE->requires->js_amd_inline($js);
    }
}
