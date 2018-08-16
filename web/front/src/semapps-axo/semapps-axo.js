Polymer({
    is: 'semapps-axo',

    // Wait all HTML to be loaded.
    attached() {
        
        $('#semapps-axo').css({ 'height': $(window).height() });
        $(window).on('resize', function() {
            $('#semapps-axo').css({ 'height': $(window).height() });
            $('body').css({ 'width': $(window).width() })
        });
        SemAppsCarto.ready(this.start.bind(this));
    },

    start(){

        //Lance la methode axoReady du map handler TODO: a transformer en observer
        this.domHost.axoReady();

        let zones = this.querySelectorAll('path');

        zones.forEach(element => {
            element.addEventListener('click', this.zoneClickHandler)
        });
    },

    zoneClickHandler(e){
        console.log(e.target.id);
    }


});