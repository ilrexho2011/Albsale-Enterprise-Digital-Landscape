sap.ui.define([
    "sap/ui/core/UIComponent",
    "sap/ui/Device",
    "teamprojekt/model/models",
    "sap/ui/model/json/JSONModel"
], function (UIComponent, Device, models, JSONModel) {
    "use strict";

    return UIComponent.extend("teamprojekt.Component", {
        metadata: {
            manifest: "json"
        },

        init: function () {
            // call the base component's init function
            UIComponent.prototype.init.apply(this, arguments);

            // enable routing
            this.getRouter().initialize();

            // set the device model
            this.setModel(models.createDeviceModel(), "device");

            // set the data model
            var oModel = new JSONModel();
            var sServiceUrl = "/api/salt";

            // Load data from the backend
            oModel.loadData(sServiceUrl, null, true, "GET", false, false, {
                "Content-Type": "application/json"
            });

            // Attach request completed handler
            oModel.attachRequestCompleted(function(oEvent) {
                if (oEvent.getParameter("success")) {
                    this.setModel(oModel, "saltData");
                } else {
                    console.error("Failed to load data from " + sServiceUrl);
                }
            }.bind(this));

            // Attach request failed handler
            oModel.attachRequestFailed(function() {
                console.error("Failed to load data from " + sServiceUrl);
            });
        }
    });
});