Polymer({
  is: 'semapps-ressource',
  properties: {
      person: String,
      queryParams: {
          observer: '_queryChanged'
      }
  },
    _queryChanged: function (data) {
        // We are on the search mode.
        "use strict";
        if (data && data.uri && data.person) {
            this.person = data.person;
            // Wait main object to be ready.
            SemAppsCarto.ready(() => {
                this.ressourceLoad(data.uri,data.person);
            });
        }
    },
  attached() {
    "use strict";
    SemAppsCarto.ready(() => {
          semapps.initElementGlobals(this);
      });
  },
    handleBack (e) {
        "use strict";
        e.preventDefault();
        semapps.goSearch();
    },
   ressourceLoad (encodedUri,encodedUriPerson) {
        "use strict";
       if( semapps.myRoute === "ressource"){
           // Show spinner.
           this.loading = true;
           // Hide content.
           this.$.ressource.style.display = 'none';
            // Request server.
            semapps.ajax('webservice/ressource?uri=' + encodedUri+'&person='+encodedUriPerson, (data) => {
                "use strict";
                // Check that we are on the last callback expected.
                this.ressourceLoadComplete(data)
            });
        }
    },
   ressourceLoadComplete (data) {
       "use strict";
        // Show detail content.
        this.$.ressource.style.display = '';
        data = data.responseJSON.ressource || {};
        this.ressourcesNeeded = data.ressourcesNeeded;
        this.ressourcesProposed = data.ressourcesProposed;
        this.hasSubject = data.hasSubject;
        this.title = data.name;
        this.detail = data.detail;
        this.loading = false;
    },
    handleClickDetail(e) {
        e.preventDefault();
        semapps.goToPath('detail', {
            uri: window.encodeURIComponent(e.currentTarget.getAttribute('rel'))
        });
    },

});
