<?php

$en_lang = array(
/*Administrative messages */

/* Actions */
'ADMIN_ACTION_VIEW' =>'View',
'ADMIN_ACTION_DELETE' =>'Delete',
'ADMIN_ACTION_EDIT' =>'Edit',
'ADMIN_ACTION_SAVE' =>'Save',
'ADMIN_ACTION_ADD' =>'Add',
'ADMIN_ACTION_CANCEL' =>'Cancel',
'ADMIN_ACTION_DONE' => 'Done',
'ADMIN_ACTION_RESET' => 'Reset',
'ADMIN_ACTION_ADD_NEW' =>'Add new %s',
/* Response Messages */

'ADMIN_MSG_MISSING_PARAM' =>'<strong>Error!</strong> The web request is missing parameters, try again. If the problem persist, please constant support.',
'ADMIN_MSG_GENERIC_ERR' =>'<strong>Error!</strong> Oops! Something went wrong, try again. If the problem persist, please contact support.',
'ADMIN_MSG_DELETE_SUCCESS' =>'<strong>Success!</strong> %s has been deleted successfully.',
'ADMIN_MSG_DELETE_ERROR' => '<strong>Error!</strong> %s unable to be deleted',
'ADMIN_MSG_ADD_SUCCESS' =>'<strong>Success!</strong> %s has been added successfully.',
'ADMIN_MSG_UPDATE_SUCCESS' =>'<strong>Success!</strong> %s has been updated successfully',
'ADMIN_MSG_CONFIRM_DELETE' => 'Are you sure you wish to delete the %s?',

'ADMIN_NOTE_STATUS_ACTIVE' => 'Active',
'ADMIN_NOTE_STATUS_INACTIVE' => 'Inactive',
'ADMIN_NOTE_STATUS_ARCHIVED' => 'Archived',
'ADMIN_NOTE_INFO_POTENTIAL' => 'Potential',
'ADMIN_NOTE_INFO_INFORMATION' => 'Information',
'ADMIN_NOTE_INFO_PENDING_TASKS' => 'Pending Tasks',
'ADMIN_NOTE_INFO_GENERAL' => 'General',
'ADMIN_NOTE_INFO_OTHER' => 'Other',
'ADMIN_INVOICE_STATUS_UNPAID' => 'Unpaid',
'ADMIN_INVOICE_STATUS_PAID' => 'Paid',
'ADMIN_INVOICE_STATUS_PENDING' => 'Pending',
'ADMIN_INVOICE_STATUS_CANCELLED' => 'Cancelled',

'ADMIN_INVOICE_PAYMENT_METHOD_CREDIT' => 'Credit',
'ADMIN_INVOICE_PAYMENT_METHOD_MAIL' => 'Mail-in',
'ADMIN_INVOICE_PAYMENT_METHOD_PAYPAL' => 'PayPal',


'ADMIN_TICKET_STATUS_OPEN' => 'Open',
'ADMIN_TICKET_STATUS_CLOSED' => 'Closed',
'ADMIN_TICKET_STATUS_INPROGRESS' => 'In Progress',
'ADMIN_TICKET_STATUS_ONHOLD' => 'On Hold',


'ADMIN_TICKET_INFO_BUG' => 'Bug',
'ADMIN_TICKET_INFO_HELP' => 'Help',
'ADMIN_TICKET_INFO_REQUEST' => 'Request',
'ADMIN_TICKET_INFO_GENERAL' => 'General',


    /* Field names */

'ADMIN_FIELD_ROLE_ROLE_NAME' => 'Role name',
'ADMIN_FIELD_ROLE_MODULE' => 'Role module',
'ADMIN_FIELD_ROLE_CONTROLLER' => 'Role controller',
'ADMIN_FIELD_ROLE_METHOD' => 'Role method',
/* Language */


'PRIVILEDGED_ROLE' => 'Priviledged role',
'ROLE_PERMISSIONS' =>'Role permissions:',
'ROLE' =>'Role',
'ROLE_MANAGEMENT' =>'Roles',
'PERMISSION' =>'Permission',
'CLIENT' => 'Client',
'COMPANY_NAME' => 'Company Name',
'COMPANY_PHONE' => 'Phone',
'ADDRESS' => 'Address',
'RENEWAL_DATE' => 'Renewal date',
'CITY' => 'City',
'STATE' => 'State',
'ZIP' => 'Zipcode',
'SUMMARY' => 'Summary',
'SORT_ORDER' => 'Sort order',
'NOTE' => 'Note',
'NOTES' => 'Notes',
'NOTE_INFO_TYPE' => 'Note information type',
'NOTE_STATUS_TYPE' => 'Note status',
'MANAGE_CLIENT_NOTES' => 'Manage client notes',
'LICENSE' => 'License Information',
'INVOICES' => 'Invoices',
'INVOICE' => 'Invoice',
'TICKETS' => 'Tickets',
'TICKET_REPLY' => 'Reply',
'TICKET_TYPE' => 'Ticket type',
'TICKET_STATUS' => 'Ticket status',
'BILLING_INFORMATION' => 'Billing Information',
'CONTACT' => 'Contact',
'CONTACT_NAME' => 'Name',
'CONTACT_PHONE' => 'Phone',
'CONTACT_EMAIL' => 'Email',
'CLIENT_CONTACTS' => 'Contacts',
'STATUS' => 'Status',
    /* Pagination messages */
'PAGINATION_MSG_LAST' => '<strong>Error!</strong> This item is already at the bottom of the list.',
'PAGINATION_MSG_FIRST' => '<strong>Error!</strong> This item is already at the top of the list',

/* Validation messages */

'HTML_VALIDATE_MATCH' => '<strong>Error!</strong> The %s field must match', // fix lol
'HTML_VALIDATE_REQUIRED' => '<strong>Error!</strong> The %s field is required',
'HTML_VALIDATE_VALID_EMAIL' => '<strong>Error!</strong> The %s field is required to be a valid email address',
'HTML_VALIDATE_MAX_LEN' => '<strong>Error!</strong> The %s field needs to be shorter than %s character',
'HTML_VALIDATE_mAX_LEN2' => '<strong>Error!</strong> The %s field needs to be shorter than %s characters',
'HTML_VALIDATE_MIN_LEN' => '<strong>Error!</strong> The %s field needs to be longer than %s character',
'HTML_VALIDATE_MIN_LEN2' => '<strong>Error!</strong> The %s field needs to be longer than %s characters',
'HTML_VALIDATE_EXACT_LEN' => '<strong>Error!</strong> The %s field needs to be exactly %s character in length',
'HTML_VALIDATE_EXACT_LEN2' => '<strong>Error!</strong> The %s field needs to be exactly %s characters in length',
'HTML_VALIDATE_ALPHA' => '<strong>Error!</strong> The %s field may only contain alpha characters(a-z)',
'HTML_VALIDATE_ALPHA_NUMERIC' => '<strong>Error!</strong> The %s field may only contain alpha-numeric characters',
'HTML_VALIDATE_ALPHA_DASH' => '<strong>Error!</strong> The %s field may only contain alpha characters &amp; dashes',
'HTML_VALIDATE_NUMERIC' => '<strong>Error!</strong> The %s field may only contain numeric characters',
'HTML_VALIDATE_INTEGER' => '<strong>Error!</strong> The %s field may only contain a numeric value',
'HTML_VALIDATE_BOOLEAN' => '<strong>Error!</strong> The %s field may only contain a true or false value',
'HTML_VALIDATE_FLOAT' => '<strong>Error!</strong> The %s field may only contain a float value',
'HTML_VALIDATE_VALID_URL' => '<strong>Error!</strong> The %s field is required to be a valid URL',
'HTML_VALIDATE_URL_EXISTS' => '<strong>Error!</strong> The %s URL does not exist',
'HTML_VALIDATE_VALID_IP' => '<strong>Error!</strong> The %s field needs to contain a valid IP address',
'HTML_VALIDATE_VALID_CC' => '<strong>Error!</strong> The %s field needs to contain a valid credit card number',
'HTML_VALIDATE_VALID_DATE' => '<strong>Error!</strong> The %s field needs to be a valid date',
'HTML_VALIDATE_MIN_NUMERIC' => '<strong>Error!</strong> The %s field needs to be a numeric value that is equal to, or higher than %s',
'HTML_VALIDATE_MAX_NUMERIC' => '<strong>Error!</strong> The %s field needs to be a numeric value, equal to, or lower than %s',
'HTML_VALIDATE_IS_UNIQUE' => '<strong>Error!</strong> The value %s is already in use.',
'HTML_VALIDATE_COMMON_STRING' => '<strong>Error!</strong> The %s field only accepts alpha, numeric and punctuation.',

'TEXT_VALIDATE_MATCH' => 'Error! The %s field must match', // fix lol
'TEXT_VALIDATE_REQUIRED' => 'Error! The %s field is required',
'TEXT_VALIDATE_VALID_EMAIL' => 'Error! The %s field is required to be a valid email address',
'TEXT_VALIDATE_MAX_LEN' => 'Error! The %s field needs to be shorter than %s character',
'TEXT_VALIDATE_mAX_LEN2' => 'Error! The %s field needs to be shorter than %s characters',
'TEXT_VALIDATE_MIN_LEN' => 'Error! The %s field needs to be longer than %s character',
'TEXT_VALIDATE_MIN_LEN2' => 'Error! The %s field needs to be longer than %s characters',
'TEXT_VALIDATE_EXACT_LEN' => 'Error! The %s field needs to be exactly %s character in length',
'TEXT_VALIDATE_EXACT_LEN2' => 'Error! The %s field needs to be exactly %s characters in length',
'TEXT_VALIDATE_ALPHA' => 'Error! The %s field may only contain alpha characters(a-z)',
'TEXT_VALIDATE_ALPHA_NUMERIC' => 'Error! The %s field may only contain alpha-numeric characters',
'TEXT_VALIDATE_ALPHA_DASH' => 'Error! The %s field may only contain alpha characters &amp; dashes',
'TEXT_VALIDATE_NUMERIC' => 'Error! The %s field may only contain numeric characters',
'TEXT_VALIDATE_INTEGER' => 'Error! The %s field may only contain a numeric value',
'TEXT_VALIDATE_BOOLEAN' => 'Error! The %s field may only contain a true or false value',
'TEXT_VALIDATE_FLOAT' => 'Error! The %s field may only contain a float value',
'TEXT_VALIDATE_VALID_URL' => 'Error! The %s field is required to be a valid URL',
'TEXT_VALIDATE_URL_EXISTS' => 'Error! The %s URL does not exist',
'TEXT_VALIDATE_VALID_IP' => 'Error! The %s field needs to contain a valid IP address',
'TEXT_VALIDATE_VALID_CC' => 'Error! The %s field needs to contain a valid credit card number',
'TEXT_VALIDATE_VALID_DATE' => 'Error! The %s field needs to be a valid date',
'TEXT_VALIDATE_MIN_NUMERIC' => 'Error! The %s field needs to be a numeric value that is equal to, or higher than %s',
'TEXT_VALIDATE_MAX_NUMERIC' => 'Error! The %s field needs to be a numeric value, equal to, or lower than %s',
'TEXT_VALIDATE_IS_UNIQUE' => 'Error! The value %s is already in use.',
'TEXT_VALIDATE_COMMON_STRING' => 'Error! The %s field only accepts alpha, numeric and punctuation.'


);