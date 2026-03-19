/**
 * Storytelling Manager Admin Dashboard Charts and Interactive elements
 */
document.addEventListener('DOMContentLoaded', function () {

    // --- Dashboard Charts logic ---
    if (typeof storytellingManagerStats !== 'undefined') {
        const stats = storytellingManagerStats;
        const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#46BFBD'];

        function createChart(id, listId, data) {
            if (!document.querySelector("#" + id)) {
                return;
            }

            const labels = Object.keys(data);
            const values = labels.map(l => data[l].count);
            const companyArrays = labels.map(l => data[l].companies);

            const options = {
                series: values,
                chart: {
                    type: 'donut',
                    height: 220,
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 300
                    }
                },
                colors: colors,
                labels: labels,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '85%'
                        },
                        expandOnClick: false
                    }
                },
                states: {
                    hover: { filter: { type: 'none' } },
                    active: { filter: { type: 'none' } }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: false
                },
                stroke: {
                    show: true,
                    width: 1,
                    colors: ['#fff']
                },
                tooltip: {
                    enabled: false
                }
            };

            const chart = new ApexCharts(document.querySelector("#" + id), options);
            chart.render();

            const listContainer = document.querySelector("#" + listId);
            if (listContainer) {
                let html = '';
                labels.forEach((label, index) => {
                    const color = colors[index % colors.length];
                    const count = values[index];
                    const companies = companyArrays[index];

                    html += `
                    <div class="chart-list-item">
                        <div class="color-circle" style="background-color: ${color}"></div>
                        <div class="list-content">
                            <span class="list-label">${label} (${count})</span>
                            <ul class="list-companies-ul">
                                ${companies.map(c => `<li>${c}</li>`).join('')}
                            </ul>
                        </div>
                    </div>`;
                });
                listContainer.innerHTML = html;
            }
        }

        function createBarChart(id, listId, data) {
            if (!document.querySelector("#" + id)) {
                return;
            }

            const labels = Object.keys(data);
            const values = labels.map(l => data[l].count);
            const companyArrays = labels.map(l => data[l].companies);

            const options = {
                series: [{
                    name: 'Registros',
                    data: values
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 300
                    }
                },
                colors: [colors[0], colors[1]],
                plotOptions: {
                    bar: {
                        distributed: true,
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: false
                },
                xaxis: {
                    categories: labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    show: true,
                    forceNiceScale: true,
                    labels: {
                        formatter: (val) => Math.floor(val)
                    }
                },
                states: {
                    hover: { filter: { type: 'none' } },
                    active: { filter: { type: 'none' } }
                },
                tooltip: {
                    enabled: false
                }
            };

            const chart = new ApexCharts(document.querySelector("#" + id), options);
            chart.render();

            const listContainer = document.querySelector("#" + listId);
            if (listContainer) {
                let html = '';
                labels.forEach((label, index) => {
                    const color = colors[index % colors.length];
                    const count = values[index];
                    const companies = companyArrays[index];

                    html += `
                    <div class="chart-list-item">
                        <div class="color-circle" style="background-color: ${color}"></div>
                        <div class="list-content">
                            <span class="list-label">${label} (${count})</span>
                            <ul class="list-companies-ul">
                                ${companies.map(c => `<li>${c}</li>`).join('')}
                            </ul>
                        </div>
                    </div>`;
                });
                listContainer.innerHTML = html;
            }
        }

        // Initialize charts
        createChart('chart-industry', 'list-industry', stats.industry);
        if (stats.radar_metrics && document.querySelector("#chart-radar-global")) {
            const categories = Object.keys(stats.radar_metrics);
            const dataAvg = Object.values(stats.radar_metrics);

            var radarSeries = [{
                name: 'Promedio General',
                data: dataAvg
            }];

            const palette = ['#008ffb', '#00e396', '#feb019', '#ff4560', '#775dd0', '#3f51b5', '#546e7a', '#d4526e', '#8d5b4c', '#f86624', '#d7263d', '#1b998b', '#2e294e', '#f46036', '#e2c044'];
            var definedColors = [palette[0]];

            if (stats.radar_participants && stats.radar_participants.length > 0) {
                stats.radar_participants.forEach(function(p, i) {
                    radarSeries.push(p);
                    definedColors.push(palette[(i + 1) % palette.length]);
                });
            }

            var originalSeries = JSON.parse(JSON.stringify(radarSeries));
            var togglePromedio = document.querySelector("#toggle-promedio");
            var participantCheckboxes = document.querySelectorAll(".toggle-participant");

            function getActiveSeriesConfig() {
                var activeSeries = [];
                var activeColors = [];

                if (togglePromedio && togglePromedio.checked) {
                    activeSeries.push(originalSeries[0]);
                    activeColors.push(definedColors[0]);
                }

                participantCheckboxes.forEach(function(cb) {
                    if (cb.checked) {
                        const pName = cb.value;
                        const index = originalSeries.findIndex(s => s.name === pName);
                        if (index !== -1) {
                            activeSeries.push(originalSeries[index]);
                            activeColors.push(definedColors[index]);
                        }
                    }
                });

                if (activeSeries.length === 0) {
                    return { 
                        series: [{ name: 'Vacío', data: [0, 0, 0, 0, 0, 0, 0] }], 
                        colors: ['transparent'] 
                    };
                }
                return { series: activeSeries, colors: activeColors };
            }

            var initialConfig = getActiveSeriesConfig();

            var radarOptions = {
                series: initialConfig.series,
                colors: initialConfig.colors,
                chart: {
                    height: 500,
                    type: 'radar',
                    toolbar: { show: false },
                    fontFamily: 'Helvetica, Arial, sans-serif'
                },
                labels: categories,
                stroke: {
                    width: 2
                },
                fill: {
                    opacity: 0.1
                },
                markers: {
                    size: 3
                },
                yaxis: {
                    min: 0,
                    max: 5,
                    tickAmount: 5,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1);
                        }
                    }
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    fontSize: '11px',
                    markers: {
                        width: 8,
                        height: 8
                    }
                }
            };
            var radarChart = new ApexCharts(document.querySelector("#chart-radar-global"), radarOptions);
            radarChart.render();

            function updateRadarChart() {
                const config = getActiveSeriesConfig();
                radarChart.updateOptions({
                    series: config.series,
                    colors: config.colors
                });
            }

            if (togglePromedio) {
                togglePromedio.addEventListener("change", updateRadarChart);
            }

            if (participantCheckboxes) {
                participantCheckboxes.forEach(function(cb) {
                    cb.addEventListener("change", updateRadarChart);
                });
            }
        }

    }

    // --- Toggle Active Status AJAX (Works on both Dashboard and Registros if buttons exist) ---
    const toggleButtons = document.querySelectorAll('.storytelling-toggle-active');
    if (toggleButtons.length > 0) {
        toggleButtons.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const id = this.getAttribute('data-id');
                const isActive = this.checked ? 1 : 0;

                if (typeof storytellingManagerAdmin === 'undefined') {
                    console.error('Storytelling Manager admin object not found');
                    return;
                }

                // Visual feedback on the container
                const container = this.closest('.storytelling-switch') || this;
                container.style.opacity = '0.5';

                fetch(storytellingManagerAdmin.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'storytelling_toggle_active',
                        id: id,
                        is_active: isActive,
                        security: storytellingManagerAdmin.nonce
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        container.style.opacity = '1';
                        if (!data.success) {
                            alert('Error al actualizar el estado.');
                            this.checked = !this.checked;
                        }
                    })
                    .catch(error => {
                        container.style.opacity = '1';
                        console.error('Error:', error);
                        this.checked = !this.checked;
                    });
            });
        });
    }
});
