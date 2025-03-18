function createGanttChart(canvasId, ganttData, xMax) {
  const ctx = document.getElementById(canvasId).getContext('2d');

  new Chart(ctx, {
      type: 'scatter',
      data: {
          datasets: [{
              label: 'Process',
              data: ganttData.map(block => ({
                  x: (block.start + block.finish) / 2,
                  name: block.name,
                  start: block.start,
                  finish: block.finish
              })),
              pointRadius: 0,
              showLine: true
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: { display: true, text: 'Time (seconds)' },
                min: 0,
                max: xMax,
                ticks: {display: false },
                grid: { display: false }
            },
            y: {
                display: false
            }
        }, 
        plugins: {
          legend: {
              display: true, // Ensure the legend is visible
              labels: {
                  generateLabels: function (chart) {
                      const labels = chart.data.datasets.map((dataset, index) => {
                          return {
                              text: dataset.label, // Label for each dataset
                              fillStyle: dataset.backgroundColor, // Use dataset color
                              hidden: !chart.isDatasetVisible(index),
                              lineCap: 'round',
                              strokeStyle: dataset.borderColor,
                          };
                      });
      
                      // Add Idle time label
                      labels.push({
                          text: "Idle Time",
                          fillStyle: "rgba(200, 200, 200, 0.5)", // Example gray color
                          strokeStyle: "rgb(78, 78, 78)", // Border color
                          hidden: false,
                      });
      
                      return labels;
                  }
              }
          }
      }
    },
      plugins: [
          {
              id: 'floatingBar',
              afterDatasetsDraw(chart) {
                  const { ctx, chartArea, scales: { x } } = chart;
                  const dataset = chart.data.datasets[0].data;

                  dataset.forEach(point => {
                      const blockStart = point.start;
                      const blockFinish = point.finish;
                      const blockName = point.name;

                      const xStart = x.getPixelForValue(blockStart);
                      const xEnd = x.getPixelForValue(blockFinish);

                      const fullHeight = chartArea.bottom - chartArea.top;
                      const barHeight = fullHeight * 0.6;
                      const yTop = chartArea.top + (fullHeight - barHeight) / 2;

                      if (blockName === 'Idle') {
                          // Hashed pattern for Idle blocks
                          const patternCanvas = document.createElement('canvas');
                          patternCanvas.width = 6;
                          patternCanvas.height = 6;
                          const pctx = patternCanvas.getContext('2d');
                          pctx.fillStyle = '#eee';
                          pctx.fillRect(0, 0, 6, 6);
                          pctx.strokeStyle = '#666';
                          pctx.beginPath();
                          pctx.moveTo(0, 0);
                          pctx.lineTo(6, 6);
                          pctx.stroke();
                          ctx.fillStyle = ctx.createPattern(patternCanvas, 'repeat');
                      } else {
                          ctx.fillStyle = 'rgba(54,162,235,0.6)';
                      }

                      const width = xEnd - xStart;
                      ctx.fillRect(xStart, yTop, width, barHeight);

                      ctx.strokeStyle = '#333';
                      ctx.strokeRect(xStart, yTop, width, barHeight);

                      ctx.fillStyle = '#000';
                      ctx.font = '12px sans-serif';
                      const text = blockName;
                      const textWidth = ctx.measureText(text).width;
                      const textX = xStart + (width - textWidth) / 2;
                      const textY = yTop + barHeight / 2 + 4;
                      if (textWidth < width) {
                          ctx.fillText(text, textX, textY);
                      }

                      // Draw time labels below each process
                      ctx.fillStyle = '#000';
                      ctx.font = '10px sans-serif';
                      ctx.fillText(blockStart.toFixed(0), xStart, chartArea.bottom + 15);
                      ctx.fillText(blockFinish.toFixed(0), xEnd, chartArea.bottom + 15);
                  });
              }
          }
      ]
  });
}
