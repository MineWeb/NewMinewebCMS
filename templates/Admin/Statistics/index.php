<?php

$colors = ['#1abc9c', '#2ecc71', '#3498db', '#e67e22', '#e74c3c'];

$hosts = [];
$ref_pie = [];
$i = 0;

foreach ($referers as $ref => $visitcount) {
    $ref = is_string($ref) ? trim($ref) : '';
    $host = '';

    if ($ref !== '') {
        $parsedHost = parse_url($ref, PHP_URL_HOST);
        $host = is_string($parsedHost) ? trim($parsedHost) : '';
    }

    if ($host === '' || $host === 'null') {
        $host = 'N/A';
    }

    if (!in_array($host, $hosts, true)) {
        $hosts[] = $host;

        if (!isset($colors[$i])) {
            $i = 0;
        }

        $color = $colors[$i];

        $ref_pie['color'][] = $color;
        $ref_pie['value'][] = (int)$visitcount;
        $ref_pie['label'][] = $host;
        $i++;
    }
}

$pages_pie = [];
$i = 0;

foreach ($pages as $page => $visitcount) {
    $page = addslashes(urldecode((string)$page));

    if (!isset($colors[$i])) {
        $i = 0;
    }

    $color = $colors[$i];

    $pages_pie['color'][] = $color;
    $pages_pie['value'][] = (int)$visitcount;
    $pages_pie['label'][] = $page;
    $i++;
}

$refLabels = isset($ref_pie['label']) ? $ref_pie['label'] : [];
$refColors = isset($ref_pie['color']) ? $ref_pie['color'] : [];
$refValues = isset($ref_pie['value']) ? $ref_pie['value'] : [];

$pageLabels = isset($pages_pie['label']) ? $pages_pie['label'] : [];
$pageColors = isset($pages_pie['color']) ? $pages_pie['color'] : [];
$pageValues = isset($pages_pie['value']) ? $pages_pie['value'] : [];

?>
<section class="content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-info mb-0">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-chart-pie mr-2"></i><?= __('GLOBAL__STATISTICS') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_statistics_reset']) ?>"
                               class="btn btn-info btn-sm">
                                <i class="fas fa-undo mr-2"></i><?= __('STATS__RESET_LABEL') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <div class="text-sm text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?= __('STATS__HINT') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-12 col-lg-6">
                <div class="card card-outline card-primary h-100">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-globe-europe mr-2"></i><?= __('STATS__VISITS_REFERING_WEBSITE') ?>
                        </h3>
                    </div>

                    <div class="card-body">
                        <?php if (empty($refValues)) : ?>
                            <div class="alert alert-light border mb-0">
                                <i class="fas fa-inbox mr-2"></i><?= __('GLOBAL__NO_DATA') ?>
                            </div>
                        <?php else : ?>
                            <div class="row">
                                <div class="col-12 col-xl-7">
                                    <div class="position-relative" style="min-height: 320px;">
                                        <canvas id="pieChart_referers" height="300"></canvas>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-5">
                                    <div class="small text-muted mb-2">
                                        <i class="fas fa-list mr-1"></i><?= __('GLOBAL__DETAILS') ?>
                                    </div>

                                    <div class="table-responsive" style="max-height: 320px;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                            <tr>
                                                <th><?= __('GLOBAL__SOURCE') ?></th>
                                                <th class="text-right"><?= __('GLOBAL__VISITS') ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($refLabels as $idx => $label) : ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <span class="badge mr-2"
                                                              style="background-color: <?= h((string)($refColors[$idx] ?? '#6c757d')) ?>;">
                                                            &nbsp;
                                                        </span>
                                                        <?= h((string)$label) ?>
                                                    </td>
                                                    <td class="text-right align-middle">
                                                        <?= (int)($refValues[$idx] ?? 0) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    new Chart(document.getElementById("pieChart_referers"), {
                                        type: 'doughnut',
                                        data: {
                                            labels: <?= json_encode($refLabels) ?>,
                                            datasets: [{
                                                backgroundColor: <?= json_encode($refColors) ?>,
                                                data: <?= json_encode($refValues) ?>
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            legend: {display: false},
                                            cutoutPercentage: 62,
                                            tooltips: {
                                                callbacks: {
                                                    label: function (tooltipItem, data) {
                                                        let label = data.labels[tooltipItem.index] || '';
                                                        let value = data.datasets[0].data[tooltipItem.index] || 0;
                                                        return label + ' : ' + value;
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card card-outline card-success h-100">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-alt mr-2"></i><?= __('STATS__VISITS_PAGES') ?>
                        </h3>
                    </div>

                    <div class="card-body">
                        <?php if (empty($pageValues)) : ?>
                            <div class="alert alert-light border mb-0">
                                <i class="fas fa-inbox mr-2"></i><?= __('GLOBAL__NO_DATA') ?>
                            </div>
                        <?php else : ?>
                            <div class="row">
                                <div class="col-12 col-xl-7">
                                    <div class="position-relative" style="min-height: 320px;">
                                        <canvas id="pieChart_pages" height="300"></canvas>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-5">
                                    <div class="small text-muted mb-2">
                                        <i class="fas fa-list mr-1"></i><?= __('GLOBAL__DETAILS') ?>
                                    </div>

                                    <div class="table-responsive" style="max-height: 320px;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                            <tr>
                                                <th><?= __('GLOBAL__PAGE') ?></th>
                                                <th class="text-right"><?= __('GLOBAL__VISITS') ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($pageLabels as $idx => $label) : ?>
                                                <tr>
                                                    <td class="align-middle text-truncate" style="max-width: 260px;">
                                                        <span class="badge mr-2"
                                                              style="background-color: <?= h((string)($pageColors[$idx] ?? '#6c757d')) ?>;">
                                                            &nbsp;
                                                        </span>
                                                        <span title="<?= h((string)$label) ?>"><?= h((string)$label) ?></span>
                                                    </td>
                                                    <td class="text-right align-middle">
                                                        <?= (int)($pageValues[$idx] ?? 0) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    new Chart(document.getElementById("pieChart_pages"), {
                                        type: 'doughnut',
                                        data: {
                                            labels: <?= json_encode($pageLabels) ?>,
                                            datasets: [{
                                                backgroundColor: <?= json_encode($pageColors) ?>,
                                                data: <?= json_encode($pageValues) ?>
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            legend: {display: false},
                                            cutoutPercentage: 62,
                                            tooltips: {
                                                callbacks: {
                                                    label: function (tooltipItem, data) {
                                                        let label = data.labels[tooltipItem.index] || '';
                                                        let value = data.datasets[0].data[tooltipItem.index] || 0;
                                                        return label + ' : ' + value;
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-chart-area mr-2"></i><?= __('GLOBAL__VISITORS') ?>
                            </h3>

                            <div class="text-sm text-muted">
                                <i class="fas fa-search-plus mr-1"></i><?= __('STATS__ZOOM_HINT') ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <?= $this->Html->script('highcharts') ?>
                        <div id="visits" style="min-height: 360px;"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let url = '<?= $this->Url->build(['_name' => 'admin_statistics_get_visits']) ?>';

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
