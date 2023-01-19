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

namespace paygw_payuindia\event;

/**
 * The \\paygw\\payuindia\\event\\user_enrolment_created event class.
 *
 * @package     local_puregistrar
 * @category    event
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_payment_accepted extends \core\event\base {

    // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.

    protected function init() {
        $this->data['objecttable'] = 'paygw_payuindia_response';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }


    public static function get_name() {
        return get_string('eventuserenrolmentcreated', 'paygw_payuindia');
    }

    /**
     * Used to get the fields and user-friendly labels for display by
     * third-party plugins.
     *
     * @return an associatiave array of field IDs to human-readable labels.
     */
    public static function get_fields() {

        return array(
            'additional_charges' => 'Additional Charges',
            'address1' => 'Address1',
            'address2' => 'Address2',
            'amount' => 'Amount',
            'bankcode' => 'Bank Code',
            'city' => 'City',
            'country'   => 'Country',
            'course_enddate'    => 'Course Enddate',
            'course_fullname'   => 'Course Fullname', 
            'course_idnumber'   => 'Course ID number',
            'course_shortname'  => 'Course Shortname',
            'course_startdate'  => 'Course Startdate',
            'email' => 'Email',
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'paymentsource' => 'Payment Source',
            'payumode' => 'Payu Mode',
            'pg_type' => 'Payment Gateway Type',
            'productinfo' => 'Product Info',
            'state' => 'State',
            'status' => 'Status',
            'txnid' => 'Transaction ID',
            'zipcode' => 'Zipcode'
        );
    }
}
