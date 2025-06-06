/**
 * Charts management for Skill Development Tracker
 */

/**
 * Create a doughnut chart to display skill distribution
 * @param {string} elementId - Canvas element ID
 * @param {Array} data - Chart data
 * @param {Array} labels - Chart labels
 * @param {Array} colors - Chart colors
 */
function createDoughnutChart(elementId, data, labels, colors) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right',
                labels: {
                    fontColor: '#718096'
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        const dataset = data.datasets[tooltipItem.datasetIndex];
                        const total = dataset.data.reduce((previousValue, currentValue) => previousValue + currentValue);
                        const currentValue = dataset.data[tooltipItem.index];
                        const percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return `${data.labels[tooltipItem.index]}: ${currentValue} (${percentage}%)`;
                    }
                }
            }
        }
    });
}

/**
 * Create a bar chart for skill growth analysis
 * @param {string} elementId - Canvas element ID
 * @param {Array} skillNames - Skill names
 * @param {Array} initialData - Initial skill level data
 * @param {Array} currentData - Current skill level data
 */
function createSkillGrowthChart(elementId, skillNames, initialData, currentData) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: skillNames,
            datasets: [
                {
                    label: 'Initial Advanced/Expert %',
                    data: initialData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Current Advanced/Expert %',
                    data: currentData,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Percentage of Interns at Advanced/Expert Level'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Skills'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Skill Growth: Initial vs Current Proficiency'
                }
            }
        }
    });
}

/**
 * Create a horizontal bar chart for department comparison
 * @param {string} elementId - Canvas element ID
 * @param {Array} departments - Department names
 * @param {Array} proficiencyData - Department proficiency data
 */
function createDepartmentComparisonChart(elementId, departments, proficiencyData) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'horizontalBar', // This is deprecated in Chart.js v3+, use 'bar' with 'indexAxis: 'y'' instead
        data: {
            labels: departments,
            datasets: [
                {
                    label: 'Department Proficiency %',
                    data: proficiencyData,
                    backgroundColor: 'rgba(153, 102, 255, 0.5)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Overall Proficiency Percentage'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Department Skill Proficiency Comparison'
                }
            }
        }
    });
}

/**
 * Create a line chart for skill progress over time
 * @param {string} elementId - Canvas element ID
 * @param {Array} timeLabels - Time period labels
 * @param {Array} skillData - Skill progress data
 */
function createSkillProgressChart(elementId, timeLabels, skillData) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: skillData
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Proficiency Percentage'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Skill Progress Over Time'
                }
            }
        }
    });
}

/**
 * Create a radar chart for skill comparison
 * @param {string} elementId - Canvas element ID
 * @param {Array} labels - Skill labels
 * @param {Array} datasets - Comparison datasets
 */
function createSkillComparisonRadar(elementId, labels, datasets) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            scale: {
                ticks: {
                    beginAtZero: true,
                    max: 4,
                    stepSize: 1,
                    callback: function(value) {
                        const labels = ['', 'Beginner', 'Intermediate', 'Advanced', 'Expert'];
                        return labels[value];
                    }
                }
            }
        }
    });
}
