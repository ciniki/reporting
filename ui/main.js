//
// This is the main app for the reporting module
//
function ciniki_reporting_main() {
    //
    // The panel to list the report
    //
    this.menu = new M.panel('report', 'ciniki_reporting_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.reporting.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search report',
//            'noData':'No report found',
//            },
        'reports':{'label':'Reports', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Title', 'Frequency', 'Next Date'],
            'noData':'No report',
            'addTxt':'Add Report',
            'addFn':'M.ciniki_reporting_main.report.open(\'M.ciniki_reporting_main.menu.open();\',0,null);'
            },
    }
/*    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.reporting.reportSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_reporting_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_reporting_main.menu.panelUID + '_' + s), rsp.reports);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_reporting_main.report.open(\'M.ciniki_reporting_main.menu.open();\',\'' + d.id + '\');';
    } */
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'reports' ) {
            switch(j) {
                case 0: return d.title;
                case 1: return d.frequency_text;
                case 2: return d.next_date;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'reports' ) {
            return 'M.ciniki_reporting_main.report.open(\'M.ciniki_reporting_main.menu.open();\',\'' + d.id + '\',M.ciniki_reporting_main.report.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.reporting.reportList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reporting_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Reports
    //
    this.report = new M.panel('Reports', 'ciniki_reporting_main', 'report', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.reporting.main.report');
    this.report.data = null;
    this.report.report_id = 0;
    this.report.nplist = [];
    this.report.sections = {
        'general':{'label':'Report Details', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'required':'yes', 'type':'text'},
            'frequency':{'label':'Frequency', 'required':'yes', 'default':'30', 'type':'toggle', 'toggles':{'10':'Daily', '30':'Weekly'}},
            }},
        '_next':{'label':'Next Run', 'aside':'yes', 'fields':{
            'next_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'next_time':{'label':'Time', 'required':'yes', 'type':'text', 'size':'small'},
            }},
        '_users':{'label':'Users', 'aside':'yes', 'fields':{
            'user_ids':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[]},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reporting_main.report.save();'},
            'pdf':{'label':'Open PDF', 'fn':'M.ciniki_reporting_main.report.downloadPDF();'},
            'testemail':{'label':'Send Test Email', 'fn':'M.ciniki_reporting_main.report.save("M.ciniki_reporting_main.report.emailTestPDF();");'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_reporting_main.report.report_id>0?'yes':'no';}, 'fn':'M.ciniki_reporting_main.report.remove();'},
            }},
        'blocks':{'label':'Report Sections', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Section',
            'addFn':'M.ciniki_reporting_main.report.save(\'M.ciniki_reporting_main.report.addBlock();\');',
        },
    }
    this.report.fieldValue = function(s, i, d) { return this.data[i]; }
    this.report.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reporting.reportHistory', 'args':{'tnid':M.curTenantID, 'report_id':this.report_id, 'field':i}};
    }
    this.report.addBlock = function() {
        M.ciniki_reporting_main.block.open('M.ciniki_reporting_main.report.open();',0,M.ciniki_reporting_main.report.report_id);
    }
    this.report.editBlock = function(i) {
        M.ciniki_reporting_main.block.open('M.ciniki_reporting_main.report.open();',i,M.ciniki_reporting_main.report.report_id);
    }
    this.report.cellValue = function(s, i, j, d) {
        if( s == 'blocks' ) {
            switch(j) {
                case 0: return d.title;
            }
        }
    }
    this.report.rowFn = function(s, i, d) {
        if( s == 'blocks' ) {
            return 'M.ciniki_reporting_main.report.save(\'M.ciniki_reporting_main.report.editBlock(' + d.id + ');\');';
        }
        return '';
    }
    this.report.open = function(cb, rid, list, block) {
        if( rid != null ) { this.report_id = rid; }
        if( list != null ) { this.nplist = list; }
        var args = {'tnid':M.curTenantID, 'report_id':this.report_id};
        if( block != null ) {
            args['addblock'] = block;
        }
        M.api.getJSONCb('ciniki.reporting.reportGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reporting_main.report;
            p.data = rsp.report;
            p.sections._users.fields.user_ids.list = rsp.users;
            p.refresh();
            p.show(cb);
        });
    }
    this.report.downloadPDF = function() {
        //this.save("M.api.openPDF('ciniki.reporting.reportPDF', {'tnid':" + M.curTenantID + ", 'report_id':" + this.report_id + "});");
        this.save("M.api.openPDF('ciniki.reporting.reportPDF', {'tnid':" + M.curTenantID + ", 'report_id':" + this.report_id + "});");
    }
    this.report.emailTestPDF = function() {
        M.api.getJSONCb('ciniki.reporting.reportPDF', {'tnid':M.curTenantID, 'report_id':this.report_id, 'email':'test'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert('Email sent');
        });
    }
    this.report.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reporting_main.report.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.report_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.reporting.reportUpdate', {'tnid':M.curTenantID, 'report_id':this.report_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.reporting.reportAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reporting_main.report.report_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.report.remove = function() {
        M.confirm('Are you sure you want to remove report?',null,function() {
            M.api.getJSONCb('ciniki.reporting.reportDelete', {'tnid':M.curTenantID, 'report_id':M.ciniki_reporting_main.report.report_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reporting_main.report.close();
            });
        });
    }
    this.report.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.report_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reporting_main.report.save(\'M.ciniki_reporting_main.report.open(null,' + this.nplist[this.nplist.indexOf('' + this.report_id) + 1] + ');\');';
        }
        return null;
    }
    this.report.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.report_id) > 0 ) {
            return 'M.ciniki_reporting_main.report.save(\'M.ciniki_reporting_main.report_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.report_id) - 1] + ');\');';
        }
        return null;
    }
    this.report.addButton('save', 'Save', 'M.ciniki_reporting_main.report.save();');
    this.report.addClose('Cancel');
    this.report.addButton('next', 'Next');
    this.report.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Report Blocks
    //
    this.block = new M.panel('Report Blocks', 'ciniki_reporting_main', 'block', 'mc', 'medium', 'sectioned', 'ciniki.reporting.main.block');
    this.block.data = null;
    this.block.block_id = 0;
    this.block.report_id = 0;
    this.block.block_ref = 0;
    this.block.nplist = [];
    this.block.sections = {
        'general':{'label':'', 'fields':{
            'block_ref':{'label':'Section Content', 'type':'select', 'options':[], 
                'onchange':'M.ciniki_reporting_main.block.setBlockOptions',
                },
            'block_title':{'label':'Title', 'type':'text'},
            'block_sequence':{'label':'Order', 'required':'yes', 'type':'text'},
            }},
        '_options':{'label':'Options', 'visible':'hidden', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reporting_main.block.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_reporting_main.block.block_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_reporting_main.block.remove();'},
            }},
        };
    this.block.fieldValue = function(s, i, d) { 
        if( s == '_options' ) {
            return this.data.options[i];
        }
        return this.data[i]; 
    }
    this.block.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reporting.blockHistory', 'args':{'tnid':M.curTenantID, 'block_id':this.block_id, 'field':i}};
    }
    this.block.setBlockOptions = function() {
        this.sections._options.visible = 'hidden';
        this.sections._options.fields = {};
        var block_ref = this.formValue('block_ref');
        if( this.data.availableblocks[block_ref] != null 
            && this.data.availableblocks[block_ref].options != null 
            && JSON.stringify(this.data.availableblocks[block_ref].options)!=JSON.stringify({})
            && JSON.stringify(this.data.availableblocks[block_ref].options)!=JSON.stringify([])
            ) {
            this.sections._options.fields = this.data.availableblocks[block_ref].options;
            this.sections._options.visible = 'yes';
        }
        this.refreshSection("_options");
        this.showHideSection("_options");
    }
    this.block.open = function(cb, bid, rid, list) {
        if( bid != null ) { this.block_id = bid; }
        if( rid != null ) { this.report_id = rid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.reporting.blockGet', {'tnid':M.curTenantID, 'block_id':this.block_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reporting_main.block;
            p.data = rsp.block;
            p.data.availableblocks = rsp.availableblocks;
            p.sections.general.fields.block_ref.options = [];
            for(var i in rsp.availableblocks) {
                p.sections.general.fields.block_ref.options[i] = rsp.availableblocks[i].module + ' - ' + rsp.availableblocks[i].name;
            }
            p.refresh();
            p.show(cb);
            p.setBlockOptions();
        });
    }
    this.block.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reporting_main.block.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.block_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.reporting.blockUpdate', {'tnid':M.curTenantID, 'block_id':this.block_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.reporting.blockAdd', {'tnid':M.curTenantID, 'report_id':this.report_id, 'btype':10}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reporting_main.block.block_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.block.remove = function() {
        M.confirm('Are you sure you want to remove block?',null,function() {
            M.api.getJSONCb('ciniki.reporting.blockDelete', {'tnid':M.curTenantID, 'block_id':M.ciniki_reporting_main.block.block_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reporting_main.block.close();
            });
        });
    }
    this.block.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.block_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reporting_main.block.save(\'M.ciniki_reporting_main.block.open(null,' + this.nplist[this.nplist.indexOf('' + this.block_id) + 1] + ');\');';
        }
        return null;
    }
    this.block.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.block_id) > 0 ) {
            return 'M.ciniki_reporting_main.block.save(\'M.ciniki_reporting_main.block.open(null,' + this.nplist[this.nplist.indexOf('' + this.block_id) - 1] + ');\');';
        }
        return null;
    }
    this.block.addButton('save', 'Save', 'M.ciniki_reporting_main.block.save();');
    this.block.addClose('Cancel');
    this.block.addButton('next', 'Next');
    this.block.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_reporting_main', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
