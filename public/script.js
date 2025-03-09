function createGanttChart(canvasId, ganttData, xMax) {
    const ctx = document.getElementById(canvasId).getContext('2d');
  
    new Chart(ctx, {
      type: 'scatter',
      data: {
        datasets: [{
          label: 'Gantt Blocks',
          data: ganttData.map(block => ({
            x: (block.start + block.finish) / 2, // midpoint
            name: block.name,
            start: block.start,
            finish: block.finish
          })),
          pointRadius: 0,
          showLine: false
        }]
      },
      options: {
        responsive: false,
        scales: {
          x: {
            type: 'linear',
            position: 'bottom',
            title: { display: true, text: 'Time (seconds)' },
            min: 0,
            max: xMax * 1.05
          },
          y: {
            display: false,
            min: 0,
            max: 1
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                const raw = context.raw;
                const duration = (raw.finish - raw.start).toFixed(2);
                return `${raw.name} (${raw.start} - ${raw.finish}, ${duration}s)`;
              }
            }
          },
          legend: { display: false }
        }
      },
      plugins: [
        {
          id: 'floatingBar',
          afterDatasetsDraw(chart) {
            const { ctx, chartArea, scales: { x } } = chart;
            const dataset = chart.data.datasets[0].data;
  
            dataset.forEach(point => {
              const blockStart  = point.start;
              const blockFinish = point.finish;
              const blockName   = point.name;
  
              const xStart = x.getPixelForValue(blockStart);
              const xEnd   = x.getPixelForValue(blockFinish);
  
              const fullHeight = chartArea.bottom - chartArea.top;
              const barHeight  = fullHeight * 0.6;
              const yTop = chartArea.top + (fullHeight - barHeight) / 2;
  
              // Choose color or pattern
              if (blockName === 'Idle') {
                // hashed pattern
                const patternCanvas = document.createElement('canvas');
                patternCanvas.width = 6;
                patternCanvas.height = 6;
                const pctx = patternCanvas.getContext('2d');
                pctx.fillStyle = '#eee';
                pctx.fillRect(0,0,6,6);
                pctx.strokeStyle = '#666';
                pctx.beginPath();
                pctx.moveTo(0,0);
                pctx.lineTo(6,6);
                pctx.stroke();
                ctx.fillStyle = ctx.createPattern(patternCanvas, 'repeat');
              } else {
                ctx.fillStyle = 'rgba(54,162,235,0.6)';
              }
  
              const width = xEnd - xStart;
              ctx.fillRect(xStart, yTop, width, barHeight);
  
              // Outline
              ctx.strokeStyle = '#333';
              ctx.strokeRect(xStart, yTop, width, barHeight);
  
              // Text label if it fits
              ctx.fillStyle = '#000';
              ctx.font = '12px sans-serif';
              const text = blockName;
              const textWidth = ctx.measureText(text).width;
              const textX = xStart + (width - textWidth)/2;
              const textY = yTop + barHeight/2 + 4;
              if (textWidth < width) {
                ctx.fillText(text, textX, textY);
              }
            });
          }
        }
      ]
    });
  }
  