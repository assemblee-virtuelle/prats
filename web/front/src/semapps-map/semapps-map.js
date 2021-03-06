Polymer({
    is: 'semapps-map',

    properties: {
        route: {
            type: Object,
            observer: '_routeChanged'
        },
        pins: {
            type: Array,
            value: []
        }
    },

    // Wait all HTML to be loaded.
    attached() {
        this.ready = false;
        // Global ref.
        semapps.map = this;

        // Wait for buildings to be loaded.
        SemAppsCarto.ready(this.start.bind(this));
    },

    start() {
        "use strict";
        this.globalX = 42.403681; //48.862725;
        this.globalY = 2.47986190000006; //2.287592000000018;
        this.globalZoom = 10;
        let maxZoom = (semapps.isAnonymous())? 12:18;
        let minZoom = 0;
        $('#semapps-map').css({ 'height': $(window).height() });
        $(window).on('resize', function() {
            $('#semapps-map').css({ 'height': $(window).height() });
            $('body').css({ 'width': $(window).width() })
        });
        $('.smoothscroll').on('click',function (e) {
            e.preventDefault();

            var target = this.hash,
                $target = $(target);

            $('html, body').stop().animate({
                'scrollTop': $target.offset().top
            }, 800, 'swing', function () {
                window.location.hash = target;
            });
        });
        this.OSM = L.map('semapps',{maxZoom: maxZoom, minZoom:minZoom}).setView([this.globalX,this.globalY], this.globalZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.OSM);
        this.markers = L.markerClusterGroup();
        this.OSM.addLayer(this.markers);
        this.OSM.scrollWheelZoom.disable();
        this.OSM.off('dblclick');
        this.OSM.on('dblclick', onMapClick);
        this.OSM.on('mouseover',mouseOver);
        this.OSM.on('mouseout',mouseOut);
        this.pinAvailaible = [];
        this.awesome= {
            "http://virtual-assembly.org/pair#Person":L.AwesomeMarkers.icon({
                icon: 'user',
                markerColor: 'blue'
            }),
            "http://virtual-assembly.org/pair#Organization":L.AwesomeMarkers.icon({
                icon: 'tower',
                markerColor: 'blue'
            }),
            "http://virtual-assembly.org/pair#Project":L.AwesomeMarkers.icon({
                icon: 'screenshot',
                markerColor: 'red'
            }),
            "http://virtual-assembly.org/pair#Event":L.AwesomeMarkers.icon({
                icon: 'calendar',
                markerColor: 'orange'
            }),
            "http://virtual-assembly.org/pair#Proposal":L.AwesomeMarkers.icon({
                icon: 'info-sign',
                markerColor: 'green'
            }),
            "http://virtual-assembly.org/pair#Document":L.AwesomeMarkers.icon({
                icon: 'folder-open',
                markerColor: 'black'
            }),
        }
        this.domHost.setOSM(this.OSM);
    },

    getOSM(){
        return this.properties.OSM;
    },

    getZoom(){
        return this.OSM.getZoom();
    },

    /**
     * affiche tous les points enregistré
     */
    pinShowAll() {
        "use strict";
        for (let key in this.pins) {
            if (this.pins.hasOwnProperty(key)) {
                this.pinShow(key);
            }
        }
    },

    pinShow(key){
        "use strict";
        if (this.pins.hasOwnProperty(key) && this.pinAvailaible[key]) {
            this.markers.addLayer(this.pins[key]);
            this.pinAvailaible[key] = false;
        }

        //this.pins[key].addTo(this.OSM);
    },


    addPin(latitude,longitude, key, text,type) {
        "use strict";
        this.pinAvailaible[key] = true;
        if (this.awesome[type] !== undefined) {
            this.pins[key] = L.marker([latitude,longitude],  {icon: this.awesome[type]})
                .bindPopup( '<a href="#" onclick="getDetail(this)" rel="'+key+'"><h5>'+text+'</h5> </a>');
        }else{
            this.pins[key] = L.marker([latitude,longitude])
                .bindPopup('<a href="#" onclick="getDetail(this)" rel="'+key+'"><h5>'+text+' </h5></a>');
        }

        this.pinShow(key);
    },
    /**
     * affiche un point et efface les autres
     * @param key -- la clé qui va être la seule affiché
     */
    pinShowOne(key) {
        "use strict";
        this.pinHideAll();
        this.pinShow(key);
        this.OSM.setView(this.pins[key].getLatLng(),15,{animate: true});
    },

    /**
     * efface un point
     * @param key -- la clé du point a ne plus afficher
     */
    pinHide(key) {
        "use strict";
        if(this.pins[key] !== undefined && !this.pinAvailaible[key]){
            let marker = this.pins[key];
            this.markers.removeLayer(marker);
            this.pinAvailaible[key] = true;
        }

    },

    /**
     * efface tous les points
     */
    pinHideAll() {
        "use strict";
        for (let key in this.pins){
            if (this.pins.hasOwnProperty(key)) {
                this.pinHide(key);
            }
        }

    },

    zoomGlobal(){
        this.OSM.setView([this.globalX,this.globalY], this.globalZoom);
    },

    handleClick(e) {
        e.preventDefault();
        semapps.scrollToContent();
        semapps.myRoute = "detail";
        semapps.goToPath('detail', {
            uri: window.encodeURIComponent(this.uri)
        });
    },


});

function onMapClick(e) {
    if (semapps.map.OSM.scrollWheelZoom.enabled()) {
        semapps.map.OSM.scrollWheelZoom.disable();
    }
    else {
        semapps.map.OSM.scrollWheelZoom.enable();
    }
    mouseOver();
}
function mouseOver(e) {
    let element = document.getElementById('semapps-map-black');
    if(!semapps.map.OSM.scrollWheelZoom.enabled()){
        $('#semapps-map-black').animate({ height: "100%"},'fast','linear');
        element.style.display = 'block';
        $('#semapps-map-message').show()
    }
    else{
        $('#semapps-map-black').animate({ height: "0px"},'fast','linear');
        $('#semapps-map-message').hide()
    }
}

function mouseOut(e){
    $('#semapps-map-message').hide();
    $('#semapps-map-black').animate({ height: "0px"},'fast','linear')
}
function getDetail(elem) {
    semapps.goToPath('detail', {
        uri: window.encodeURIComponent(elem.rel)
    });
}
