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
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    $rsp = array('stat'=>'ok');

    //
    // Check for start date 
    //
    if( !isset($args['start_date']) || $args['start_date'] == '' ) {
        $args['start_date'] = '';
/*        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $dt->setTime(0,0,0);
        $args['start_date'] = $dt->format('Y-m-d');
        $rsp['start_date'] = $dt->format($date_format);  */
        $rsp['start_date'] = '';
    } elseif( $args['start_date'] != '' ) {
        $dt = new DateTime($args['start_date'], new DateTimezone($intl_timezone));
        $rsp['start_date'] = $dt->format($date_format);
    } else {
        $rsp['start_date'] = $args['start_date'];
    }
    if( !isset($args['end_date']) ) {
        $args['end_date'] = '';
        $rsp['end_date'] = '';
    } 
    elseif( $args['end_date'] != '' ) {
        $dt = new DateTime($args['end_date'], new DateTimezone($intl_timezone));
        $dt->setTime(11,59,59);
        $args['end_date'] = $dt->format('Y-m-d');
        $rsp['end_date'] = $dt->format($date_format);
    } else {
        $rsp['end_date'] = $args['end_date'];
    }

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.categories');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    
    $rsp['categories'] = $categories;

    //
    // Load the report if specified
    //
    if( isset($args['report_id']) && $args['report_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportExec');
        $rc = ciniki_reporting_reportExec($ciniki, $args['tnid'], array(
            'report_id' => $args['report_id'],
            'start_date' => $args['start_date'],
            'end_date' => $args['end_date'],
            ));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'empty' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.27', 'msg'=>'Unable to execute report', 'err'=>$rc['err']));
        }
        $rsp['report'] = $rc['report'];
    }
    
    return $rsp;
}
?>
