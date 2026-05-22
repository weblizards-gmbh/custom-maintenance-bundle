pimcore.registerNS("pimcore.plugin.WeblizardsCustomMaintenanceBundle");

pimcore.plugin.WeblizardsCustomMaintenanceBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.WeblizardsCustomMaintenanceBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        var custommaintenance_action = new Ext.Action({
            id:"extras_custommaintenance_button",
            text: t('Custom Maintenance'),
            iconCls: "custommaintenance_icon",
            handler: function(){
                try {
                    pimcore.globalmanager.get("custommaintenance_adminpanel").activate();
                } catch (e) {
                    pimcore.globalmanager.add("custommaintenance_adminpanel", new custommaintenance.AdminPanel());
                }

            }
        });
        toolbar.extrasMenu.add(custommaintenance_action);
    }
});

var WeblizardsCustomMaintenanceBundlePlugin = new pimcore.plugin.WeblizardsCustomMaintenanceBundle();
