const $ = require('jquery');

const getColors = function (chartType)
{
    const isDark = $('body').data('bs-theme') === 'dark';
    if (chartType === 'submissions') {
        if (isDark) {
            return {
                background: '#182433',
                fill: ['#1d3b21', '#3b1d1d'],
                colors: ['#56B344', '#D63939'],
                shade: 'dark',
                tooltipTheme: 'dark',
                axisColor: '#dce1e7',
                annotationsLineColor: '#BBBBBB',
                annotationsLabelColor: '#BBBBBB',
                grid: '#5e6671',
            };
        } else {
            return {
                background: '#FFFFFF',
                fill: ['#EAF7EC', '#FBEBEB'],
                colors: ['#56B344', '#D63939'],
                shade: 'light',
                tooltipTheme: 'light',
                axisColor: '#000000',
                annotationsLineColor: '#888888',
                annotationsLabelColor: '#888888',
                grid: '#AAAAAA',
            };
        }
    } else if (chartType === 'requests') {
        if (isDark) {
            return {
                background: '#182433',
                fill: ['#5b4924', '#512944'],
                colors: ['#f59f00', '#d6336c'],
                shade: 'dark',
                tooltipTheme: 'dark',
                axisColor: '#dce1e7',
                annotationsLineColor: '#BBBBBB',
                annotationsLabelColor: '#BBBBBB',
                grid: '#5e6671',
            };
        } else {
            return {
                background: '#FFFFFF',
                fill: ['#fef5e5', '#faeaf0'],
                colors: ['#f59f00', '#d6336c'],
                shade: 'light',
                tooltipTheme: 'light',
                axisColor: '#000000',
                annotationsLineColor: '#888888',
                annotationsLabelColor: '#888888',
                grid: '#5e6671',
            };
        }
    }

    return {};
}

const renderChart = function (chartElement, nonce, series, chartType, dateFormat, annotations)
{
    let colors = getColors(chartType);
    let chartOptions = {
        series: series,
        chart: {
            type: "area",
            background: colors.background,
            fontFamily: 'inherit',
            height: 250,
            stacked: true,
            parentHeightOffset: 5,
            nonce: nonce,
            animations: {
                enabled: false
            },
            zoom: {
                enabled: false
            },
            toolbar: {
                show: false
            },
            sparkline: {
                enabled: false
            }
        },
        dataLabels: {
            enabled: false,
        },
        fill: {
            colors: colors.fill,
            type: 'gradient',
            gradient: {
                inverseColors: false,
                shade: colors.shade,
                type: "vertical",
                opacityFrom: 0.9,
                opacityTo: 0.6,
                stops: [0, 100, 100, 100]
            }
        },
        tooltip: {
            theme: colors.tooltipTheme,
            x: {
                show: true,
                format: dateFormat,
                formatter: undefined,
            }
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        colors: colors.colors,
        grid: {
            show: true,
            strokeDashArray: 4,
            borderColor: colors.grid,
        },
        legend: {
            show: false
        },
        point: {
            show: false
        },
        xaxis: {
            labels: {
                padding: 0,
                datetimeFormatter: {
                    day: dateFormat
                },
                style: {
                    colors: colors.axisColor
                }
            },
            tooltip: {
                enabled: false
            },
            axisBorder: {
                show: false,
            },
            type: 'datetime',
        },
        yaxis: {
            show: true,
            labels: {
                show: true,
                offsetX: 0,
                offsetY: 3,
                style: {
                    colors: colors.axisColor
                }
            },
            tooltip: {
                enabled: false
            }
        },
        annotations: annotations,
    };

    let chart = new ApexCharts(chartElement, chartOptions);
    chart.render();

    return chart;
}

window.renderChart = renderChart;