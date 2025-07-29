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

import * as Ajax from 'core/ajax';

/**
 * Does dragging and dropping.
 *
 * @module     block_coursefeedback/drag-and-drop-reorder
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize drag and drop reorder.
 * @param {number} surveyPartId
 */
export function init(surveyPartId) {
    const list = document.querySelector('.coursefeedback-dnd-list');

    let movingElement = null;
    let startY = null;
    let lastMouseClientY = null;

    const pointerDownListener = (event) => {
        const handle = event.target.closest('.coursefeedback-dnd-handle');
        if (!handle) {
            return false;
        }
        movingElement = handle.closest('.coursefeedback-dnd-item');
        movingElement.classList.add('grabbed');
        startY = event.pageY;
        lastMouseClientY = event.clientY;
        list.setPointerCapture(event.pointerId);
        requestAnimationFrame(doScrolling);
        return true;
    };

    const doScrolling = () => {
        if (!movingElement) {
            return;
        }
        const SCROLL_ZONE_PERCENTAGE = 0.3;
        const MAX_SPEED = 10;
        const rect = movingElement.getBoundingClientRect();
        if (rect.top < window.innerHeight * SCROLL_ZONE_PERCENTAGE) {
            const ratioInScrollZone = 1 - (rect.top / window.innerHeight) / SCROLL_ZONE_PERCENTAGE;
            window.scrollBy({top: -Math.pow(ratioInScrollZone, 2) * MAX_SPEED});
        } else if (rect.bottom > window.innerHeight * (1 - SCROLL_ZONE_PERCENTAGE)) {
            const ratioInScrollZone = ((rect.bottom / window.innerHeight) - (1 - SCROLL_ZONE_PERCENTAGE)) / SCROLL_ZONE_PERCENTAGE;
            window.scrollBy({top: Math.pow(ratioInScrollZone, 2) * MAX_SPEED});
        }

        move();

        requestAnimationFrame(doScrolling);
    };

    const move = () => {
        let offset = (lastMouseClientY + window.scrollY) - startY;
        const oldScroll = window.scrollY;
        if (offset < 0) {
            while (movingElement.previousElementSibling) {
                const movingElementMidpoint = movingElement.offsetTop + movingElement.offsetHeight * 0.5;
                const previousSibling = movingElement.previousElementSibling;
                const limit = previousSibling.offsetTop + previousSibling.offsetHeight * 0.66;
                if (movingElementMidpoint + offset > limit) {
                    break;
                }
                offset += movingElement.offsetTop - previousSibling.offsetTop;
                startY -= movingElement.offsetTop - previousSibling.offsetTop;
                list.removeChild(movingElement);
                previousSibling.insertAdjacentElement('beforebegin', movingElement);
            }
            if (!movingElement.previousElementSibling) {
                offset = Math.max(0, offset);
            }
        } else {
            while (movingElement.nextElementSibling) {
                const movingElementMidpoint = movingElement.offsetTop + movingElement.offsetHeight * 0.5;
                const nextSibling = movingElement.nextElementSibling;
                const limit = nextSibling.offsetTop + nextSibling.offsetHeight * 0.33;
                if (movingElementMidpoint + offset < limit) {
                    break;
                }
                offset -= nextSibling.offsetTop - movingElement.offsetTop;
                startY += nextSibling.offsetTop - movingElement.offsetTop;
                list.removeChild(movingElement);
                nextSibling.insertAdjacentElement('afterend', movingElement);
            }
            if (!movingElement.nextElementSibling) {
                offset = Math.min(0, offset);
            }
        }
        // Reset scroll that happened because of DOM changes at top of viewport.
        window.scroll({top: oldScroll, behavior: 'instant'});
        movingElement.style.transform = 'translateY(' + offset + 'px)';
    };

    const pointerMoveListener = (event) => {
        if (!movingElement) {
            return false;
        }
        lastMouseClientY = event.clientY;
        move(event);
        return true;
    };

    const pointerUpListener = async(event) => {
        if (!movingElement) {
            return false;
        }

        lastMouseClientY = event.clientY;
        move(event);
        movingElement.style.transform = null;
        movingElement.classList.remove('grabbed');
        const index = [...list.querySelectorAll('.coursefeedback-dnd-item')].indexOf(movingElement);
        const id = movingElement.dataset.id;
        void updateSortindex(surveyPartId, id, index);
        movingElement = null;

        return true;
    };

    list.addEventListener('pointerdown', pointerDownListener);
    list.addEventListener('pointermove', pointerMoveListener);
    list.addEventListener('pointerup', pointerUpListener);
}

/**
 * Reorders a survey item.
 * @param {number} surveyPartId
 * @param {number} surveyItemId
 * @param {number} sortindex
 * @returns {Promise<void>}
 */
async function updateSortindex(surveyPartId, surveyItemId, sortindex) {
    await Ajax.call([{
        methodname: 'block_coursefeedback_reorder_surveyitem',
        args: {
            surveypartid: surveyPartId,
            id: surveyItemId,
            sortindex,
        }
    }])[0];
}
