// Example with Chart.js (frontend)
const ctx = document.getElementById('aiChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['2026-03-10','2026-03-11'], // days
        datasets: [{ label: 'AI Questions', data: [12, 25] }]
    }
});