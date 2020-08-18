<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reporting_blockUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'block_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Report Blocks'),
        'report_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Report'),
        'btype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Block Type'),
        'block_title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'block_sequence'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order'),
        'block_ref'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Block'),
        'options'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    if( isset($args['block_title']) ) {
        $args['title'] = $args['block_title'];
    }
    if( isset($args['block_sequence']) ) {
        $args['sequence'] = $args['block_sequence'];
    }

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockUpdate');
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
    // Get the current block
    //
    $strsql = "SELECT ciniki_reporting_report_blocks.id, "
        . "ciniki_reporting_report_blocks.report_id, "
        . "ciniki_reporting_report_blocks.btype, "
        . "ciniki_reporting_report_blocks.title, "
        . "ciniki_reporting_report_blocks.sequence, "
        . "ciniki_reporting_report_blocks.block_ref, "
        . "ciniki_reporting_report_blocks.options "
        . "FROM ciniki_reporting_report_blocks "
        . "WHERE ciniki_reporting_report_blocks.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_reporting_report_blocks.id = '" . ciniki_core_dbQuote($ciniki, $args['block_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'block', 'fname'=>'id', 
            'fields'=>array('report_id', 'btype', 
                'block_title'=>'title', 'sequence', 'block_ref', 'options'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.28', 'msg'=>'Report section not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['block'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.29', 'msg'=>'Unable to find report section'));
    }
    $existing_block = $rc['block'][0];

    //
    // Load the block_ref details
    //
    $pieces = explode('.', $existing_block['block_ref']);
    if( count($pieces) > 2 ) {
        //
        // Check if the module has a file reporting/blocks.php
        //
        $rc = ciniki_core_loadMethod($ciniki, $pieces[0], $pieces[1], 'reporting', 'blocks');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.30', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
        }
        $fn = $rc['function_call'];
        $rc = $fn($ciniki, $args['tnid'], array());
        if( !isset($rc['blocks'][$existing_block['block_ref']]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.31', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
        }
        $block = $rc['blocks'][$existing_block['block_ref']];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.32', 'msg'=>'Unknown block reference', 'err'=>$rc['err']));
    }

    //
    // Update the options
    //
    $options = unserialize($existing_block['options']);
    foreach($block['options'] as $oid => $option) {
        if( isset($ciniki['request']['args'][$oid]) ) {
            if( !isset($options[$oid]) ) {
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.reporting', 'ciniki_reporting_history',
                    $args['tnid'], 1, 'ciniki_reporting_report_blocks', $args['block_id'], 
                    'option_' . $oid, $ciniki['request']['args'][$oid]);
            } elseif( isset($options[$oid]) && $options[$oid] != $ciniki['request']['args'][$oid] ) {
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.reporting', 'ciniki_reporting_history',
                    $args['tnid'], 2, 'ciniki_reporting_report_blocks', $args['block_id'], 
                    'option_' . $oid, $ciniki['request']['args'][$oid]);
            } 
            $options[$oid] = $ciniki['request']['args'][$oid];
        }
    }
    $args['options'] = serialize($options);
    if( $args['options'] == $existing_block['options'] ) {
        unset($args['options']);
    }

    //
    // Update the Report Blocks in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reporting.block', $args['block_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
        return $rc;
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.reporting.block', 
            'report_id', $existing_block['report_id'], $args['sequence'], $existing_block['sequence']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
            return $rc;
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.reporting.block', 'object_id'=>$args['block_id']));

    return array('stat'=>'ok');
}
?>
