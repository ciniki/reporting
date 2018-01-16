<?php
//
// Description
// ===========
// This function runs the report and builds the text and pdf versions.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_reporting_reportRun($ciniki, $tnid, $report_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportExec');
    $rc = ciniki_reporting_reportExec($ciniki, $tnid, $report_id);
    if( $rc['stat'] != 'ok' ) {
        //
        // Email the error code and information, that way they know the report ran but there was a problem.
        //
        $report = array(
            'text'=>"There was an error processing the report.\n\n" . print_r($rc, true),
            'html'=>"<p>There was an error processing the report.</p><br/><br/>" . print_r($rc, true),
            );
        //
        // FIXME: Email sysadmins as well
        //
        
    } else {
        $report = $rc['report'];
    }

    //
    // Update the next run for the report
    //
    $dt = new DateTime($report['next_dt'], new DateTimezone('UTC'));
    if( $report['frequency'] == 10 ) {
        $dt->add(new DateInterval('P1D'));
    } elseif( $report['frequency'] == 30 ) {
        $dt->add(new DateInterval('P7D'));
    } elseif( $report['frequency'] == 50 ) {
        $dt->add(new DateInterval('P1M'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.reporting.report', $report['id'], array('next_date'=>$dt->format('Y-m-d H:i:s'))); 
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.18', 'msg'=>'Unable to update the next run time', 'err'=>$rc['err']));
    }


    //
    // Create the email 
    //
    if( isset($report['text']) && isset($report['user_ids']) && count($report['user_ids']) > 0 ) {
        $filename = preg_replace("/[^0-9a-zA-Z ]/", "", $report['title']);
        $filename = preg_replace("/ /", '-', $filename);
        //
        // Get the users email
        //
        $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
            . "FROM ciniki_users "
            . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $report['user_ids']) . ") "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.17', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        foreach($rc['rows'] as $user) {
            $name = $user['name'];
            $email = $user['email'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'customer_email'=>$email,
                'customer_name'=>$name,
                'subject'=>$report['title'],
                'html_content'=>$report['html'],
                'text_content'=>$report['text'],
                'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename . '.pdf')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
        }
    }

    return array('stat'=>'ok');
}
?>
