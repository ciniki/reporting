<?php
//
// Description
// ===========
// This function will load all the data for a report.
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
function ciniki_reporting_reportLoad($ciniki, $tnid, $report_id) {
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Load the report
    //
    $strsql = "SELECT ciniki_reporting_reports.id, "
        . "ciniki_reporting_reports.title, "
        . "ciniki_reporting_reports.category, "
        . "ciniki_reporting_reports.frequency, "
        . "ciniki_reporting_reports.flags, "
        . "ciniki_reporting_reports.skip_days, "
        . "ciniki_reporting_reports.next_date AS next_dt, "
        . "ciniki_reporting_reports.next_date, "
        . "ciniki_reporting_reports.next_date AS next_time "
        . "FROM ciniki_reporting_reports "
        . "WHERE ciniki_reporting_reports.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_reporting_reports.id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'category', 'frequency', 'flags', 'skip_days',
                'next_dt', 'next_date', 'next_time',
                ),
            'utctotz'=>array(   
                'next_date'=>array('format'=>$date_format, 'timezone'=>$intl_timezone),
                'next_time'=>array('format'=>$time_format, 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.12', 'msg'=>'Reports not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['reports'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.13', 'msg'=>'Unable to find Reports'));
    }
    $report = $rc['reports'][0];

    //
    // Get the users for the report
    //
    $strsql = "SELECT id, uuid, user_id "
        . "FROM ciniki_reporting_report_users "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reporting', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $users = $rc['rows'];
        foreach($users as $user) {
            $report['user_ids'][] = $user['user_id'];
        }
    }

    //
    // Get the blocks for the reports
    //
    $strsql = "SELECT id, btype, title, sequence, block_ref, options "
        . "FROM ciniki_reporting_report_blocks "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $report_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'blocks', 'fname'=>'id', 'fields'=>array('id', 'btype', 'title', 'sequence', 'block_ref', 'options')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['blocks']) ) {
        $blocks = $rc['blocks'];
        foreach($blocks as $block) {
            $block['options'] = unserialize($block['options']);
            $report['blocks'][] = $block;
        }
    }

    return array('stat'=>'ok', 'report'=>$report);
}
?>
