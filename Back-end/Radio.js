const radioLog = document.getElementById('radioLog');
if (radioLog) {
    setInterval(async () => {
        try {
            const res = await fetch('../Back-end/Radio.php?action=log');
            const data = await res.json();
            radioLog.innerHTML = '';
            data.forEach(msg => {
                const p = document.createElement('p');
                p.textContent = `📡 ${msg}`;
                radioLog.appendChild(p);
            });
        } catch (err) { console.error(err); }
    }, 3000);
}