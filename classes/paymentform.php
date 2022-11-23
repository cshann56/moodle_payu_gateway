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

/**
 * Plugin administration pages are defined here.
 *
 * @package     paygw_payuindia
 * @category    admin
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace paygw_payuindia;

defined('MOODLE_INTERNAL') || die();

use \moodleform;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once('./lib.php'); // Loads settings that all accounts will use.

class paymentform extends moodleform {

    private $remote_keyvals = array();
    private $display_keyvals = array();
    private $admin_keyvals = array();

    function __construct(array $rkeyvals, array $adminkeyvals) {

        $this->remote_keyvals   = $rkeyvals;
        $this->display_keyvals  = $this->display_keyvalues();
        $this->admin_keyvals    = $adminkeyvals;

        $send_to_remote = $this->admin_keyvals["remote"];

        // Determines if the target on the remote system is test or prod.

        if ($send_to_remote) {
            $remote_sys = ($this->admin_keyvals["testorprod"] == 'test') ? 
                REMOTE_SYS_TEST_URL : REMOTE_SYS_PROD_URL;
            $remote_sys = $remote_sys . '/' . REMOTE_SYS_PAYMENT_URL_SUBPATH;

            parent::__construct($remote_sys, null, 'post', null, ['id' => 'payusendform']); // Needs to go last to set defaults.
        } else {
            parent::__construct(null, null, null, null, ['id' => 'payuworkform']); // expliciltiy sets ID attribute.
        }

        // Disable javascript form checking.
        $this->_form->disable_form_change_checker();
    }

    /* The format of this associative array is: The key is the form field
    name that gets submitted. Then the array value for each key is:

    ['human-readable label', 'text' : input text or dropdown select [NOT USED]',
    length of field, true or false for required field, moodle form field type,
    regex character mask, error message if regex not working]

    */
    protected function display_keyvalues() {
        return [
            'country'       => ['Country', 'select', 50, true, PARAM_TEXT,      null, null],
            'email'         => ['Email','text',50,true,PARAM_EMAIL,             '/^[A-Z0-9+_.-]+@[A-Z0-9.-]+\\.[A-Z0-9]{2,}$/i', 'Email format invalid.'],
            'phone'         => ['Phone Number', 'text',50,true, PARAM_INT,      '/^\\d+$/',   'Numbers only for Phone.'],
            'firstname'     => ['First Name', 'text', 60, true, PARAM_TEXT,     '/^[a-zA-Z0-9@_\\/\\. -]+$/',   'First Name: illegal characters.'],
            'lastname'      => ['Last Name', 'text',20, true, PARAM_TEXT,       '/^[a-zA-Z0-9@_\\/\\. -]+$/',   'Last Name: illegal characters.'],
            'address1'      => ['Address 1', 'text', 100, true, PARAM_TEXT,     '/^[a-zA-Z0-9@_\\/\\. -]+$/',   'Address 1: illegal characters.'],
            'address2'      => ['Address 2', 'text', 100, false, PARAM_TEXT,    '/^[a-zA-Z0-9@_\\/\\. -]*$/',   'Address 2: illegal characters'],
            'city'          => ['City', 'text', 100, true, PARAM_TEXT,          '/^[a-zA-Z0-9@_\\/\\. -]+$/',   'City: illegal characters.'],
            'state'         => ['State/Region', 'select', 50, false, PARAM_TEXT,   null,   null],
            'zipcode'       => ['Zip/Postal Code','text',20, true, PARAM_INT,   '/^\\d+$/',                     'Numbers only for Zip/Postal Code.']
        ];
    }

    protected function definition() {

        global $DB;

        $mform = $this->_form;

        // We want to clear the automatically added elements, so we
        // invoke lower-level functions to determine what they are.

        // Add the configurable fields.

        // Adds hidden fields which the user may not modify.
        foreach ($this->remote_keyvals as $rkey => $rval) {
            $mform->addElement("hidden", $rkey, "");
            $mform->setType($rkey,PARAM_RAW);
            $mform->setDefault($rkey, $rval);
        }

        // Adds input fields which the user may modify.
        
        $send_to_remote = $this->admin_keyvals["remote"];

        foreach ($this->display_keyvals as $dkey => $dval) {

            if ($send_to_remote) {
                $mform->addElement("hidden", $dkey);
                $mform->setType($dkey,$dval[4]); // PARAM_RAW, PARAM_EMAIL, etc.
            } else {
                if ($dval[1] == 'text') {
                    $mform->addElement("text", $dkey, $dval[0], 'maxlength = "'.$dval[2].'" size = "'.ceil($dval[2]/2).'"');
                    $mform->setType($dkey,$dval[4]); // PARAM_RAW, PARAM_EMAIL, etc.
                    // $mform->setDefault($dkey, $dval[1]); // Index 1 is for the default value, and 0 is for the field label.

                    // Make required.
                    if ($dval[3]) {
                        $mform->addRule($dkey, 'Missing '.$dval[0].'.', 'required', null, 'client', false, false);
                    }

                    // Add regex
                    if ($dval[5] != null) {
                        $mform->addRule($dkey, $dval[6], 'regex', $dval[5], 'client', false, false);
                    }
                } else { // It's a select field, so we have to process it differently.
                    // Special, if country or state, load data from database.
                    if ($dkey == 'country' || $dkey == 'state') {
                        $tblname = $dkey == 'country' ? 'paygw_payuindia_countries' : 'paygw_payuindia_states';

                        $select_options = [];

                        if ($dkey == 'country') {
                            $recordset = $DB->get_records($tblname);

                            foreach ($recordset as $record) {
                                $select_options[$record->iso3] = $record->name;
                            }


                        } else {  // It's a state, so it has to be limited to country. Default is India.
                            $rec = $DB->get_record('paygw_payuindia_countries', ['iso3' => 'IND']);
                            $countryid = $rec->countryid;

                            $recordset = $DB->get_records($tblname, ['countryid' => $countryid]);
                            $select_options[''] = '[Select STATE]';
                            foreach ($recordset as $record) { 
                                $select_options[$record->state_code] = $record->name;
                            }
                        }
                    }

                    if ($dkey == 'country') {
                        // Disable the control for now, because we are only accepting India payments.
                        $select = $mform->addElement("select", $dkey, $dval[0], $select_options,
                           ["id" => "payu".$dkey, 'disabled' => 'disabled'] );
                        // $select = $mform->addElement("select", $dkey, $dval[0], $select_options,
                           // ["id" => "payu".$dkey] );
                    } else {
                        $select = $mform->addElement("select", $dkey, $dval[0], $select_options, "id='payu".$dkey."'");
                    }

                    if ($dkey == 'country') {
                        // Set India as default.
                        $select->setSelected('IND');
                    } else {
                        // Set state to not selected.
                        $select->setSElected(null);
                    }

                    $mform->setType($dkey,$dval[4]); // PARAM_RAW, PARAM_EMAIL, etc.

                    // Make required.
                    if ($dval[3]) {
                        $mform->addRule($dkey, $dval[0].' not selected.', 'required', null, 'client', false, false);
                    }   
                }
            }
        }

        // The hash element, which will be updated via ajax.
        $mform->addElement("hidden", "hash", "");
        $mform->setType("hash",PARAM_RAW);

        // Create button array for the visible working form.
        if (! $send_to_remote) {
            // Code lifted from lib/formslib.php, function add_action_buttons
            $buttonarray=array();
            $buttonarray[] = &$mform->createElement('button', 'paybutton', "Pay",
                [
                    "id" => "payusubmitbutton",
                    "style" => 'background-color: navy; color: white'
                ]
            );
            $buttonarray[] = &$mform->createElement('button', 'cancelbutton', 'Cancel', 
                ["id" => "payucancelbutton"]);
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }
    }

    // We want to remove elements from this.
    // This is a blacklist, but we can later move to a white list.
    function definition_after_data() {
        $send_to_remote = $this->admin_keyvals["remote"];
        
        if ($send_to_remote) {
            $this->_form->removeElement('component');
            $this->_form->removeElement('paymentarea');
            $this->_form->removeElement('itemid');
            $this->_form->removeElement('courseid');
            $this->_form->removeElement('wwwroot');
            $this->_form->removeElement("sesskey");
            $this->_form->removeElement('_qf__'.$this->_formname);
        }
    }
}
