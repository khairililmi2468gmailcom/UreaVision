<div class="container mt-4">
    <div class="card shadow p-3 bg-white">
        <div class="card-body">
            <h1 class="card-title text-center">Trending Analysis</h1>
        </div>
    </div>

    <div class="row mt-4 border p-3 bg-white">
        <div class="col-md-4">
            <label for="jenis">Jenis:</label>
            <select id="jenis" class="form-select">
                <option></option> <!-- Opsi kosong untuk placeholder -->
            </select>
        </div>
        <div class="col-md-4">
            <label for="date">Date:</label>
            <input type="date" id="date" class="form-control">
        </div>
        <div class="col-md-4">
            <button id="add-item" class="btn btn-success mt-4">Tambahkan Item</button>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div id="selected-options" class="selected-options border rounded p-2" style="min-height: 80px;">
                <!-- Selected options will be displayed here -->
            </div>
        </div>
    </div>
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="bi bi-arrow-repeat"></i> <!-- Spinner icon -->
        </div>
    </div>


    <div class="row mt-4 justify-content-center">
        <div class="col-md-12 text-center">
            <button id="play-button" class="btn btn-primary">
                <i class="bi bi-play-fill"></i> Play
            </button>
        </div>
    </div>
    <div class="mt-4 text-right">
        <button id="refresh-button" class="btn btn-secondary">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <button id="delete-button" class="btn btn-danger">
            <i class="bi bi-trash"></i> Delete Charts
        </button>
    </div>


    <div class="mt-4">
        <h5>Line Chart Gabungan</h5>
        <canvas id="combinedChart"></canvas>
    </div>

    <div class="mt-4">
        <h5>Line Chart Item</h5>
        <div class="row individual-charts">
            <!-- Individual charts will be displayed here -->
        </div>
    </div>
</div>
<style>
/* Overlay style */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    /* Semi-transparent black */
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    /* Ensure it's above all content */
}

/* Spinner style */
.loading-spinner {
    font-size: 3rem;
    /* Adjust size as needed */
    color: #007bff;
    /* Spinner color */
    animation: spin 1s linear infinite;
}

/* Spinner animation */
@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* Optional: Adjust icon size */
.loading-spinner i {
    font-size: 4rem;
    /* Adjust icon size if needed */
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 with placeholder
    $('#jenis').select2({
        placeholder: "Pilih jenis...",
        allowClear: true // Allows clearing the selection
    });

    // Fetch data from the API endpoint
    fetch('<?= base_url('/columns/getColumns') ?>')
        .then(response => response.json())
        .then(data => {
            // Filter out "Time_Stamp" column
            const filteredData = data.filter(column => column !== "Time_Stamp");

            // Populate the select element with the filtered data
            filteredData.forEach(column => {
                const option = new Option(column, column, false, false);
                $('#jenis').append(option);
            });

            // Refresh the Select2 dropdown to display the new options
            $('#jenis').trigger('change');
        })
        .catch(error => console.error('Error fetching data:', error));
});

let selectedOptions = [];
let combinedChart = null; // Variable to hold the combined chart instance

// Event listener for the "Add Item" button
$('#add-item').on('click', function() {
    const date = $('#date').val();
    const jenis = $('#jenis').val();

    if (!date || !jenis) {
        alert('Opsi dan tanggal tidak boleh kosong.');
        return;
    }

    // Check if the option is already selected
    if (selectedOptions.find(option => option.jenis === jenis)) {
        alert('This option is already added.');
        return;
    }

    // Limit to a maximum of 3 options
    if (selectedOptions.length >= 3) {
        alert('Anda hanya dapat menampilkan 3 item.');
        return;
    }

    // Add the option to the selectedOptions array
    selectedOptions.push({
        jenis,
        date
    });

    // Update the selected-options div
    $('#selected-options').append(
        `<div class="selected-option p-1">${jenis} <button class="btn btn-danger btn-sm remove-item" data-jenis="${jenis}">Remove</button></div>`
    );

    // Clear the select and date inputs
    $('#jenis').val(null).trigger('change');
    $('#date').val('');
});

document.getElementById('play-button').addEventListener('click', function() {
    if (selectedOptions.length === 0) {
        alert("Belum memilih opsi!");
        return;
    }

    // Show the loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';

    const chartData = []; // Clear previous chart data

    // Fetch data for each selected option
    Promise.all(selectedOptions.map(option => {
            const apiUrl =
                `http://localhost:8080/proxy/fetch-data?date=${encodeURIComponent(option.date)}&column=${encodeURIComponent(option.jenis)}`;

            return fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    // console.log(`Data for ${option.jenis}:`, data); 
                    // Extract timestamp and values
                    const timestamps = data.data.map(item => item[
                        0]); // Assuming item[0] is the timestamp
                    const values = data.data.map(item => item[1]); // Assuming item[1] is the value

                    return {
                        label: option.jenis,
                        data: values,
                        timestamps: timestamps,
                        borderColor: getRandomColor(),
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        fill: false
                    };
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    return null; // Handle errors gracefully
                });
        }))
        .then(results => {
            // Hide the loading overlay
            document.getElementById('loading-overlay').style.display = 'none';

            // Filter out any null results (in case of fetch errors)
            results = results.filter(result => result !== null);

            if (results.length === 0) {
                alert("Failed to fetch data for all options.");
                return;
            }

            // console.log('Combined Chart Data:', results); 

            // Update the combined chart and individual charts
            updateCombinedChart(results);
            displayIndividualCharts(results);
        })
        .catch(() => {
            // Hide the loading overlay if an error occurs
            document.getElementById('loading-overlay').style.display = 'none';
        });
});

