<div id="chart-{{ $getName() }}" style="width: 100%; height: 400px;"></div>


<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var options = @json($options);
        var chart = new ApexCharts(document.querySelector("#chart-{{ $getName() }}"), options);
        chart.render();
    });
</script>
