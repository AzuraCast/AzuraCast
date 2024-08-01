/*
 * Color Scheme Plugin
 */

import {forEach} from "lodash";
import colorLib from "@kurkle/color";

const scheme: string[] = ['#4E79A7', '#A0CBE8', '#F28E2B', '#FFBE7D', '#59A14F', '#8CD17D', '#B6992D', '#F1CE63', '#499894', '#86BCB6', '#E15759', '#FF9D9A', '#79706E', '#BAB0AC', '#D37295', '#FABFD2', '#B07AA1', '#D4A6C8', '#9D7660', '#D7B5A6'];
const length: number = scheme.length;
const fillAlpha: number = 0.5;

const METADATA_KEY: string = '$colorschemes';

export default {
    id: 'colorschemes',
    beforeUpdate: (chart) => {
        // Set scheme colors
        forEach(chart.config.data.datasets, (dataset, datasetIndex: number) => {
            const color = scheme[datasetIndex % length];

            // Object to store which color option is set
            dataset[METADATA_KEY] = {};

            switch (dataset.type || chart.config.type) {
                // For line, radar and scatter chart, borderColor and backgroundColor (50% transparent) are set
                case 'line':
                case 'radar':
                case 'scatter':
                    if (typeof dataset.backgroundColor === 'undefined') {
                        dataset[METADATA_KEY].backgroundColor = dataset.backgroundColor;
                        dataset.backgroundColor = colorLib(color).alpha(fillAlpha).rgbString();
                    }
                    if (typeof dataset.borderColor === 'undefined') {
                        dataset[METADATA_KEY].borderColor = dataset.borderColor;
                        dataset.borderColor = color;
                    }
                    if (typeof dataset.pointBackgroundColor === 'undefined') {
                        dataset[METADATA_KEY].pointBackgroundColor = dataset.pointBackgroundColor;
                        dataset.pointBackgroundColor = colorLib(color).alpha(fillAlpha).rgbString();
                    }
                    if (typeof dataset.pointBorderColor === 'undefined') {
                        dataset[METADATA_KEY].pointBorderColor = dataset.pointBorderColor;
                        dataset.pointBorderColor = color;
                    }
                    break;

                // For doughnut and pie chart, backgroundColor is set to an array of colors
                case 'doughnut':
                case 'pie':
                case 'polarArea':
                    if (typeof dataset.backgroundColor === 'undefined') {
                        dataset[METADATA_KEY].backgroundColor = dataset.backgroundColor;
                        dataset.backgroundColor = dataset.data.map(function (_, dataIndex) {
                            return scheme[dataIndex % length];
                        });
                    }
                    break;
                // For bar chart backgroundColor (including fillAlpha) and borderColor are set
                case 'bar':
                    if (typeof dataset.backgroundColor === 'undefined') {
                        dataset[METADATA_KEY].backgroundColor = dataset.backgroundColor;
                        dataset.backgroundColor = colorLib(color).alpha(fillAlpha).rgbString();
                    }
                    if (typeof dataset.borderColor === 'undefined') {
                        dataset[METADATA_KEY].borderColor = dataset.borderColor;
                        dataset.borderColor = color;
                    }
                    break;
                // For the other chart, only backgroundColor is set
                default:
                    if (typeof dataset.backgroundColor === 'undefined') {
                        dataset[METADATA_KEY].backgroundColor = dataset.backgroundColor;
                        dataset.backgroundColor = color;
                    }
                    break;
            }
        });
    },

    afterUpdate: function (chart) {
        // Unset colors
        forEach(chart.config.data.datasets, (dataset) => {
            if (!dataset[METADATA_KEY]) {
                return;
            }

            if ('backgroundColor' in dataset[METADATA_KEY]) {
                dataset.backgroundColor = dataset[METADATA_KEY].backgroundColor;
            }
            if ('borderColor' in dataset[METADATA_KEY]) {
                dataset.borderColor = dataset[METADATA_KEY].borderColor;
            }
            if ('pointBackgroundColor' in dataset[METADATA_KEY]) {
                dataset.pointBackgroundColor = dataset[METADATA_KEY].pointBackgroundColor;
            }
            if ('pointBorderColor' in dataset[METADATA_KEY]) {
                dataset.pointBorderColor = dataset[METADATA_KEY].pointBorderColor;
            }

            delete dataset[METADATA_KEY];
        });
    },
};

