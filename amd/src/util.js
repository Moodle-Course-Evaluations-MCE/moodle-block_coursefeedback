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

import * as Toast from 'core/toast';
import Ajax from 'core/ajax';

/**
 * Makes a single AJAX call. If that call throws, shows the message in a toast and returns null.
 * @param {Object} call
 * @returns {Promise<*|null>}
 */
export async function ajaxAndHandleError(call) {
    try {
        return await Ajax.call([call])[0];
    } catch (error) {
        await Toast.add(error.message, {type: "danger"});
        throw error;
    }
}

/**
 * Turns a NodeList or HTMLCollection into an array.
 * @param {null|undefined|Array|NodeList|HTMLCollection|Object} maybeNodeList
 * @returns {Array}
 */
export function toArray(maybeNodeList) {
    // eslint-disable-next-line no-eq-null
    if (maybeNodeList == null) {
        return [];
    }
    if (Array.isArray(maybeNodeList)) {
        return maybeNodeList;
    }
    if (maybeNodeList instanceof NodeList || maybeNodeList instanceof HTMLCollection) {
        return [...maybeNodeList];
    }

    return [maybeNodeList];
}
