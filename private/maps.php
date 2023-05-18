<?php
//
// Description
// -----------
// The module maps
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reporting_maps($ciniki) {
    $maps = array();
    $maps['report'] = array(
        'frequency'=>array(
            '10'=>'Daily',
            '30'=>'Weekly',
            '50'=>'Monthly',
            '90'=>'Yearly',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
