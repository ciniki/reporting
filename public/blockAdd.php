<?php
//
// Description
// -----------
// This method will add a new report blocks for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Report Blocks to.
//
// Returns
// -------
//
function ciniki_reporting_blockAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Report'),
        'btype'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Block Type'),
        'block_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'block_sequence'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Order'),
        'block_ref'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Block'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    $args['title'] = $args['block_title'];
    $args['sequence'] = $args['block_sequence'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Load the block_ref details
    //
    $pieces = explode('.', $args['block_ref']);
    if( count($pieces) > 2 ) {
        //
        // Check if the module has a file reporting/blocks.php
        //
        $rc = ciniki_core_loadMethod($ciniki, $pieces[0], $pieces[1], 'reporting', 'blocks');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.24', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
        }
        $fn = $rc['function_call'];
        $rc = $fn($ciniki, $args['tnid'], array());
        if( !isset($rc['blocks'][$args['block_ref']]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.25', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
        }
        $block = $rc['blocks'][$args['block_ref']];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.26', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
    }

    //
    // Update the options
    //
    $options = array();
    foreach($block['options'] as $oid => $option) {
        if( isset($ciniki['request']['args'][$oid]) ) {
            $options[$oid] = $ciniki['request']['args'][$oid];
        }
    }
    $args['options'] = serialize($options);

    //
    // Add the report blocks to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.reporting.block', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
        return $rc;
    }
    $block_id = $rc['id'];

    //
    // Add history for block options
    //
    foreach($block['options'] as $oid => $option) {
        if( isset($ciniki['request']['args'][$oid]) ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.reporting', 'ciniki_reporting_history',
                $args['tnid'], 1, 'ciniki_reporting_report_blocks', $block_id, 
                'option_' . $oid, $ciniki['request']['args'][$oid]);
        }
    }

    //
    // Update any sequences
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
    $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.reporting.block', 
        'report_id', $args['report_id'], $args['sequence'], -1);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.reporting.block', 'object_id'=>$block_id));

    return array('stat'=>'ok', 'id'=>$block_id);
}
?>
