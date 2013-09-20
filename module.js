/**
 * Multi Grader report namespace
 */
M.gradereport_multigrader = {
    /**
     * @param {Array} reports An array of instantiated report objects
     */
    reports : [],
    /**
     * @namespace M.gradereport_multigrader
     * @param {Object} reports A collection of classes used by the multi grader report module
     */
    classes : {},
    /**
     * @param {Object} tooltip Null or a tooltip object
     */
    tooltip : null,
    /**
     * Instantiates a new multi grader report
     *
     * @function
     * @param {YUI} Y
     * @param {String} id The id attribute of the reports table
     * @param {Object} cfg A configuration object
     * @param {Array} An array of items in the report
     * @param {Array} An array of users on the report
     * @param {Array} An array of feedback objects
     * @param {Array} An array of student grades
     */
    init_report : function(Y, id, cfg, items, users, feedback, grades) {
        this.tooltip = this.tooltip || {
            overlay : null, // Y.Overlay instance
            /**
             * Attaches the tooltip event to the provided cell
             *
             * @function M.gradereport_multigrader.tooltip.attach
             * @this M.gradereport_multigrader
             * @param {Y.Node} td The cell to attach the tooltip event to
             */
            attach : function(td, report) {
                td.on('mouseenter', this.show, this, report);
            },
            /**
             * Shows the tooltip: Callback from @see M.gradereport_multigrader.tooltip#attach
             *
             * @function M.gradereport_multigrader.tooltip.show
             * @this {M.gradereport_multigrader.tooltip}
             * @param {Event} e
             * @param {M.gradereport_multigrader.classes.report} report
             */
            show : function(e, report) {
                e.halt();

                var properties = report.get_cell_info(e.target);
                if (!properties) {
                    return;
                }

                var content = '<div class="multigraderreportoverlay">';
                content += '<div class="fullname">'+properties.username+'</div><div class="itemname">'+properties.itemname+'</div>';
                if (properties.feedback) {
                    content += '<div class="feedback">'+properties.feedback+'</div>';
                }
                content += '</div>';

                properties.cell.on('mouseleave', this.hide, this, properties.cell);
                properties.cell.addClass('tooltipactive');

                this.overlay = this.overlay || (function(){
                    var overlay = new Y.Overlay({
                        bodyContent : 'Loading',
                        visible : false,
                        zIndex : 2
                    });
                    overlay.render(report.table.ancestor('div'));
                    return overlay;
                })();
                this.overlay.set('xy', [e.target.getX()+(e.target.get('offsetWidth')/2),e.target.getY()+e.target.get('offsetHeight')-5]);
                this.overlay.set("bodyContent", content);
                this.overlay.show();
                this.overlay.get('boundingBox').setStyle('visibility', 'visible');
            },
            /**
             * Hides the tooltip
             *
             * @function M.gradereport_multigrader.tooltip.hide
             * @this {M.gradereport_multigrader.tooltip}
             * @param {Event} e
             * @param {Y.Node} cell
             */
            hide : function(e, cell) {
                cell.removeClass('tooltipactive');
                this.overlay.hide();
                this.overlay.get('boundingBox').setStyle('visibility', 'hidden');
            }
        };
        // Create the actual report
        this.reports[id] = new this.classes.report(Y, id, cfg, items, users, feedback, grades);
    }
};

/**
 * Initialises the JavaScript for the gradebook multi grader report
 *
 * The functions fall into 3 groups:
 * M.gradereport_multigrader.classes.ajax Used when editing is off and fields are dynamically added and removed
 * M.gradereport_multigrader.classes.existingfield Used when editing is on meaning all fields are already displayed
 * M.gradereport_multigrader.classes.report Common to both of the above
 *
 * @class report
 * @constructor
 * @this {M.gradereport_multigrader}
 * @param {YUI} Y
 * @param {int} id The id of the table to attach the report to
 * @param {Object} cfg Configuration variables
 * @param {Array} items An array containing grade items
 * @param {Array} users An array containing user information
 * @param {Array} feedback An array containing feedback information
 */
M.gradereport_multigrader.classes.report = function(Y, id, cfg, items, users, feedback, grades) {
    this.Y = Y;
    this.isediting = (cfg.isediting);
    this.ajaxenabled = (cfg.ajaxenabled);
    this.items = items;
    this.users = users;
    this.feedback = feedback;
    this.table = Y.one('#user-grades');
    this.grades = grades;

    // Alias this so that we can use the correct scope in the coming
    // node iteration
    this.table.all('tr').each(function(tr){
        // Check it is a user row
        if (tr.getAttribute('id').match(/^(fixed_)?user_(\d+)$/)) {
            // Highlight rows
            tr.all('th.cell').on('click', this.table_highlight_row, this, tr);
            // Display tooltips
            tr.all('td.cell').each(function(cell){
                M.gradereport_multigrader.tooltip.attach(cell, this);
            }, this);
        }
    }, this);

    // If the fixed table exists then map those rows to highlight the
    // grades table rows
    var fixed = this.Y.one(id);
    if (fixed) {
        fixed.all('tr').each(function(tr) {
            if (tr.getAttribute('id').match(/^fixed_user_(\d+)$/)) {
                tr.all('th.cell').on('click', this.table_highlight_row, this, this.Y.one(tr.getAttribute('id').replace(/^fixed_/, '#')));
            }
        }, this);
    }

    // Highlight columns
    this.table.all('.highlightable').each(function(cell){
        cell.on('click', this.table_highlight_column, this, cell);
        cell.removeClass('highlightable');
    }, this);

    // If ajax is enabled then initialise the ajax component
    if (this.ajaxenabled) {
        this.ajax = new M.gradereport_multigrader.classes.ajax(this, cfg);
    }
};
/**
 * Extend the report class with the following methods and properties
 */
