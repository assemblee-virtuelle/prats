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

        this.domHost.axoReady();
    }


});