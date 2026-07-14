pimcore.registerNS("pimcore.plugin.WeblizardsCustomMaintenanceBundle");

pimcore.plugin.WeblizardsCustomMaintenanceBundle = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        var menu = e.detail.menu;

        if (!menu.extras || !menu.extras.items) {
            return;
        }

        menu.extras.items.push({
            itemId: "pimcore_menu_extras_custommaintenance",
            text: t('Custom Maintenance'),
            iconCls: "custommaintenance_icon",
            priority: 42,
            handler: this.openAdminPanel.bind(this)
        });
    },

    openAdminPanel: function () {
        try {
            pimcore.globalmanager.get("custommaintenance_adminpanel").activate();
        } catch (e) {
            pimcore.globalmanager.add("custommaintenance_adminpanel", new custommaintenance.AdminPanel());
        }
    }
});

var WeblizardsCustomMaintenanceBundlePlugin = new pimcore.plugin.WeblizardsCustomMaintenanceBundle();
