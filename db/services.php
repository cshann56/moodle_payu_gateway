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

$services = array(
    'payuindia_validpayinfo' => array(     // the name of the web service
        'functions' =>
            array ('paygw_payuindia\external\get_hash'), // web service functions of this service
        'requiredcapability' => '',     // if set, the web service user need this capability to access 
                                        // any function of this service. For example: 'some/capability:specified'                 
        'restrictedusers' => 0,         // if enabled, the Moodle administrator must link some user to this service
                                        // into the administration
        'enabled' => 1,                 // if enabled, the service can be reachable on a default installation
        'shortname' =>  'payuindia_validpayinfo', // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 0,           // allow file downloads.
        'uploadfiles'  => 0             // allow file uploads.
    ),
    'payuindia_stateregioninfo' => array(     // the name of the web service
        'functions' =>
            array ('paygw_payuindia\external\get_stateregioninfo'), // web service functions of this service
        'requiredcapability' => '',     // if set, the web service user need this capability to access 
                                        // any function of this service. For example: 'some/capability:specified'                 
        'restrictedusers' => 0,         // if enabled, the Moodle administrator must link some user to this service
                                        // into the administration
        'enabled' => 1,                 // if enabled, the service can be reachable on a default installation
        'shortname' =>  'payuindia_stateregioninfo', // optional – but needed if restrictedusers is set so as to allow logins.
        'downloadfiles' => 0,           // allow file downloads.
        'uploadfiles'  => 0             // allow file uploads.
    )
);


$functions = array(
    'paygw_payuindia_get_hash' => array(            //web service function name
        'classname'   => 'paygw_payuindia\external\webservices', //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_hash',                //external function name
        // 'classpath'   => 'local/myplugin/externallib.php',  //file containing the class/external function - not required if using namespaced auto-loading classes.
                                                    // defaults to the service's externalib.php
        'description' => 'Calculates hash for PayU India plugin.',     //human readable description of the web service function
        'type'        => 'write',                   //database rights of the web service function (read, write)
        'ajax' => true,                             // is the service available to 'internal' ajax calls. 
        'services' => array('payuindia_validpayinfo',MOODLE_OFFICIAL_MOBILE_SERVICE), // Optional, only available for Moodle 3.1 onwards.
                                                    // List of built-in services (by shortname) where the function will be included.
                                                    // Services created manually via the Moodle interface are not supported.
        'capabilities' => '',                       // comma separated list of capabilities used by the function.
    ),
    'paygw_payuindia_get_stateregioninfo' => array(            //web service function name
        'classname'   => 'paygw_payuindia\external\webservices', //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_stateregioninfo',                //external function name
        'description' => 'Gets the list of states or regions of a country.',     //human readable description of the web service function
        'type'        => 'write',                   //database rights of the web service function (read, write)
        'ajax' => true,                             // is the service available to 'internal' ajax calls. 
        'services' => array('payuindia_stateregioninfo',MOODLE_OFFICIAL_MOBILE_SERVICE), // Optional, only available for Moodle 3.1 onwards.
                                                    // List of built-in services (by shortname) where the function will be included.
                                                    // Services created manually via the Moodle interface are not supported.
        'capabilities' => '',                       // comma separated list of capabilities used by the function.
    ),
);
