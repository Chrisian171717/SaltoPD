function startScan() {
    fetch('../Back-end/FacialScan.php')
        .then(res => res.json())
        .then(data => {
            alert(`Resultado: ${data.status}\nNombre: ${data.nombre || 'Desconocido'}`);
        })
        .catch(err => console.error(err));
}

function stopScan() { alert('Escaneo detenido'); }
function resetScanner() { alert('Sistema reiniciado'); }
function exportData() { alert('Datos exportados correctamente'); }

async function updateStatus() {
  const res = await fetch("http://localhost:5000/status");
  const data = await res.json();

  document.getElementById("scanner-status").innerText = data.scanner_status;
  document.getElementById("accuracy").innerText = data.accuracy;
  document.getElementById("scan-time").innerText = data.scan_time;
  document.getElementById("landmarks").innerText = data.landmarks;
}

setInterval(updateStatus, 1000);
updateStatus();

function startScan() {
  fetch("/start", { method: "POST" })
    .then(res => res.json())
    .then(data => {
      document.getElementById("scanner-status").innerText = data.status;
      document.getElementById("accuracy").innerText = data.accuracy;
      document.getElementById("scan-time").innerText = data.scan_time;
    });
}

function stopScan() {
  fetch("/stop", { method: "POST" })
    .then(res => res.json())
    .then(data => {
      document.getElementById("scanner-status").innerText = data.status;
    });
}

function resetScanner() {
  fetch("/reset", { method: "POST" })
    .then(res => res.json())
    .then(data => {
      document.getElementById("scanner-status").innerText = data.status;
    });
}

function exportData() {
  fetch("/export", { method: "POST" })
    .then(res => res.json())
    .then(data => {
      alert(data.status);
    });
}