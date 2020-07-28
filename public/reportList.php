<?php
//
// Description
// -----------
// This method will return the list of Reports for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Reports for.
//
// Returns
// -------
//
function ciniki_reporting_reportList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.reportList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load timezone settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load reporting maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'maps');
    $rc = ciniki_reporting_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of reports
    //
    $strsql = "SELECT reports.id, "
        . "reports.title, "
        . "reports.frequency, "
        . "reports.frequency AS frequency_text, "
        . "reports.flags, "
        . "reports.next_date, "
        . "IFNULL(users.display_name, '') AS userlist "
        . "FROM ciniki_reporting_reports AS reports "
        . "LEFT JOIN ciniki_reporting_report_users AS u1 ON ("
            . "reports.id = u1.report_id "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_users AS users ON ("
            . "u1.user_id = users.id "
            . ") "
        . "WHERE reports.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY reports.next_date, reports.title, reports.id, users.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'frequency', 'frequency_text', 'flags', 'next_date', 'userlist'),
            'dlists'=>array('userlist'=>', '),
            'maps'=>array('frequency_text'=>$maps['report']['frequency']),
            'utctotz'=>array('next_date'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['reports']) ) {
        $reports = $rc['reports'];
        $report_ids = array();
        foreach($reports as $iid => $report) {
            $report_ids[] = $report['id'];
        }
    } else {
        $reports = array();
        $report_ids = array();
    }

    return array('stat'=>'ok', 'reports'=>$reports, 'nplist'=>$report_ids);
}
?>
