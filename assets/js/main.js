function initMap() {
    // Współrzędne dla lokalizacji pinezki (np. Warszawa)
    var location = {lat: 52.884751, lng: 16.979308};
    
    // Utwórz mapę skoncentrowaną na wybranej lokalizacji
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 10,
        center: location
    });

    // Dodaj pinezkę na mapie
    var marker = new google.maps.Marker({
        position: location,
        map: map
    });
}
