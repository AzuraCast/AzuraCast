$(document).ready(function(){
    

    /*----------------------------------------------
        Make some random data for Flot Line Chart
     ----------------------------------------------*/
    var data1 = [[1,60], [2,30], [3,50], [4,100], [5,10], [6,90], [7,85]];
    var data2 = [[1,20], [2,90], [3,60], [4,40], [5,100], [6,25], [7,65]];
    var data3 = [[1,100], [2,20], [3,60], [4,90], [5,80], [6,10], [7,5]];
    
    // Create an Array push the data + Draw the bars

    var barData = [
        {
            label: 'Tokyo',
            data: data1,
            color: '#dbdddd'
        },
        {
            label: 'Seoul',
            data: data2,
            color: '#636c72'
        },
        {
            label: 'Beijing',
            data: data3,
            color: '#3e4a51'
        }
    ]
    

    /*---------------------------------
        Let's create the chart
     ---------------------------------*/
    if ($('#bar-chart')[0]) {
        $.plot($("#bar-chart"), barData, {
            series: {
                bars: {
                    show: true,
                    barWidth: 0.05,
                    order: 1,
                    fill: 1
                }
            },
            grid : {
                    borderWidth: 1,
                    borderColor: '#333c42',
                    show : true,
                    hoverable : true,
                    clickable : true
            },

            yaxis: {
                tickColor: '#333c42',
                tickDecimals: 0,
                font :{
                    lineHeight: 13,
                    style: "normal",
                    color: "#9f9f9f",
                },
                shadowSize: 0
            },

            xaxis: {
                tickColor: '#333c42',
                tickDecimals: 0,
                font :{
                    lineHeight: 13,
                    style: "normal",
                    color: "#9f9f9f"
                },
                shadowSize: 0
            },

            legend:{
                container: '.flc-bar',
                backgroundOpacity: 0.5,
                noColumns: 0,
                backgroundColor: "white",
                lineWidth: 0
            }
        });
    }
    

    /*---------------------------------
        Tooltips for Flot Charts
     ---------------------------------*/
    if ($(".flot-chart")[0]) {
        $(".flot-chart").bind("plothover", function (event, pos, item) {
            if (item) {
                var x = item.datapoint[0].toFixed(2),
                    y = item.datapoint[1].toFixed(2);
                $(".flot-tooltip").html(item.series.label + " of " + x + " = " + y).css({top: item.pageY+5, left: item.pageX+5}).show();
            }
            else {
                $(".flot-tooltip").hide();
            }
        });
        
        $("<div class='flot-tooltip' class='chart-tooltip'></div>").appendTo("body");
    }
});