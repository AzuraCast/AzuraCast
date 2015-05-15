<?php

define('THOUSAND_SEPARATOR',true);

if (!extension_loaded('Zend OPcache')) {
    echo '<div style="background-color: #F2DEDE; color: #B94A48; padding: 1em;">You do not have the Zend OPcache extension loaded, sample data is being shown instead.</div>';
    require 'data-sample.php';
}

class OpCacheDataModel
{
    private $_configuration;
    private $_status;
    private $_d3Scripts = array();

    public function __construct()
    {
        $this->_configuration = opcache_get_configuration();
        $this->_status = opcache_get_status();
    }

    public function getPageTitle()
    {
        return 'PHP ' . phpversion() . " with OpCache {$this->_configuration['version']['version']}";
    }

    public function getStatusDataRows()
    {
        $rows = array();
        foreach ($this->_status as $key => $value) {
            if ($key === 'scripts') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($v === false) {
                        $value = 'false';
                    }
                    if ($v === true) {
                        $value = 'true';
                    }
                    if ($k === 'used_memory' || $k === 'free_memory' || $k === 'wasted_memory') {
                        $v = $this->_size_for_humans(
                            $v
                        );
                    }
                    if ($k === 'current_wasted_percentage' || $k === 'opcache_hit_rate') {
                        $v = number_format(
                                $v,
                                2
                            ) . '%';
                    }
                    if ($k === 'blacklist_miss_ratio') {
                        $v = number_format($v, 2) . '%';
                    }
                    if ($k === 'start_time' || $k === 'last_restart_time') {
                        $v = ($v ? date(DATE_RFC822, $v) : 'never');
                    }
                    if (THOUSAND_SEPARATOR === true && is_int($v)) {
                        $v = number_format($v);
                    }

                    $rows[] = "<tr><th>$k</th><td>$v</td></tr>\n";
                }
                continue;
            }
            if ($value === false) {
                $value = 'false';
            }
            if ($value === true) {
                $value = 'true';
            }
            $rows[] = "<tr><th>$key</th><td>$value</td></tr>\n";
        }

        return implode("\n", $rows);
    }

    public function getConfigDataRows()
    {
        $rows = array();
        foreach ($this->_configuration['directives'] as $key => $value) {
            if ($value === false) {
                $value = 'false';
            }
            if ($value === true) {
                $value = 'true';
            }
            if ($key == 'opcache.memory_consumption') {
                $value = $this->_size_for_humans($value);
            }
            $rows[] = "<tr><th>$key</th><td>$value</td></tr>\n";
        }

        return implode("\n", $rows);
    }

    public function getScriptStatusRows()
    {
        foreach ($this->_status['scripts'] as $key => $data) {
            $dirs[dirname($key)][basename($key)] = $data;
            $this->_arrayPset($this->_d3Scripts, $key, array(
                'name' => basename($key),
                'size' => $data['memory_consumption'],
            ));
        }

        asort($dirs);

        $basename = '';
        while (true) {
            if (count($this->_d3Scripts) !=1) break;
            $basename .= DIRECTORY_SEPARATOR . key($this->_d3Scripts);
            $this->_d3Scripts = reset($this->_d3Scripts);
        }

        $this->_d3Scripts = $this->_processPartition($this->_d3Scripts, $basename);
        $id = 1;

        $rows = array();
        foreach ($dirs as $dir => $files) {
            $count = count($files);
            $file_plural = $count > 1 ? 's' : null;
            $m = 0;
            foreach ($files as $file => $data) {
                $m += $data["memory_consumption"];
            }
            $m = $this->_size_for_humans($m);

            if ($count > 1) {
                $rows[] = '<tr>';
                $rows[] = "<th class=\"clickable\" id=\"head-{$id}\" colspan=\"3\" onclick=\"toggleVisible('#head-{$id}', '#row-{$id}')\">{$dir} ({$count} file{$file_plural}, {$m})</th>";
                $rows[] = '</tr>';
            }

            foreach ($files as $file => $data) {
                $rows[] = "<tr id=\"row-{$id}\">";
                $rows[] = "<td>" . $this->_format_value($data["hits"]) . "</td>";
                $rows[] = "<td>" . $this->_size_for_humans($data["memory_consumption"]) . "</td>";
                $rows[] = $count > 1 ? "<td>{$file}</td>" : "<td>{$dir}/{$file}</td>";
                $rows[] = '</tr>';
            }

            ++$id;
        }

        return implode("\n", $rows);
    }

    public function getScriptStatusCount()
    {
        return count($this->_status["scripts"]);
    }

    public function getGraphDataSetJson()
    {
        $dataset = array();
        $dataset['memory'] = array(
            $this->_status['memory_usage']['used_memory'],
            $this->_status['memory_usage']['free_memory'],
            $this->_status['memory_usage']['wasted_memory'],
        );

        $dataset['keys'] = array(
            $this->_status['opcache_statistics']['num_cached_keys'],
            $this->_status['opcache_statistics']['max_cached_keys'] - $this->_status['opcache_statistics']['num_cached_keys'],
            0
        );

        $dataset['hits'] = array(
            $this->_status['opcache_statistics']['misses'],
            $this->_status['opcache_statistics']['hits'],
            0,
        );

        $dataset['restarts'] = array(
            $this->_status['opcache_statistics']['oom_restarts'],
            $this->_status['opcache_statistics']['manual_restarts'],
            $this->_status['opcache_statistics']['hash_restarts'],
        );

        if (THOUSAND_SEPARATOR === true) {
            $dataset['TSEP'] = 1;
        } else {
            $dataset['TSEP'] = 0;
        }

        return json_encode($dataset);
    }

    public function getHumanUsedMemory()
    {
        return $this->_size_for_humans($this->getUsedMemory());
    }

    public function getHumanFreeMemory()
    {
        return $this->_size_for_humans($this->getFreeMemory());
    }

    public function getHumanWastedMemory()
    {
        return $this->_size_for_humans($this->getWastedMemory());
    }

    public function getUsedMemory()
    {
        return $this->_status['memory_usage']['used_memory'];
    }

    public function getFreeMemory()
    {
        return $this->_status['memory_usage']['free_memory'];
    }

    public function getWastedMemory()
    {
        return $this->_status['memory_usage']['wasted_memory'];
    }

    public function getWastedMemoryPercentage()
    {
        return number_format($this->_status['memory_usage']['current_wasted_percentage'], 2);
    }

    public function getD3Scripts()
    {
        return $this->_d3Scripts;
    }

    private function _processPartition($value, $name = null)
    {
        if (array_key_exists('size', $value)) {
            return $value;
        }

        $array = array('name' => $name,'children' => array());

        foreach ($value as $k => $v) {
            $array['children'][] = $this->_processPartition($v, $k);
        }

        return $array;
    }

    private function _format_value($value)
    {
        if (THOUSAND_SEPARATOR === true) {
            return number_format($value);
        } else {
            return $value;
        }
    }

    private function _size_for_humans($bytes)
    {
        if ($bytes > 1048576) {
            return sprintf('%.2f&nbsp;MB', $bytes / 1048576);
        } else {
            if ($bytes > 1024) {
                return sprintf('%.2f&nbsp;kB', $bytes / 1024);
            } else {
                return sprintf('%d&nbsp;bytes', $bytes);
            }
        }
    }

    // Borrowed from Laravel
    private function _arrayPset(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;
        $keys = explode(DIRECTORY_SEPARATOR, ltrim($key, DIRECTORY_SEPARATOR));
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

}

