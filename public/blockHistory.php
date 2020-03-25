<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an report blocks.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// block_id:          The ID of the report blocks to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_reporting_blockHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'block_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Report Blocks'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'block_title' ) {
        $args['field'] = 'title';
    } elseif( $args['field'] == 'block_sequence' ) {
        $args['field'] = 'sequence';
    } elseif( $args['field'] == 'block_ref' ) {
        $args['field'] = 'block_ref';
    } else {
        $args['field'] = 'option_' . $args['field'];
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.reporting', 'ciniki_reporting_history', $args['tnid'], 'ciniki_reporting_report_blocks', $args['block_id'], $args['field']);
}
?>
