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
 * Allows for show/hiding advanced options in case of 'Numeric' or 'User defined' scale selection
 *
 * @module block_accessreview/module
 * @author      Mickael Clavreul <mickael.clavreul@eseo.fr>
 * @copyright   2025 ESEO Group <max@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str'], function($, Str) {
    return {
    init:function(){
        var scaleSelect = $('#id_s_block_coursefeedback_scale');
        var numericOptionRow = $('#admin-scalenumber').closest('.form-item');
        var userOptionRow = $('#admin-scaletexts').closest('.form-item');

        /**
         * Toggles advanced options for 'Numeric and 'User defined' scales, hides them otherwise.
         */
        function toggleScaleOptions() {
            Str.get_strings([
            {key: 'adminpage_html_headingscale2', component: 'block_coursefeedback'},
            {key: 'adminpage_html_headingscale3', component: 'block_coursefeedback'},
            ]).then(function(strings) {
                const numText = strings[0];
                const userText = strings[1];

                if (scaleSelect.val() === numText) {
                    numericOptionRow.show();
                    userOptionRow.hide();
                } else if(scaleSelect.val() === userText) {
                    numericOptionRow.hide();
                    userOptionRow.show();
                } else {
                    numericOptionRow.hide();
                    userOptionRow.hide();
                }
            });
        }

        // Initialize on page load
        toggleScaleOptions();

        // Update on change
        scaleSelect.on('change', toggleScaleOptions);
    }
    };
});