$dataModel = new OpCacheDataModel();
?>
<!DOCTYPE html>
<meta charset="utf-8">
<html>
<head>
    <style>
        body {
            font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
            margin: 0;
            padding: 0;
        }

        #container {
            width: 1024px;
            margin: auto;
            position: relative;
        }

        h1 {
            padding: 10px 0;
        }

        table {
            border-collapse: collapse;
        }

        tbody tr:nth-child(even) {
            background-color: #eee;
        }

        p.capitalize {
            text-transform: capitalize;
        }

        .tabs {
            position: relative;
            float: left;
            width: 60%;
        }

        .tab {
            float: left;
        }

        .tab label {
            background: #eee;
            padding: 10px 12px;
            border: 1px solid #ccc;
            margin-left: -1px;
            position: relative;
            left: 1px;
        }

        .tab [type=radio] {
            display: none;
        }

        .tab th, .tab td {
            padding: 8px 12px;
        }

        .content {
            position: absolute;
            top: 28px;
            left: 0;
            background: white;
            border: 1px solid #ccc;
            height: 450px;
            width: 100%;
            overflow: auto;
        }

        .content table {
            width: 100%;
        }

        .content th, .tab:nth-child(3) td {
            text-align: left;
        }

        .content td {
            text-align: right;
        }

        .clickable {
            cursor: pointer;
        }

        [type=radio]:checked ~ label {
            background: white;
            border-bottom: 1px solid white;
            z-index: 2;
        }

        [type=radio]:checked ~ label ~ .content {
            z-index: 1;
        }

        #graph {
            float: right;
            width: 40%;
            position: relative;
        }

        #graph > form {
            position: absolute;
            right: 60px;
            top: -20px;
        }

        #graph > svg {
            position: absolute;
            top: 0;
            right: 0;
        }

        #stats {
            position: absolute;
            right: 125px;
            top: 145px;
        }

        #stats th, #stats td {
            padding: 6px 10px;
            font-size: 0.8em;
        }

        #partition {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 10;
            top: 0;
            left: 0;
            background: #ddd;
            display: none;
        }

        #close-partition {
            display: none;
            position: absolute;
            z-index: 20;
            right: 15px;
            top: 15px;
            background: #f9373d;
            color: #fff;
            padding: 12px 15px;
        }

        #close-partition:hover {
            background: #D32F33;
            cursor: pointer;
        }

        #partition rect {
            stroke: #fff;
            fill: #aaa;
            fill-opacity: 1;
        }

        #partition rect.parent {
            cursor: pointer;
            fill: steelblue;
        }

        #partition text {
            pointer-events: none;
        }

        label {
            cursor: pointer;
        }
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/d3/3.0.1/d3.v3.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script>
        var hidden = {};
        function toggleVisible(head, row) {
            if (!hidden[row]) {
                d3.selectAll(row).transition().style('display', 'none');
                hidden[row] = true;
                d3.select(head).transition().style('color', '#ccc');
            } else {
                d3.selectAll(row).transition().style('display');
                hidden[row] = false;
                d3.select(head).transition().style('color', '#000');
            }
        }
    </script>
    <title><?php echo $dataModel->getPageTitle(); ?></title>
