function initMap() {
    const mapContainer = document.querySelector('.mapa-contenedor');
    if (!mapContainer) return;

    const center = { lat: -31.3833, lng: -57.9667 };
    const map = new google.maps.Map(mapContainer, {
        zoom: 14,
        center: center
    });

    fetch('../Back-end/Map.php?action=list')
        .then(res => res.json())
        .then(markersData => {
            markersData.forEach(data => {
                const marker = new google.maps.Marker({
                    position: data.position,
                    map: map,
                    title: data.title
                });
                const info = new google.maps.InfoWindow({ content: `<strong>${data.title}</strong>` });
                marker.addListener('click', () => info.open(map, marker));
            });
        })
        .catch(err => console.error(err));
}
