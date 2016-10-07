$(document).ready(function () {
    /*-------------------------------------------
        Sparkline
    ---------------------------------------------*/
    function sparklineBar(id, values, height, barWidth, barColor, barSpacing) {
        $('.'+id).sparkline(values, {
            type: 'bar',
            height: height,
            barWidth: barWidth,
            barColor: barColor,
            barSpacing: barSpacing
        })
    }

    function sparklineLine(id, values, width, height, lineColor, fillColor, lineWidth, maxSpotColor, minSpotColor, spotColor, spotRadius, hSpotColor, hLineColor) {
        $('.'+id).sparkline(values, {
            type: 'line',
            width: width,
            height: height,
            lineColor: lineColor,
            fillColor: fillColor,
            lineWidth: lineWidth,
            maxSpotColor: maxSpotColor,
            minSpotColor: minSpotColor,
            spotColor: spotColor,
            spotRadius: spotRadius,
            highlightSpotColor: hSpotColor,
            highlightLineColor: hLineColor
        });
    }

    function sparklinePie(id, values, width, height, sliceColors) {
        $('.'+id).sparkline(values, {
            type: 'pie',
            width: width,
            height: height,
            sliceColors: sliceColors,
            offset: 0,
            borderWidth: 0
        });
    }

    // Mini Chart - Bar Chart 1
    if ($('.stats-bar')[0]) {
        sparklineBar('stats-bar', [6,4,8,6,5,6,7,8,3,5,9,5,8,4], '35px', 3, '#d6d8d9', 2);
    }

    // Mini Chart - Bar Chart 2
    if ($('.stats-bar-2')[0]) {
        sparklineBar('stats-bar-2', [4,7,6,2,5,3,8,6,6,4,8,6,5,8], '35px', 3, '#d6d8d9', 2);
    }

    // Mini Chart - Line Chart 1
    if ($('.stats-line')[0]) {
        sparklineLine('stats-line', [9,4,6,5,6,4,5,7,9,3,6,5], 68, 35, '#fff', 'rgba(0,0,0,0)', 1.25, '#d6d8d9', '#d6d8d9', '#d6d8d9', 3, '#fff', '#d6d8d9');
    }

    // Mini Chart - Line Chart 2
    if ($('.stats-line-2')[0]) {
        sparklineLine('stats-line-2', [5,6,3,9,7,5,4,6,5,6,4,9], 68, 35, '#fff', 'rgba(0,0,0,0)', 1.25, '#d6d8d9', '#d6d8d9', '#d6d8d9', 3, '#fff', '#d6d8d9');
    }

    // Mini Chart - Pie Chart 1
    if ($('.stats-pie')[0]) {
        sparklinePie('stats-pie', [20, 35, 30, 5], 45, 45, ['#fff', 'rgba(255,255,255,0.7)', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.2)']);
    }

    // Dash Widget Line Chart
    if ($('.dash-widget-visits')[0]) {
        sparklineLine('dash-widget-visits', [9,4,6,5,6,4,5,7,9,3,6,5], '100%', '70px', 'rgba(255,255,255,0.7)', 'rgba(0,0,0,0)', 1, 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.4)', 5, 'rgba(255,255,255,0.4)', '#fff');
    }


    /*-------------------------------------------
        Easy Pie Charts
    ---------------------------------------------*/
    function easyPieChart(id, trackColor, scaleColor, barColor, lineWidth, lineCap, size) {
        $('.'+id).easyPieChart({
            trackColor: trackColor,
            scaleColor: scaleColor,
            barColor: barColor,
            lineWidth: lineWidth,
            lineCap: lineCap,
            size: size
        });
    }

    // Main Pie Chart
    if ($('.main-pie')[0]) {
        easyPieChart('main-pie', 'rgba(0,0,0,0.2)', 'rgba(255,255,255,0)', 'rgba(255,255,255,0.7)', 2, 'butt', 148);
    }

    // Others
    if ($('.sub-pie-1')[0]) {
        easyPieChart('sub-pie-1', 'rgba(0,0,0,0.2)', 'rgba(255,255,255,0)', 'rgba(255,255,255,0.7)', 2, 'butt', 95);
    }

    if ($('.sub-pie-2')[0]) {
        easyPieChart('sub-pie-2', 'rgba(0,0,0,0.2)', 'rgba(255,255,255,0)', 'rgba(255,255,255,0.7)', 2, 'butt', 95);
    }
});
