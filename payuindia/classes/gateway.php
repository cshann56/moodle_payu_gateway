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
 * Contains class for Stripe payment gateway.
 *
 * @package    paygw_stripe
 * @copyright  2021 Alex Morris <alex@navra.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payuindia;

use core_payment\form\account_gateway;

/**
 * The gateway class for Stripe payment gateway.
 *
 * @copyright  2021 Alex Morris <alex@navra.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * The full list of currencies supported by PayU India.
     *
     * {@link https://stripe.com/docs/currencies}
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return[
            "INR","USD","EUR","JPY","GBP","CHF","SEK","DKK","NOK",
            "SGD","AUD","CAD","AED","HKD","QAR","SAR","OMR","ZAR",
            "MYR","KWD","MUR","LKR","KES","NZD","THB","BDT","CNY",
            "NPR","BHD"
            ];
    }


    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        // Selects test or production system.
        $mform->addElement(
            'select', 'testorprod', get_string('testorprod_label', 'paygw_payuindia'),
            [
                'test'  => get_string('testsys', 'paygw_payuindia'),
                'prod'  => get_string('prodsys', 'paygw_payuindia')
            ]);
        $mform->setType('testorprod', PARAM_TEXT);

        // Add the remote account id 
        $mform->addElement('text', 'remoteid', get_string('remoteid_label', 'paygw_payuindia'));
        $mform->setType('remoteid', PARAM_RAW);

        // Add the remote account key
        $mform->addElement('text', 'remotekey', get_string('remotekey_label', 'paygw_payuindia'));
        $mform->setType('remotekey', PARAM_RAW);

        // Add the remote account salt 
        $mform->addElement('text', 'remotesalt', get_string('remotesalt_label', 'paygw_payuindia'));
        $mform->setType('remotesalt', PARAM_RAW);

        // Add the remote live key
        $mform->addElement('text', 'remotekeylive', get_string('remotekeylive_label', 'paygw_payuindia'));
        $mform->setType('remotekeylive', PARAM_RAW);

        // Add the remote live account salt 
        $mform->addElement('text', 'remotesaltlive', get_string('remotesaltlive_label', 'paygw_payuindia'));
        $mform->setType('remotesaltlive', PARAM_RAW);

        // Add a prefix for the transaction id (txnid)
        $mform->addElement('text', 'transactionprefix',
            get_string('transactionprefix_label', 'paygw_payuindia'),
            'maxlength="10" size="10"');
        $mform->setType('transactionprefix', PARAM_RAW);

        // Add a test IP address for webhooks testing.
        $mform->addElement('text', 'testwebhook',
            get_string('testwebhook_label', 'paygw_payuindia'));
        $mform->setType('testwebhook', PARAM_RAW);
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(account_gateway $form,
        \stdClass $data, array $files, array &$errors): void {
    }
}