M.gradereport_multigrader.classes.report.prototype.table = null;           // YUI Node for the reports main table
M.gradereport_multigrader.classes.report.prototype.items = [];             // Array containing grade items
M.gradereport_multigrader.classes.report.prototype.users = [];             // Array containing user information
M.gradereport_multigrader.classes.report.prototype.feedback = [];          // Array containing feedback items
M.gradereport_multigrader.classes.report.prototype.ajaxenabled = false;    // True is AJAX is enabled for the report
M.gradereport_multigrader.classes.report.prototype.ajax = null;            // An instance of the ajax class or null
/**
 * Highlights a row in the report
 *
 * @function
 * @param {Event} e
 * @param {Y.Node} tr The table row to highlight
 */
M.gradereport_multigrader.classes.report.prototype.table_highlight_row = function (e, tr) {
    tr.all('.cell').toggleClass('hmarked');
};
/**
 * Highlights a cell in the table
 *
 * @function
 * @param {Event} e
 * @param {Y.Node} cell
 */
M.gradereport_multigrader.classes.report.prototype.table_highlight_column = function(e, cell) {
    var column = 0;
    while (cell = cell.previous('.cell')) {
        column += parseFloat(cell.getAttribute('colspan')) || 1;
    }
    this.table.all('.c'+column).toggleClass('vmarked');
};
/**
 * Builds an object containing information at the relevant cell given either
 * the cell to get information for or an array containing userid and itemid
 *
 * @function
 * @this {M.gradereport_multigrader}
 * @param {Y.Node|Array} arg Either a YUI Node instance or an array containing
 *                           the userid and itemid to reference
 * @return {Object}
 */
M.gradereport_multigrader.classes.report.prototype.get_cell_info = function(arg) {

    var userid= null;
    var itemid = null;
    var feedback = ''; // Don't default feedback to null or string comparisons become error prone
    var cell = null;
    var i = null;

    if (arg instanceof this.Y.Node) {
        if (arg.get('nodeName').toUpperCase() !== 'TD') {
            arg = arg.ancestor('td.cell');
        }
        var regexp = /^u(\d+)i(\d+)$/;
        var parts = regexp.exec(arg.getAttribute('id'));
        userid = parts[1];
        itemid = parts[2];
        cell = arg;
    } else {
        userid = arg[0];
        itemid = arg[1];
        cell = this.Y.one('#u'+userid+'i'+itemid);
    }

    if (!cell) {
        return null;
    }

    for (i in this.feedback) {
        if (this.feedback[i] && this.feedback[i].user == userid && this.feedback[i].item == itemid) {
            feedback = this.feedback[i].content;
            break;
        }
    }

    return {
        userid : userid,
        username : this.users[userid],
        itemid : itemid,
        itemname : this.items[itemid].name,
        itemtype : this.items[itemid].type,
        itemscale : this.items[itemid].scale,
        itemdp : this.items[itemid].decimals,
        feedback : feedback,
        cell : cell
    };
};

/**
 * Extend the ajax class with the following methods and properties
 */
M.gradereport_multigrader.classes.ajax.prototype.report = null;                  // A reference to the report class this object will use
M.gradereport_multigrader.classes.ajax.prototype.courseid = null;                // The id for the course being viewed
M.gradereport_multigrader.classes.ajax.prototype.feedbacktrunclength = null;     // The length to truncate feedback to
M.gradereport_multigrader.classes.ajax.prototype.studentsperpage = null;         // The number of students shown per page
M.gradereport_multigrader.classes.ajax.prototype.showquickfeedback = null;       // True if feedback editing should be shown
M.gradereport_multigrader.classes.ajax.prototype.current = null;                 // The field being currently editing
M.gradereport_multigrader.classes.ajax.prototype.pendingsubmissions = [];        // Array containing pending IO transactions
M.gradereport_multigrader.classes.ajax.prototype.scales = [];                    // An array of scales used in this report

/**
 * Attach the required properties and methods to the existing field class
 * via prototyping
 */
M.gradereport_multigrader.classes.existingfield.prototype.userid = null;
M.gradereport_multigrader.classes.existingfield.prototype.itemid = null;
M.gradereport_multigrader.classes.existingfield.prototype.editfeedback = false;
M.gradereport_multigrader.classes.existingfield.prototype.grade = null;
M.gradereport_multigrader.classes.existingfield.prototype.oldgrade = null;
M.gradereport_multigrader.classes.existingfield.prototype.keyevents = [];


/**
 * Replaces the cell contents with the controls to enable editing
 *
 * @function
 * @this {M.gradereport_multigrader.classes.textfield}
 * @return {M.gradereport_multigrader.classes.textfield}
 */
M.gradereport_multigrader.classes.textfield.prototype.replace = function() {
    this.set_grade(this.get_grade());
    if (this.editfeedback) {
        this.set_feedback(this.get_feedback());
    }
    this.node.replaceChild(this.inputdiv, this.gradespan);
    this.grade.focus();
    this.editable = true;
    return this;
};

/**
 * Override + extend the scalefield class with the following properties
 * and methods
 */
/**
 * @property {Array} scale
 */
M.gradereport_multigrader.classes.scalefield.prototype.scale = [];

