<?php
//
// Description
// ===========
// This method will return all the information about an reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:                The ID of the tenant the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_reporting_reportPDF(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Report'),
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.reportPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Execute the report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportExec'); 
    $rc = ciniki_reporting_reportExec($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $report = $rc['report'];

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $filename = preg_replace("/[^0-9a-zA-Z ]/", "", $dt->format('Y M d') . ' ' . $report['title']);
    $filename = preg_replace("/ /", '-', $filename);

    //
    // Check if the report is to be emailed
    //
    if( isset($args['email']) && $args['email'] == 'test' ) {
        //
        // Get the users email
        //
        $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
            . "FROM ciniki_users "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.8', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        $name = $rc['user']['name'];
        $email = $rc['user']['email'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $args['tnid'], array(
            'customer_email'=>$email,
            'customer_name'=>$name,
            'subject'=>$dt->format('D M j, Y - ') . $report['title'],
            'html_content'=>$report['html'],
            'text_content'=>$report['text'],
            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$args['tnid']);
        return array('stat'=>'ok');
    } else {
        $report['pdf']->Output($filename . '.pdf', 'I');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok');
}
?>
