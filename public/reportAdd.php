<?php
//
// Description
// -----------
// This method will add a new reports for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Reports to.
//
// Returns
// -------
//
function ciniki_reporting_reportAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'user_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Users'),
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'frequency'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Frequency'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'next_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Next Date'),
        'next_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'Next Time'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];


    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.reportAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $ts = strtotime($args['next_date'] . ' ' . $args['next_time']);
    if( $ts === FALSE || $ts < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.4', 'msg'=>'Invalid date or time format'));
    }
    $dt = new DateTime("@" . $ts, new DateTimezone($intl_timezone));
    $args['next_date'] = $dt->format('Y-m-d H:i:s');

    //
    // Get the list of tenant users
    //
    $strsql = "SELECT user_id "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 10 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.reporting', 'users', 'user_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $users = array();
    if( isset($rc['users']) ) {
        $users = $rc['users'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reporting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the reports to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.reporting.report', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
        return $rc;
    }
    $report_id = $rc['id'];

    //
    // Add the users
    //
    if( isset($args['user_ids']) && is_array($args['user_ids']) ) {
        foreach($args['user_ids'] as $id) {
            if( in_array($id, $users) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.reporting.reportuser', array(
                    'report_id'=>$report_id,
                    'user_id'=>$id,
                    ), 0x04);
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.reporting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'reporting');

    return array('stat'=>'ok', 'id'=>$report_id);
}
?>