function updateCombinedChart(data) {
    // Destroy the existing chart if it exists
    if (combinedChart) {
        combinedChart.destroy();
    }

    const ctxCombined = document.getElementById('combinedChart').getContext('2d');

    // console.log('Combined Chart Data before rendering:', data); 

    // Assuming all datasets have the same timestamps
    const labels = data[0].timestamps.map((fullTimestamp) => {
        const timePart = fullTimestamp.split(" ")[1]; // Extract HH:MM:SS
        return timePart;
    });

    combinedChart = new Chart(ctxCombined, {
        type: 'line',
        data: {
            labels: labels,
            datasets: data.map(dataset => ({
                label: dataset.label,
                data: dataset.data, // Data values
                borderColor: dataset.borderColor,
                backgroundColor: dataset.backgroundColor,
                fill: false,
            })),
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Time (HH:MM:SS)'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            return data[0].timestamps[index]; // Full timestamp display in tooltip
                        },
                        label: function(tooltipItem) {
                            const value = tooltipItem.raw;
                            return `Value: ${value}`;
                        }
                    }
                }
            }
        }
    });
}

// Function to display individual charts
function displayIndividualCharts(data) {
    const individualChartsContainer = document.querySelector('.individual-charts');
    individualChartsContainer.innerHTML = ''; // Clear previous charts

    data.forEach(dataset => {
        // console.log(`Individual Chart Data for ${dataset.label}:`, dataset
        // .data); // Log data for each individual chart

        const colDiv = document.createElement('div');
        colDiv.className = 'col-md-4';
        const canvas = document.createElement('canvas');
        colDiv.appendChild(canvas);
        individualChartsContainer.appendChild(colDiv);

        const labels = dataset.timestamps.map((fullTimestamp) => {
            const timePart = fullTimestamp.split(" ")[1]; // Extract "HH:MM:SS" part
            return timePart;
        });

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels, // Use "hh:mm:ss" for x-axis labels
                datasets: [{
                    label: dataset.label,
                    data: dataset.data,
                    borderColor: dataset.borderColor,
                    backgroundColor: dataset.backgroundColor,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time_Stamp (hh:mm:ss)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                return dataset.timestamps[index]; // Show full timestamp in tooltip
                            }
                        }
                    }
                }
            }
        });
    });
}



document.getElementById('refresh-button').addEventListener('click', function() {
    // Check if there are selected options before refreshing
    if (selectedOptions.length === 0) {
        alert("No options selected to refresh!");
        return;
    }

    // Show the loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';

    const chartData = []; // Clear previous chart data

    // Fetch data for each selected option
    Promise.all(selectedOptions.map(option => {
            const apiUrl =
                `http://localhost:8080/proxy/fetch-data?date=${encodeURIComponent(option.date)}&column=${encodeURIComponent(option.jenis)}`;

            return fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    return {
                        label: option.jenis,
                        data: data.data.map(item => item[
                            1]), // Assuming item[1] is the value to plot
                        borderColor: getRandomColor(),
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        fill: false
                    };
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    return null; // Handle errors gracefully
                });
        }))
        .then(results => {
            // Hide the loading overlay
            document.getElementById('loading-overlay').style.display = 'none';

            // Filter out any null results (in case of fetch errors)
            results = results.filter(result => result !== null);

            if (results.length === 0) {
                alert("Failed to fetch data for all options.");
                return;
            }

            // Update the combined chart and individual charts
            updateCombinedChart(results);
            displayIndividualCharts(results);
        })
        .catch(() => {
            // Hide the loading overlay if an error occurs
            document.getElementById('loading-overlay').style.display = 'none';
        });
});


document.getElementById('delete-button').addEventListener('click', function() {
    // Confirm deletion
    if (confirm("Apakah anda yakin menghapus Chart?")) {
        // Destroy the combined chart if it exists
        if (combinedChart) {
            combinedChart.destroy();
            combinedChart = null;
        }

        // Clear individual charts
        const individualChartsContainer = document.querySelector('.individual-charts');
        individualChartsContainer.innerHTML = ''; // Clear previous charts

        // Clear selected options
        selectedOptions = [];
        $('#selected-options').empty();

        // Optionally, show a message to the user
        alert("All charts have been deleted.");
    }
});
// Event listener for removing items
$(document).on('click', '.remove-item', function() {
    const jenis = $(this).data('jenis');
    selectedOptions = selectedOptions.filter(option => option.jenis !== jenis);

    // Update the selected-options div
    $(this).parent().remove();
});

function getRandomColor() {
    const r = Math.floor(Math.random() * 255);
    const g = Math.floor(Math.random() * 255);
    const b = Math.floor(Math.random() * 255);
    return `rgba(${r}, ${g}, ${b}, 1)`;
}
</script>