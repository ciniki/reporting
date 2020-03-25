<?php
//
// Description
// -----------
// This method will delete an reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:                The ID of the tenant the report is attached to.
// report_id:           The ID of the reports to be removed.
//
// Returns
// -------
//
function ciniki_reporting_reportDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Reports'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.reportDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the reports
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_reporting_reports "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reporting', 'report');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['report']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.5', 'msg'=>'Reports does not exist.'));
    }
    $report = $rc['report'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.reporting.report', $args['report_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.6', 'msg'=>'Unable to check if the reports is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.7', 'msg'=>'The reports is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reporting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the users
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_reporting_report_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reporting', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $users = $rc['rows'];
        foreach($users as $user) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.reporting.user', $user['id'], $user['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
                return $rc;
            }
        }
    }

    //
    // Remove the blocks
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_reporting_report_blocks "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reporting', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $blocks = $rc['rows'];
        foreach($blocks as $block) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.reporting.block', $block['id'], $block['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
                return $rc;
            }
        }
    }

    //
    // Remove the report
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.reporting.report',
        $args['report_id'], $report['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
        return $rc;
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

    return array('stat'=>'ok');
}
?>