</head>

<body>
    <div id="container">
        <h1><?php echo $dataModel->getPageTitle(); ?></h1>

        <div class="tabs">

            <div class="tab">
                <input type="radio" id="tab-status" name="tab-group-1" checked>
                <label for="tab-status">Status</label>
                <div class="content">
                    <table>
                        <?php echo $dataModel->getStatusDataRows(); ?>
                    </table>
                </div>
            </div>

            <div class="tab">
                <input type="radio" id="tab-config" name="tab-group-1">
                <label for="tab-config">Configuration</label>
                <div class="content">
                    <table>
                        <?php echo $dataModel->getConfigDataRows(); ?>
                    </table>
                </div>
            </div>

            <div class="tab">
                <input type="radio" id="tab-scripts" name="tab-group-1">
                <label for="tab-scripts">Scripts (<?php echo $dataModel->getScriptStatusCount(); ?>)</label>
                <div class="content">
                    <table style="font-size:0.8em;">
                        <tr>
                            <th width="10%">Hits</th>
                            <th width="20%">Memory</th>
                            <th width="70%">Path</th>
                        </tr>
                        <?php echo $dataModel->getScriptStatusRows(); ?>
                    </table>
                </div>
            </div>

            <div class="tab">
                <input type="radio" id="tab-visualise" name="tab-group-1">
                <label for="tab-visualise">Visualise Partition</label>
                <div class="content"></div>
            </div>

        </div>

        <div id="graph">
            <form>
                <label><input type="radio" name="dataset" value="memory" checked> Memory</label>
                <label><input type="radio" name="dataset" value="keys"> Keys</label>
                <label><input type="radio" name="dataset" value="hits"> Hits</label>
                <label><input type="radio" name="dataset" value="restarts"> Restarts</label>
            </form>

            <div id="stats"></div>
        </div>
    </div>

    <div id="close-partition">&#10006; Close Visualisation</div>
    <div id="partition"></div>

    <script>
        var dataset = <?php echo $dataModel->getGraphDataSetJson(); ?>;

        var width = 400,
            height = 400,
            radius = Math.min(width, height) / 2,
            colours = ['#B41F1F', '#1FB437', '#ff7f0e'];

        d3.scale.customColours = function() {
            return d3.scale.ordinal().range(colours);
        };

        var colour = d3.scale.customColours();
        var pie = d3.layout.pie().sort(null);

        var arc = d3.svg.arc().innerRadius(radius - 20).outerRadius(radius - 50);
        var svg = d3.select("#graph").append("svg")
                    .attr("width", width)
                    .attr("height", height)
                    .append("g")
                    .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

        var path = svg.selectAll("path")
                      .data(pie(dataset.memory))
                      .enter().append("path")
                      .attr("fill", function(d, i) { return colour(i); })
                      .attr("d", arc)
                      .each(function(d) { this._current = d; }); // store the initial values

        d3.selectAll("input").on("change", change);
        set_text("memory");

        function set_text(t) {
            if (t === "memory") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Used</th><td><?php echo $dataModel->getHumanUsedMemory()?></td></tr>"+
                    "<tr><th style='background:#1FB437;'>Free</th><td><?php echo $dataModel->getHumanFreeMemory()?></td></tr>"+
                    "<tr><th style='background:#ff7f0e;' rowspan=\"2\">Wasted</th><td><?php echo $dataModel->getHumanWastedMemory()?></td></tr>"+
                    "<tr><td><?php echo $dataModel->getWastedMemoryPercentage()?>%</td></tr></table>"
                );
            } else if (t === "keys") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Cached keys</th><td>"+format_value(dataset[t][0])+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Free Keys</th><td>"+format_value(dataset[t][1])+"</td></tr></table>"
                );
            } else if (t === "hits") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Misses</th><td>"+format_value(dataset[t][0])+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Cache Hits</th><td>"+format_value(dataset[t][1])+"</td></tr></table>"
                );
            } else if (t === "restarts") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Memory</th><td>"+dataset[t][0]+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Manual</th><td>"+dataset[t][1]+"</td></tr>"+
                    "<tr><th style='background:#ff7f0e;'>Keys</th><td>"+dataset[t][2]+"</td></tr></table>"
                );
            }
        }

        function change() {
            // Filter out any zero values to see if there is anything left
            var remove_zero_values = dataset[this.value].filter(function(value) {
                return value > 0;
            });

            // Skip if the value is undefined for some reason
            if (typeof dataset[this.value] !== 'undefined' && remove_zero_values.length > 0) {
                $('#graph').find('> svg').show();
                path = path.data(pie(dataset[this.value])); // update the data
                path.transition().duration(750).attrTween("d", arcTween); // redraw the arcs
            // Hide the graph if we can't draw it correctly, not ideal but this works
            } else {
                $('#graph').find('> svg').hide();
            }

            set_text(this.value);
        }

        function arcTween(a) {
            var i = d3.interpolate(this._current, a);
            this._current = i(0);
            return function(t) {
                return arc(i(t));
            };
        }

        function size_for_humans(bytes) {
            if (bytes > 1048576) {
                return (bytes/1048576).toFixed(2) + ' MB';
            } else if (bytes > 1024) {
                return (bytes/1024).toFixed(2) + ' KB';
            } else return bytes + ' bytes';
        }

        function format_value(value) {
            if (dataset["TSEP"] == 1) {
                return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            } else {
                return value;
            }
        }

        var w = window.innerWidth,
            h = window.innerHeight,
            x = d3.scale.linear().range([0, w]),
            y = d3.scale.linear().range([0, h]);

        var vis = d3.select("#partition")
                    .style("width", w + "px")
                    .style("height", h + "px")
                    .append("svg:svg")
                    .attr("width", w)
                    .attr("height", h);

        var partition = d3.layout.partition()
                .value(function(d) { return d.size; });

        root = JSON.parse('<?php echo json_encode($dataModel->getD3Scripts()); ?>');

        var g = vis.selectAll("g")
                   .data(partition.nodes(root))
                   .enter().append("svg:g")
                   .attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; })
                   .on("click", click);

        var kx = w / root.dx,
                ky = h / 1;

        g.append("svg:rect")
         .attr("width", root.dy * kx)
         .attr("height", function(d) { return d.dx * ky; })
         .attr("class", function(d) { return d.children ? "parent" : "child"; });

        g.append("svg:text")
         .attr("transform", transform)
         .attr("dy", ".35em")
         .style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; })
         .text(function(d) { return d.name; })

        d3.select(window)
          .on("click", function() { click(root); })

        function click(d) {
            if (!d.children) return;

            kx = (d.y ? w - 40 : w) / (1 - d.y);
            ky = h / d.dx;
            x.domain([d.y, 1]).range([d.y ? 40 : 0, w]);
            y.domain([d.x, d.x + d.dx]);

            var t = g.transition()
                     .duration(d3.event.altKey ? 7500 : 750)
                     .attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; });

            t.select("rect")
             .attr("width", d.dy * kx)
             .attr("height", function(d) { return d.dx * ky; });

            t.select("text")
             .attr("transform", transform)
             .style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; });

            d3.event.stopPropagation();
        }

        function transform(d) {
            return "translate(8," + d.dx * ky / 2 + ")";
        }

        $(document).ready(function() {

            function handleVisualisationToggle(close) {

                $('#partition, #close-partition').fadeToggle();

                // Is the visualisation being closed? If so show the status tab again
                if (close) {

                    $('#tab-visualise').removeAttr('checked');
                    $('#tab-status').trigger('click');

                }

            }

            $('label[for="tab-visualise"], #close-partition').on('click', function() {

                handleVisualisationToggle(($(this).attr('id') === 'close-partition'));

            });

            $(document).keyup(function(e) {

                if (e.keyCode == 27) handleVisualisationToggle(true);

            });

        });
    </script>
</body>
</html>
