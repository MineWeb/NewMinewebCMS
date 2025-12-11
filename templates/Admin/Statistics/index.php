<?php

$colors = ['#1abc9c', '#2ecc71', '#3498db', '#e67e22', '#e74c3c'];
$hosts = [];
$ref_pie = [];
$i = 0;
foreach ($referers as $ref => $visitcount) {
    $host = parse_url($ref, PHP_URL_HOST);
    if (!in_array($host, $hosts)) {
        if ($host != 'null') {
            $hosts[] = $host;
        } else {
            $hosts[] = 'N/A';
        }
        if (!isset($colors[$i])) {
            $i = 0;
        }
        $color = $colors[$i];

        $ref_pie['color'][] = $color;
        $ref_pie['value'][] = $visitcount;
        $ref_pie['label'][] = $host;
        $i++;
    }
}

$pages_pie = [];
$i = 0;
foreach ($pages as $page => $visitcount) {
    $page = addslashes(urldecode($page));
    if (!isset($colors[$i])) {
        $i = 0;
    }
    $color = $colors[$i];

    $pages_pie['color'][] = $color;
    $pages_pie['value'][] = $visitcount;
    $pages_pie['label'][] = $page;
    $i++;
}
?>
<section class="content">
    <div class="row">

        <div class="col-md-12">
            <a href="<?= $this->Url->build(['_name' => 'admin_statistics_reset']) ?>"
               class="btn btn-info btn-block"><?= __('STATS__RESET_LABEL') ?></a>
        </div>
        <br><br>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('STATS__VISITS_REFERING_WEBSITE') ?></h3>
                </div>
                <div class="card-body">
                    <canvas id="pieChart_referers" height="300"></canvas>
                    <script>
                        new Chart(document.getElementById("pieChart_referers"), {
                            type: 'doughnut',
                            data: {
                                labels: <?= isset($ref_pie['label']) ? json_encode($ref_pie['label']) : "[]" ?>,
                                datasets: [{
                                    backgroundColor: <?= isset($ref_pie['color']) ? json_encode($ref_pie['color']) : "[]" ?>,
                                    data: <?= isset($ref_pie['value']) ? json_encode($ref_pie['value']) : "[]" ?>
                                }]
                            },
                            options: {
                                title: {
                                    display: false
                                },
                                responsive: false,
                                legend: {display: false}
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('STATS__VISITS_PAGES') ?></h3>
                </div>
                <div class="card-body">

                    <canvas id="pieChart_pages" height="300"></canvas>
                    <script>
                        new Chart(document.getElementById("pieChart_pages"), {
                            type: 'doughnut',
                            data: {
                                labels: <?= isset($pages_pie['label']) ? json_encode($pages_pie['label']) : "[]" ?>,
                                datasets: [{
                                    backgroundColor: <?= isset($pages_pie['color']) ? json_encode($pages_pie['color']) : "[]" ?>,
                                    data: <?= isset($pages_pie['value']) ? json_encode($pages_pie['value']) : "[]" ?>
                                }]
                            },
                            options: {
                                title: {
                                    display: false
                                },
                                responsive: false,
                                legend: {display: false}
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('GLOBAL__VISITORS') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Html->script('highcharts') ?>
                    <div id="visits"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var url = '<?= $this->Url->build(['_name' => 'admin_statistics_get_visits']) ?>';

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                Highcharts.chart('visits', {
                    chart: {
                        zoomType: 'x'
                    },
                    title: {
                        text: ''
                    },
                    subtitle: {
                        text: false
                    },
                    xAxis: {
                        type: 'datetime',
                        title: {
                            text: '<?= __('GLOBAL__CREATED') ?>'
                        }
                    },
                    yAxis: {
                        title: {
                            text: '<?= __('GLOBAL__VISITORS') ?>'
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    plotOptions: {
                        area: {
                            fillColor: {
                                linearGradient: {
                                    x1: 0,
                                    y1: 0,
                                    x2: 0,
                                    y2: 1
                                },
                                stops: [
                                    [0, Highcharts.getOptions().colors[0]],
                                    [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                                ]
                            },
                            marker: {
                                radius: 2
                            },
                            lineWidth: 1,
                            states: {
                                hover: {
                                    lineWidth: 1
                                }
                            },
                            threshold: null
                        }
                    },
                    series: [{
                        type: 'area',
                        name: '<?= __('GLOBAL__VISITORS') ?>',
                        data: data
                    }]
                });
            });
    });
</script>
