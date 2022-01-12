'use strict';

import { Chart, DatasetController } from 'chart.js';
import * as helpers from 'chart.js/helpers'


// Element models are always reset when hovering in Chart.js 2.7.2 or earlier
var hoverReset = DatasetController.prototype.removeHoverStyle.length === 2;

var EXPANDO_KEY = '$colorschemes';

const pluginBase = Chart.defaults.global || Chart.defaults;
pluginBase.plugins.colorschemes = {
	scheme: 'brewer.Paired12',
	fillAlpha: 0.5,
	reverse: false,
	override: false
};

const getScheme = (scheme) => {
	var colorschemes, matches, arr, category;

	if (helpers.isArray(scheme)) {
		return scheme;
	} else if (typeof scheme === 'string') {
		colorschemes = Chart.colorschemes || {};

		// For backward compatibility
		matches = scheme.match(/^(brewer\.\w+)([1-3])-(\d+)$/);
		if (matches) {
			scheme = matches[1] + ['One', 'Two', 'Three'][matches[2] - 1] + matches[3];
		} else if (scheme === 'office.Office2007-2010-6') {
			scheme = 'office.OfficeClassic6';
		}

		arr = scheme.split('.');
		category = colorschemes[arr[0]];
		if (category) {
			return category[arr[1]];
		}
	}
}

var ColorSchemesPlugin = {
    id: 'colorschemes',
    beforeUpdate: (chart, args, options) => {
        if (options === undefined) {
			options = args;
		}

        var scheme = getScheme(options.scheme);
		var fillAlpha = options.fillAlpha;
		var reverse = options.reverse;
		var override = options.override;
		var custom = options.custom;
		var schemeClone, customResult, length, colorIndex, color;
        if (scheme) {

			if (typeof custom === 'function') {
				// clone the original scheme
				schemeClone = scheme.slice();

				// Execute own custom color function
				customResult = custom(schemeClone);

				// check if we really received a filled array; otherwise we keep and use the original scheme
				if (helpers.isArray(customResult) && customResult.length) {
					scheme = customResult;
				} else if (helpers.isArray(schemeClone) && schemeClone.length) {
					scheme = schemeClone;
				}
			}

			length = scheme.length;

			// Set scheme colors
			chart.config.data.datasets.forEach(function(dataset, datasetIndex) {
				colorIndex = datasetIndex % length;
				color = scheme[reverse ? length - colorIndex - 1 : colorIndex];

				// Object to store which color option is set
				dataset[EXPANDO_KEY] = {};

				switch (dataset.type || chart.config.type) {
				// For line, radar and scatter chart, borderColor and backgroundColor (50% transparent) are set
				case 'line':
				case 'radar':
				case 'scatter':
					if (typeof dataset.backgroundColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].backgroundColor = dataset.backgroundColor;
						dataset.backgroundColor = helpers.color(color).alpha(fillAlpha).rgbString();
					}
					if (typeof dataset.borderColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].borderColor = dataset.borderColor;
						dataset.borderColor = color;
					}
					if (typeof dataset.pointBackgroundColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].pointBackgroundColor = dataset.pointBackgroundColor;
						dataset.pointBackgroundColor = helpers.color(color).alpha(fillAlpha).rgbString();
					}
					if (typeof dataset.pointBorderColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].pointBorderColor = dataset.pointBorderColor;
						dataset.pointBorderColor = color;
					}
					break;
				// For doughnut and pie chart, backgroundColor is set to an array of colors
				case 'doughnut':
				case 'pie':
				case 'polarArea':
					if (typeof dataset.backgroundColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].backgroundColor = dataset.backgroundColor;
						dataset.backgroundColor = dataset.data.map(function(data, dataIndex) {
							colorIndex = dataIndex % length;
							return scheme[reverse ? length - colorIndex - 1 : colorIndex];
						});
					}
					break;
				// For bar chart backgroundColor (including fillAlpha) and borderColor are set
				case 'bar':
					if (typeof dataset.backgroundColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].backgroundColor = dataset.backgroundColor;
						dataset.backgroundColor = helpers.color(color).alpha(fillAlpha).rgbString();
					}
					if (typeof dataset.borderColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].borderColor = dataset.borderColor;
						dataset.borderColor = color;
					}
					break;
				// For the other chart, only backgroundColor is set
				default:
					if (typeof dataset.backgroundColor === 'undefined' || override) {
						dataset[EXPANDO_KEY].backgroundColor = dataset.backgroundColor;
						dataset.backgroundColor = color;
					}
					break;
				}
			});
		}
	},

	afterUpdate: function(chart) {
		// Unset colors
		chart.config.data.datasets.forEach(function(dataset) {
			if (dataset[EXPANDO_KEY]) {
				if (dataset[EXPANDO_KEY].hasOwnProperty('backgroundColor')) {
					dataset.backgroundColor = dataset[EXPANDO_KEY].backgroundColor;
				}
				if (dataset[EXPANDO_KEY].hasOwnProperty('borderColor')) {
					dataset.borderColor = dataset[EXPANDO_KEY].borderColor;
				}
				if (dataset[EXPANDO_KEY].hasOwnProperty('pointBackgroundColor')) {
					dataset.pointBackgroundColor = dataset[EXPANDO_KEY].pointBackgroundColor;
				}
				if (dataset[EXPANDO_KEY].hasOwnProperty('pointBorderColor')) {
					dataset.pointBorderColor = dataset[EXPANDO_KEY].pointBorderColor;
				}
				delete dataset[EXPANDO_KEY];
			}
		});
	},

	beforeEvent: function(chart, event, options) {
		if (hoverReset) {
			this.beforeUpdate(chart, options);
		}
	},

	afterEvent: function(chart) {
		if (hoverReset) {
			this.afterUpdate(chart);
		}
	}
}

Chart.register(ColorSchemesPlugin);

export default ColorSchemesPlugin;