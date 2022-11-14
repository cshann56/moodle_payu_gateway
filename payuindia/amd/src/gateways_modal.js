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
/**
 * Plugin administration pages are defined here.
 *
 * @module      paygw_payuindia/gateway_modal
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import ModalFactory from 'core/modal_factory';

const showModalWithPlaceholder = async() => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_payuindia/payuindia_button_placeholder', {})
    });
    modal.show();
};

export const process = (component, paymentArea, itemId, description) => {
    return showModalWithPlaceholder()
        .then(() => {
            location.href = M.cfg.wwwroot + '/payment/gateway/payuindia/pay.php?' +
                'component='    + component + '&' +
                'paymentarea='  + paymentArea + '&' +
                'itemid='       + itemId + '&' +
                'description='  + description;
            return new Promise(() => null);
        });
};
