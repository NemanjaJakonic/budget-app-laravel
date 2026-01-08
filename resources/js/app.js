import {
    Chart,
    CategoryScale,
    LinearScale,
    BarElement,
    BarController,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

// Register Chart.js components
Chart.register(
    CategoryScale,
    LinearScale,
    BarElement,
    BarController,
    Title,
    Tooltip,
    Legend
);

// Make Chart available globally for use in Blade templates
window.Chart = Chart;