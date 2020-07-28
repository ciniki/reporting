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
function ciniki_reporting_categories($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Report'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.categories');
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
        . "IF(reports.category='', 'Uncategorized', reports.category) AS category, "
        . "reports.title, "
        . "reports.frequency, "
        . "reports.flags, "
        . "reports.next_date "
        . "FROM ciniki_reporting_reports AS reports "
        . "WHERE reports.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY category, reports.title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'category', 'title', 'frequency', 'flags', 'next_date'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    
    $rsp = array('stat'=>'ok', 'categories'=>$categories);

    //
    // Load the report if specified
    //
    if( isset($args['report_id']) && $args['report_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportExec');
        $rc = ciniki_reporting_reportExec($ciniki, $args['tnid'], $args['report_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.27', 'msg'=>'Unable to execute report', 'err'=>$rc['err']));
        }
        $rsp['report'] = $rc['report'];
    }

    return $rsp;
}
?>
