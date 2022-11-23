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
 * This is an empty module, that is required before all other modules.
 * Because every module is returned from a request for any other module, this
 * forces the loading of all modules with a single request.
 *
 * @module     paygw_payuindia/payusubmit
 * @package
 * @copyright  2022 Christopher Shannon <cshannon108@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.11
 */

import jQuery from 'jquery';
import Ajax from 'core/ajax';
import notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';

export const init = () => {

    /**
    * Returns true if all fields are valid.
    */
    function validateVisibleFields() {

        var retval = false;
        var curFocusedElem = jQuery(":focus")[0];

        // Causes a validation event to occur by triggering focus and blur events.
        jQuery("#payuworkform input").filter(
            "[name='email']," +
            "[name='phone']," +
            "[name='firstname']," +
            "[name='lastname']," +
            "[name='address1']," +
            "[name='address2']," +
            "[name='city']," +
            "[name='state']," +
            "[name='country']," +
            "[name='zipcode']"
            ).each(function(index, elem) {
                    elem.focus();
                    elem.blur();
                });

        // Return to original focused element
        curFocusedElem.focus();

        // Now find out how many elements are aria-invalid=true
        var numInvalid = jQuery("#payuworkform input[aria-invalid='true']").length;

        if (numInvalid == 0) {
            retval = true;
        }

        return retval;
    }

    function sendFormDataToRemote() {
        // Disable the visible input fields.
        jQuery("#payuworkform input[type='text']").prop('disabled', true);
        jQuery("#payuworkform select").prop('disabled', true);

        // Disable the pay and cancel buttons.
        jQuery("#payusubmitbutton").prop('disabled', true);
        jQuery("#payucancelbutton").prop('disabled', true);

        // call the web service to get the hash of the reuqired fields.
        var fieldvals = {};
        jQuery("#payuworkform input,select").filter(
            "[name='component']," +
            "[name='paymentarea']," +
            "[name='itemid']," +
            "[name='productinfo']," +
            "[name='amount']," +
            "[name='additional_charges']," +
            "[name='email']," +
            "[name='phone']," +
            "[name='firstname']," +
            "[name='lastname']," +
            "[name='address1']," +
            "[name='address2']," +
            "[name='city']," +
            "[name='state']," +
            "[name='country']," +
            "[name='zipcode']," +
            "[name='udf1']"
            ).each(function(index, elem) {
                    fieldvals[elem.name] = elem.value;
                });

        var promises = Ajax.call([
            {
                methodname: 'paygw_payuindia_get_hash',
                args: { inputparams: [{
                    component: fieldvals["component"],
                    paymentarea: fieldvals["paymentarea"],
                    itemid: fieldvals["itemid"],
                    productinfo: fieldvals["productinfo"],
                    amount: fieldvals["amount"],
                    additional_charges: fieldvals["additional_charges"],
                    email: fieldvals["email"],
                    phone: fieldvals["phone"],
                    firstname: fieldvals["firstname"],
                    lastname: fieldvals["lastname"],
                    address1: fieldvals["address1"],
                    address2: fieldvals["address2"],
                    city: fieldvals["city"],
                    state: fieldvals["state"],
                    country: fieldvals["country"],
                    zipcode: fieldvals["zipcode"],
                    udf1: fieldvals["udf1"]
                    }] }
            }]);

        promises[0].done(function(response) {
            response = response[0];

            // Now update the fields.
            jQuery("#payuworkform input[name='txnid']").val(response['txnid']);
            jQuery("#payuworkform input[name='key']").val(response['key']);
            jQuery("#payuworkform input[name='hash']").val(response['hash']);

            // Update the send form using values from the work form.
            var mysendobjs = jQuery("#payusendform input,select");

            mysendobjs.each(
                function(index, sendelem) {
                    var workelem = jQuery("#payuworkform input,select").filter("[name='" + sendelem.name + "']")[0];
                    sendelem.value = workelem.value;
                }
             );

            // Submit the send to form.
            var sendform = document.getElementById("payusendform");

            sendform.submit();

        }).fail(function(ex) {
            // Failure, so re-enable the fields and show a popup with the exception.
            jQuery("#payuworkform input[type='text']").prop('disabled', false);
            jQuery("#payuworkform select").prop('disabled', false);
            jQuery("#payusubmitbutton").prop('disabled', false);
            jQuery("#payucancelbutton").prop('disabled', false);
            notification.exception(ex);
        });
    }

    function cancelTransaction() {

        var courseId = jQuery("#payuworkform input[name='courseid']").val();
        var wwwRoot  = jQuery("#payuworkform input[name='wwwroot']").val();

        var redirectUrl = wwwRoot + '/enrol/index.php?id=' + courseId;

        // We don't want them coming back to this page with the back button.
        // So, we simulate an HTTP recirect.
        window.location.replace(redirectUrl);
    }

    var myActionButton = document.getElementById("payusubmitbutton");

    myActionButton.onclick = function() {

        // Validate form using client side.
        var validationResult = validateVisibleFields();

        // exit function if validation result is not passed.
        if (! validationResult) {
            return;
        } else { // Validation passed, so popup a modal form asking if user wants to continue

            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: 'Submit Payment',
                body: 'Pressing OK will redirect your browser to '+
                      'another website that will process your '+
                      'payment. After the transaction is complete, '+
                      'your browser will be redirected back to '+
                      'this website. Do you want to continue?'
                })
                .then(function(modal) {
                    modal.setSaveButtonText('OK');
                    var root = modal.getRoot();
                    root.on(ModalEvents.save, sendFormDataToRemote);
                    modal.show();
                });
        }

    };

    var myCancelButton = document.getElementById("payucancelbutton");

    myCancelButton.onclick = function() {

        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: 'Cancel Payment',
            body: 'Pressing OK will leave this page, '+
                  'and all form data will be lost. '+
                  'Do you want to continue?'
            })
            .then(function(modal) {
                modal.setSaveButtonText('OK');
                var root = modal.getRoot();
                root.on(ModalEvents.save, cancelTransaction);
                modal.show();
            });
    };

    // Set up the Ajax call to repopulate states on change of country.

    var myCountriesSelect = jQuery('#payuworkform select[name="country"]');

    myCountriesSelect[0].onclick = function() {

        var curCountry = myCountriesSelect.val();

        var promises2 = Ajax.call([
            {
                methodname: 'paygw_payuindia_get_stateregioninfo',
                args: { country: curCountry }
            }]);

        promises2[0].done(function(response) {

            var staterecs  = response["records"].split("|");
            var stateField = jQuery("#payuworkform select[name='state']");

            // Now update the fields.
            stateField.empty();
            var sfDomObj = stateField[0]; // Get the HTMLDOM object.

            for (var i = 0; i < staterecs.length; i++) {
                var rec = staterecs[i].split(";");
                var opt = document.createElement("option");
                opt.value = rec[0];
                opt.text = rec[1];
                sfDomObj.add(opt);
            }

        }).fail(function(ex) {
            notification.exception(ex);
        });
    };
};
