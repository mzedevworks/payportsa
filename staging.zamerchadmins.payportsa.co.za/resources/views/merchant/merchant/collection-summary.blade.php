<!-- <h5 class="card-title">Business Overview</h5> -->
    <div class="float-right">
         <ul class="list-inline d-none d-sm-block">
             <li>
            	<span class="status bg-primary"></span>
                <span class="text-semibold">Settled payment value</span>
             </li>
             <li>
               <span class="status bg-success"></span>
               <span class="text-semibold">Number of payments</span>
             </li>
        </ul>
    </div>
<div class="card-body">
  <div id="morris-bar-example" style="height: 372px"></div>
</div>
<!-- <div id="morris-donut-example" style="height: 240px"></div> -->
<script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
<script src="{{ asset('plugins/raphael/raphael-min.js') }}"></script>
<script type="text/javascript">
var parameter = '{{ json_encode($graphParamArray)}}';
var parameterList = JSON.parse(parameter.replace(/&quot;/g,'"'));
!function ($) {
    "use strict";

    var Dashboard = function () {
    };
        //creates Bar chart
        Dashboard.prototype.createBarChart = function (element, data, xkey, ykeys, labels, lineColors) {
            Morris.Bar({
                element: element,
                data: data,
                xkey: xkey,
                ykeys: ykeys,
                labels: labels,
                gridLineColor: '#eee',
                barSizeRatio: 0.4,
                resize: true,
                hideHover: 'auto',
                barColors: lineColors
            });
        },

        //creates Donut chart
        Dashboard.prototype.createDonutChart = function (element, data, colors) {
            Morris.Donut({
                element: element,
                data: data,
                resize: true,
                colors: colors,
            });
        },

        Dashboard.prototype.init = function () {
 			var dateArray = [];
            $.each(parameterList.date,function(key,value){
            	dateArray.push({ y : value , a : parameterList.first_value[key] , b: parameterList.second_value[key]  });
            });

            this.createBarChart('morris-bar-example', dateArray, 'y', ['a', 'b'], ['Settled payment value', 'Number of payments'], ['#e22a6f','#24d5d8', '#ab8ce4']);

            //creating donut chart for dashboard-1
            var $donutData = [
                {label: "Marketplace", value: 55},
                {label: "On-site", value: 30},
                {label: "Others", value: 15},
            ];
            this.createDonutChart('morris-donut-example', $donutData, ['#e22a6f', "#24d5d8", '#ab8ce4']);

        },
        //init
        $.Dashboard = new Dashboard, $.Dashboard.Constructor = Dashboard
}(window.jQuery),

	//initializing
    function ($) {
        "use strict";
        $.Dashboard.init();
    }(window.jQuery);
</script>