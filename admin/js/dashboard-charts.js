// Building Requests Chart
const buildingChart = new Chart(document.getElementById('buildingChart'), {
    type: 'bar',
    data: {
        labels: buildingStats.labels,
        datasets: [{
            label: 'Number of Requests',
            data: buildingStats.data,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Top 5 Buildings by Request Count'
            }
        }
    }
});

// Issue Categories Chart
const issuesChart = new Chart(document.getElementById('issuesChart'), {
    type: 'doughnut',
    data: {
        labels: issueStats.labels,
        datasets: [{
            data: issueStats.data,
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)',
                'rgba(153, 102, 255, 0.5)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Distribution of Reported Issues'
            },
            legend: {
                position: 'bottom'
            }
        }
    }
});
