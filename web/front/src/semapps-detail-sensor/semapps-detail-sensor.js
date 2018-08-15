Polymer({
    is: 'semapps-detail-sensor',
    properties: {},

    handleClickDetail(e) {
        e.preventDefault();
        semapps.goToPath('detail', {
            uri: window.encodeURIComponent(e.currentTarget.getAttribute('rel'))
        });
    },

    attached() {
        SemAppsCarto.ready(() => {
            semapps.initElementGlobals(this);
        });
        // Raw values.

        $.extend(this, this.data);
        console.log('this.data :', this.data);
    },
});