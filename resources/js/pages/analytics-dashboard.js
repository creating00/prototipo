import { Chart, registerables } from "chart.js";
Chart.register(...registerables);

document.addEventListener("DOMContentLoaded", () => {
    if (!window.analyticsData) return;

    const data = window.analyticsData;

    /** REVENUE CHART (FLUJO MENSUAL) **/
    const revenueCtx = document.getElementById("revenueChart");
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: "bar",
            data: {
                labels: data.monthly.labels,
                datasets: [
                    {
                        label: "Cobros Reales",
                        data: data.monthly.payments,
                        backgroundColor: "rgba(40, 167, 69, 0.7)",
                        borderColor: "rgb(40, 167, 69)",
                        borderWidth: 1,
                    },
                    {
                        label: "Gastos",
                        data: data.monthly.expenses,
                        type: "line",
                        borderColor: "rgb(220, 53, 69)",
                        fill: false,
                        tension: 0.3,
                    },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    /** PROFIT CHART (COMPARATIVA ANUAL HISTÓRICA) **/
    const profitCtx = document.getElementById("profitChart");
    if (profitCtx) {
        new Chart(profitCtx, {
            type: "bar",
            data: {
                labels: data.yearly.labels, // Muestra 2022, 2023, 2024, etc.
                datasets: [
                    {
                        label: "Resultado Neto Anual",
                        data: data.yearly.profits,
                        backgroundColor: data.yearly.profits.map((v) =>
                            v >= 0
                                ? "rgba(40, 167, 69, 0.8)"
                                : "rgba(220, 53, 69, 0.8)",
                        ),
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { ticks: { callback: (v) => "$" + v.toLocaleString() } },
                },
            },
        });
    }
});
