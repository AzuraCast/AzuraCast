/**
*
*  Scrollable HTML table
*  http://www.webtoolkit.info/
*
**/

function ScrollableTable (tableEl, tableHeight, tableWidth) {

	this.initIEengine = function () {

		this.containerEl.style.overflowY = 'auto';
		if (this.tableEl.parentElement.clientHeight - this.tableEl.offsetHeight < 0) {
			this.tableEl.style.width = this.newWidth - this.scrollWidth +'px';
		} else {
			this.containerEl.style.overflowY = 'hidden';
			this.tableEl.style.width = this.newWidth +'px';
		}

		if (this.thead) {
			var trs = this.thead.getElementsByTagName('tr');
			for (x=0; x<trs.length; x++) {
				trs[x].style.position ='relative';
				trs[x].style.setExpression("top",  "this.parentElement.parentElement.parentElement.scrollTop + 'px'");
			}
		}

		if (this.tfoot) {
			var trs = this.tfoot.getElementsByTagName('tr');
			for (x=0; x<trs.length; x++) {
				trs[x].style.position ='relative';
				trs[x].style.setExpression("bottom",  "(this.parentElement.parentElement.offsetHeight - this.parentElement.parentElement.parentElement.clientHeight - this.parentElement.parentElement.parentElement.scrollTop) + 'px'");
			}
		}

		eval("window.attachEvent('onresize', function () { document.getElementById('" + this.tableEl.id + "').style.visibility = 'hidden'; document.getElementById('" + this.tableEl.id + "').style.visibility = 'visible'; } )");
	};


	this.initFFengine = function () {
		this.containerEl.style.overflow = 'hidden';
		this.tableEl.style.width = this.newWidth + 'px';

		var headHeight = (this.thead) ? this.thead.clientHeight : 0;
		var footHeight = (this.tfoot) ? this.tfoot.clientHeight : 0;
		var bodyHeight = this.tbody.clientHeight;
		
		var trs = this.tbody.getElementsByTagName('tr');
		if (bodyHeight >= (this.newHeight - (headHeight + footHeight))) {
			this.tbody.style.overflow = '-moz-scrollbars-vertical';
			for (x=0; x<trs.length; x++) {
				var tds = trs[x].getElementsByTagName('td');
				tds[(tds.length)-1].style.paddingRight += this.scrollWidth + 'px';
			}
		} else {
			this.tbody.style.overflow = '-moz-scrollbars-none';
		}
		
		var cellSpacing = (this.tableEl.offsetHeight - (this.tbody.clientHeight + headHeight + footHeight)) / 4;
		this.tbody.style.height = (this.newHeight - (headHeight + cellSpacing * 2) - (footHeight + cellSpacing * 2)) + 'px';

	};

	this.tableEl = tableEl;
	this.scrollWidth = 17;

	this.originalHeight = this.tableEl.clientHeight;
	this.originalWidth = this.tableEl.clientWidth;

	this.newHeight = parseInt(tableHeight);
	this.newWidth = tableWidth ? parseInt(tableWidth) : this.originalWidth;

	this.tableEl.style.height = 'auto';
	this.tableEl.removeAttribute('height');
	
	this.containerEl = this.tableEl.parentNode.insertBefore(document.createElement('div'), this.tableEl);
	this.containerEl.appendChild(this.tableEl);
	this.containerEl.className = 'nce-datatable-wrapper';	
	this.containerEl.style.height = this.newHeight + 'px';
	this.containerEl.style.width = this.newWidth + 'px';

	var thead = this.tableEl.getElementsByTagName('thead');
	this.thead = (thead[0]) ? thead[0] : null;

	var tfoot = this.tableEl.getElementsByTagName('tfoot');
	this.tfoot = (tfoot[0]) ? tfoot[0] : null;

	var tbody = this.tableEl.getElementsByTagName('tbody');
	this.tbody = (tbody[0]) ? tbody[0] : null;

	if (!this.tbody) return;

	if (document.all && document.getElementById && !window.opera) this.initIEengine();
	if (!document.all && document.getElementById && !window.opera) this.initFFengine();
}

jQuery.fn.Scrollable = function(tableHeight, tableWidth) {
    this.each(function(){
        if (jQuery.browser.msie || jQuery.browser.mozilla) {
            var table = new ScrollableTable(this, tableHeight, tableWidth);
        }
    });
};
