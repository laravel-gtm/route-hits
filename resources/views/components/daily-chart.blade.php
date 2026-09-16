@props(['daily'])
<canvas id="daily" height="80"></canvas>
<script>
    new Chart(document.getElementById('daily'), {
        type: 'line',
        data: {
            labels: @json(array_keys($daily)),
            datasets: [{
                data: @json(array_values($daily)),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });
</script>
