/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'EsChart',
    'mage/translate'
], function (Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: "Smile_ElasticsuiteCatalogOptimizer/form/element/scale-function-chart",
            chartId: null,
            chartRangesId: null,
            defaultBorderWidth: 1,
            selectedBorderWidth: 4,
            chartRangesInit: false,
            currentChartRange: {
                min: 0,
                max: 10,
                interval: 1
            },
            chartRangeThreshold: 10,
        },

        initialize: function () {
            this._super();
            this.chartRangesId = this.chartId + '_chart_ranges';
            this.currentOptionValue = this.initOptionValue;
            this.initPlugins();
        },

        createChart: function (optionValue = null) {
            if (!this.chartRangesInit) {
                this.initRanges();
            }
            optionValue = typeof optionValue === 'object' ? null : optionValue;
            var data = {
                labels: _.range(
                    this.currentChartRange.min,
                    this.currentChartRange.max + this.currentChartRange.interval,
                    this.currentChartRange.interval
                ),
                datasets: this.getDataSets(optionValue),
            };

            var chartElement = $('#' + this.chartId);
            var modal = chartElement.closest('.modal-content');
            var scrollTop = null;
            if (this.chart !== undefined) {
                /**
                 * When the chart is in a modal, once it is destroyed there is a scroll effect because the HTML chart element is empty,
                 * to avoid that we keep the scroll top value before the destruction and we reapply it on the modal after the chart creation.
                 */
                scrollTop = modal.scrollTop();
                this.chart.destroy();
            }

            this.chart = new Chart(chartElement, {
                type: 'line',
                plugins : this.plugins,
                data: data,
                options: {
                    tooltips: {
                        enabled: false
                    },
                    scales: {
                        y: {
                            display: true,
                            type: 'linear',
                        },
                    },
                    animation: {
                        duration: 0
                    },
                }
            });

            if (scrollTop) {
                modal.scrollTop(scrollTop);
            }
        },

        initRanges: function () {
            $('#' + this.chartRangesId).on('click', '[data-role="range"]', function (e) {
                this.currentChartRange.min      = $(e.currentTarget).data('range-min');
                this.currentChartRange.max      = $(e.currentTarget).data('range-max');
                this.currentChartRange.interval = $(e.currentTarget).data('range-interval');
                this.createChart();
            }.bind(this));
            this.chartRangesInit = true;
        },

        initPlugins: function () {
            this.plugins = [{
                beforeInit: function (chart) {
                    var data = chart.config.data;
                    for (var i = 0; i < data.datasets.length; i++) {
                        for (var j = 0; j < data.labels.length; j++) {
                            var fct = data.datasets[i].function,
                                x = data.labels[j],
                                y = fct(x);
                            data.datasets[i].data.push(y);
                        }
                    }
                }
            }]
        },

        getDataSets: function (optionValue) {
            optionValue =  this.currentOptionValue;
            var datasets = [];
            var DataSetsConfig =  {
                'ln1p': {
                    label: $.mage.__('Low'),
                    function: function (x) {
                        return Math.log(x + 1);
                    },
                    borderColor: "rgba(43, 172, 118, 1)",
                    data: [],
                    fill: false,
                    borderWidth: optionValue === 'ln1p' ? this.selectedBorderWidth : this.defaultBorderWidth,
                },
                'log1p': {
                    label: $.mage.__('Low'),
                    function: function (x) {
                        return Math.log10(x + 1);
                    },
                    borderColor: "rgba(43, 172, 118, 1)",
                    data: [],
                    fill: false,
                    borderWidth: optionValue === 'log1p' ? this.selectedBorderWidth : this.defaultBorderWidth,
                },
                'sqrt': {
                    label:  $.mage.__('Medium'),
                    function: function (x) {
                        return Math.sqrt(x);
                    },
                    borderColor: "rgba(153, 102, 255, 1)",
                    data: [],
                    fill: false,
                    borderWidth: optionValue === 'sqrt' ? this.selectedBorderWidth : this.defaultBorderWidth,
                },
                'none': {
                    label:  $.mage.__('High'),
                    function: function (x) {
                        return x;
                    },
                    borderColor: "rgba(63, 123, 217, 1)",
                    data: [],
                    fill: false,
                    borderWidth: optionValue === 'none' ? this.selectedBorderWidth : this.defaultBorderWidth,
                    hidden: this.currentChartRange.max > this.chartRangeThreshold && optionValue !== 'none',
                }
            };

            Object.values(this.functions).forEach(function (key) {
                if (DataSetsConfig.hasOwnProperty(key)) {
                    datasets.push(DataSetsConfig[key]);
                }
            });

            return datasets;
        },

        onScaleFunctionChange: function (value) {
            if (this.chart !== undefined) {
                this.currentOptionValue = value;
                this.createChart();
            }
        },
    });
});
