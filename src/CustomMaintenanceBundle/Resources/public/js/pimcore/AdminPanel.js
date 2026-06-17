/**
 * Created by thomas on 11.08.14.
 */
pimcore.registerNS("custommaintenance.AdminPanel");
custommaintenance.AdminPanel = Class.create({

    initialize: function () {
        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: "/admin/weblizards_custom_maintenance/adminpanel/load",
            success: function (response) {
                this.data = Ext.decode(response.responseText);
                this.getTabPanel();
            }.bind(this)
        });
    },

    getValue: function (key) {

        var nk = key.split("\.");
        var current = this.data.values;

        for (var i = 0; i < nk.length; i++) {
            if (current[nk[i]]) {
                current = current[nk[i]];
            } else {
                current = null;
                break;
            }
        }

        if (typeof current !== "object" && typeof current !== "function") {
            return current;
        }

        return "";
    },

    translateWithFallback: function (key, fallback) {
        var translated = t(key);

        return translated === key ? fallback : translated;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveTab("custommaintenance_adminpanel");
    },

    getTabPanel: function () {
        if (!this.panel) {

            this.panel = new Ext.Panel({
                id: "custommaintenance_adminpanel",
                title: t("Custom Maintenance Admin Panel"),
                iconCls: "custommaintenance_icon",
                border: false,
                layout: "fit",
                closable: true
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveTab("custommaintenance_adminpanel");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("custommaintenance_adminpanel");
            }.bind(this));



            this.layout = new Ext.form.Panel({
                title: "Custom Maintenance",
                bodyStyle: "padding: 10px;",
                autoScroll: true,
                items: [
                    {
                        xtype: "hidden",
                        name: "custom_tokens",
                        value: this.data["tokens"].join(",")
                    },
                    {
                        xtype: 'fieldset',
                        title: t('custommaintenance.Frontend'),
                        collapsible: true,
                        collapsed: false,
                        autoHeight: true,
                        defaults: {
                            labelWidth: 250
                        },
                        items: [
                            {
                                xtype: "textfield",
                                fieldLabel: t("custommaintenance.indication_upcoming"),
                                name: "frontend_indication_upcoming",
                                value: this.data["frontend"]["indication_upcoming"]["de"],
                                width: 800
                            },
                            {
                                xtype: "textfield",
                                fieldLabel: t("custommaintenance.indication_current"),
                                name: "frontend_indication_current",
                                value: this.data["frontend"]["indication_current"]["de"],
                                width: 800
                            },
                            {
                                xtype: "textfield",
                                fieldLabel: t("custommaintenance.frontend_more"),
                                name: "frontend_more",
                                value: this.data["frontend"]["more"]["de"],
                                width: 800
                            },
                            {
                                xtype: "textfield",
                                fieldLabel: t("custommaintenance.fulltimeformat"),
                                name: "frontend_fulltimeformat",
                                value: this.data["frontend"]["fulltimeformat"]["de"],
                                width: 800
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('custommaintenance.pimcore'),
                        collapsible: true,
                        collapsed: false,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 250
                        },
                        items: [
                            {
                                xtype: 'fieldset',
                                title: t("custommaintenance.timecontrol"),
                                collapsible: false,
                                autoHeight: true,
                                defaults: {
                                    labelWidth: 200
                                },
                                items: [
                                    {
                                        xtype: 'fieldcontainer',
                                        layout: 'hbox',
                                        fieldLabel: t("custommaintenance.datum_von"),
                                        combineErrors: true,
                                        name: 'pimcore_start_date',
                                        items: [
                                            this.createDateField("pimcore_from_date", this.data["pimcore"]["planned"]["from"]["date"]),
                                            this.createTimeField("pimcore_from_time", this.data["pimcore"]["planned"]["from"]["time"])
                                        ]
                                    },
                                    {
                                        xtype: 'fieldcontainer',
                                        layout: 'hbox',
                                        fieldLabel: t("custommaintenance.datum_bis"),
                                        combineErrors: true,
                                        name: 'pimcore_start_date',
                                        items: [
                                            this.createDateField("pimcore_to_date", this.data["pimcore"]["planned"]["to"]["date"]),
                                            this.createTimeField("pimcore_to_time", this.data["pimcore"]["planned"]["to"]["time"])
                                        ]
                                    }
                                ]
                            },
                            {
                                xtype: 'fieldset',
                                title: t("custommaintenance.display"),
                                collapsible: false,
                                autoHeight: true,
                                defaults: {
                                    labelWidth: 200
                                },
                                items: [
                                    {
                                        xtype: 'fieldcontainer',
                                        layout: 'hbox',
                                        fieldLabel: t("custommaintenance.hinweis_zeigen"),
                                        combineErrors: true,
                                        name: 'pimcore_show_info_container',
                                        items: [
                                            {
                                                xtype: "combo",
                                                name: "pimcore_show_info",
                                                value: this.data["pimcore"]["show_info"],
                                                store: [
                                                    ["always", t("custommaintenance.always")],
                                                    ["never", t("custommaintenance.never")],
                                                    ["automatic", t("custommaintenance.automatic")]
                                                ],
                                                listeners: {
                                                    select: this.showInfo.bind(this, "pimcore")
                                                },
                                                mode: "local",
                                                editable: false,
                                                forceSelection: true,
                                                triggerAction: "all"
                                            },
                                            this.createDateField("pimcore_show_info_from_date", this.data["pimcore"]["show_info_from"]["date"], this.data["pimcore"]["show_info"] !== "automatic"),
                                            this.createTimeField("pimcore_show_info_from_time", this.data["pimcore"]["show_info_from"]["time"], this.data["pimcore"]["show_info"] !== "automatic")
                                        ]
                                    },
                                    {
                                        xtype: "textfield",
                                        fieldLabel: t("custommaintenance.info_document"),
                                        name: "pimcore_document",
                                        value: this.data["pimcore"]["document"],
                                        width: 800,
                                        cls: "input_drop_target",
                                        listeners: {
                                            "render": function (el) {
                                                new Ext.dd.DropZone(el.getEl(), {
                                                    reference: this,
                                                    ddGroup: "element",
                                                    getTargetFromEvent: function (/* e */) {
                                                        return this.getEl();
                                                    }.bind(el),

                                                    onNodeOver: function (/* target, dd, e, data */) {
                                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                                    },

                                                    onNodeDrop: function (target, dd, e, data) {
                                                        var record = data.records[0];
                                                        var dropped_data = record.data;

                                                        if (dropped_data.elementType === "document") {
                                                            this.setValue(dropped_data.path);
                                                            return true;
                                                        }
                                                        return false;
                                                    }.bind(el)
                                                });
                                            }
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                ],
                buttons: [
                    {
                        text: t("custommaintenance_adminpanel_add"),
                        handler: this.addCustomMaintenance.bind(this),
                        iconCls: "pimcore_icon_add"
                    },
                    {
                        text: t("custommaintenance_adminpanel_save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ]
            });

            for (var i=0; i<this.data["tokens"].length; i++) {
                var token = this.data["tokens"][i];
                var config = this.data["custom"][token];
                this.layout.add(this.createCustomFieldset(token, config, false));
            }



            this.panel.add(this.layout);
            tabPanel.setActiveTab("custommaintenance_adminpanel");
            pimcore.layout.refresh();
        }

        return this.panel;
    },

    showInfo: function(token) {
        var form = this.layout.getForm();
        var disabled = form.findField(token + "_show_info").getValue() !== "automatic";
        form.findField(token + "_show_info_from_date").setHidden(disabled);
        form.findField(token + "_show_info_from_time").setHidden(disabled);
    },

    addCustomMaintenance: function () {
        Ext.Msg.prompt(
            this.translateWithFallback("custommaintenance_adminpanel_add", "Neue Maintenance-Art"),
            this.translateWithFallback("custommaintenance.token", "Technischer Token"),
            function (button, value) {
                var token = (value || "").replace(/^\s+|\s+$/g, "");

                if (button !== "ok") {
                    return;
                }

                if (!token.match(/^[A-Za-z0-9]+$/)) {
                    pimcore.helpers.showNotification(
                        t("error"),
                        this.translateWithFallback("custommaintenance.token_invalid", "Token darf nur alphanumerische Zeichen enthalten"),
                        "error"
                    );
                    return;
                }

                if (token === "pimcore") {
                    pimcore.helpers.showNotification(
                        t("error"),
                        this.translateWithFallback("custommaintenance.token_reserved", "Token pimcore ist reserviert"),
                        "error"
                    );
                    return;
                }

                if (this.data["tokens"].indexOf(token) !== -1) {
                    pimcore.helpers.showNotification(
                        t("error"),
                        this.translateWithFallback("custommaintenance.token_exists", "Token existiert bereits"),
                        "error"
                    );
                    return;
                }

                this.data["tokens"].push(token);
                this.data["custom"][token] = this.buildNewCustomConfig();
                this.layout.add(this.createCustomFieldset(token, this.data["custom"][token], true));
                this.updateCustomTokensField();
                this.layout.doLayout();
                pimcore.layout.refresh();
            }.bind(this)
        );
    },

    save: function () {
        var values = this.layout.getForm().getFieldValues();

        Ext.Ajax.request({
            url: "/admin/weblizards_custom_maintenance/adminpanel/save",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                try {
                    var res = Ext.decode(response.responseText);
                    pimcore.helpers.showNotification(res.title, res.message, res.type);
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("custommaintenance_adminpanel_save_error"), "error");
                }
            }
        });
    },

    buildNewCustomConfig: function () {
        return {
            active: "false",
            fixed: "false",
            description: "",
            show_info: "never",
            show_info_from: {
                date: "",
                time: ""
            },
            planned: {
                from: {
                    date: "",
                    time: ""
                },
                to: {
                    date: "",
                    time: ""
                }
            },
            document: ""
        };
    },

    updateCustomTokensField: function () {
        var field = this.layout.getForm().findField("custom_tokens");
        if (field) {
            field.setValue(this.data["tokens"].join(","));
        }
    },

    createCustomFieldset: function (token, config, isNewEntry) {
        var items = [];
        var descriptionValue = config["description"] ? config["description"] : "";
        var title = descriptionValue ? descriptionValue : token;
        var fieldset;

        if (isNewEntry) {
            items.push({
                xtype: "displayfield",
                fieldLabel: this.translateWithFallback("custommaintenance.token", "Technischer Token"),
                value: token
            });
            items.push({
                xtype: "textfield",
                fieldLabel: this.translateWithFallback("custommaintenance.description", "Beschreibung"),
                name: token + "_description",
                value: descriptionValue,
                width: 425,
                listeners: {
                    change: function (field, newValue) {
                        fieldset.setTitle(newValue ? newValue : token);
                    }
                }
            });
        } else {
            items.push({
                xtype: "hidden",
                name: token + "_description",
                value: descriptionValue
            });
        }

        items.push({
            fieldLabel: t("custommaintenance.active"),
            xtype: "combo",
            width: 425,
            name: token + "_active",
            value: config["active"] ? config["active"] : "false",
            store: [
                ["false", t("custommaintenance.inactive")],
                ["true", t("custommaintenance.active")]
            ],
            mode: "local",
            editable: false,
            forceSelection: true,
            triggerAction: "all"
        });
        items.push({
            xtype: 'fieldcontainer',
            fieldLabel: t("custommaintenance.fixed"),
            layout: 'hbox',
            items: [
                {
                    xtype: "combo",
                    width: 325,
                    name: token + "_fixed",
                    value: config["fixed"] ? config["fixed"] : "false",
                    store: [
                        ["true", t("custommaintenance.is_fixed")],
                        ["false", t("custommaintenance.is_not_fixed")]
                    ],
                    mode: "local",
                    editable: false,
                    forceSelection: true,
                    triggerAction: "all"
                },
                {
                    xtype: 'displayfield',
                    value: t('custommaintenance.fixed.helptext'),
                    style: {
                        marginLeft : "20px"
                    }
                }
            ]
        });
        items.push({
            xtype: 'fieldset',
            title: t("custommaintenance.timecontrol"),
            collapsible: false,
            autoHeight: true,
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    fieldLabel: t("custommaintenance.datum_von"),
                    combineErrors: true,
                    name: token + '_start_date',
                    items: [
                        this.createDateField(token + "_from_date", config["planned"]["from"]["date"]),
                        this.createTimeField(token + "_from_time", config["planned"]["from"]["time"])
                    ]
                },
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    fieldLabel: t("custommaintenance.datum_bis"),
                    combineErrors: true,
                    name: token + '_start_date',
                    items: [
                        this.createDateField(token + "_to_date", config["planned"]["to"]["date"]),
                        this.createTimeField(token + "_to_time", config["planned"]["to"]["time"])
                    ]
                }
            ]
        });
        items.push({
            xtype: 'fieldset',
            title: t("custommaintenance.display"),
            collapsible: false,
            autoHeight: true,
            defaults: {
                labelWidth: 200
            },
            items: [
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    fieldLabel: t("custommaintenance.hinweis_zeigen"),
                    combineErrors: true,
                    name: token + '_show_info_container',
                    items: [
                        {
                            xtype: "combo",
                            name: token + "_show_info",
                            value: config["show_info"] ? config["show_info"] : "never",
                            store: [
                                ["always", t("custommaintenance.always")],
                                ["never", t("custommaintenance.never")],
                                ["automatic", t("custommaintenance.automatic")]
                            ],
                            listeners: {
                                select: this.showInfo.bind(this, token)
                            },
                            mode: "local",
                            editable: false,
                            forceSelection: true,
                            triggerAction: "all"
                        },
                        this.createDateField(token + "_show_info_from_date", config["show_info_from"]["date"], config["show_info"] !== "automatic"),
                        this.createTimeField(token + "_show_info_from_time", config["show_info_from"]["time"], config["show_info"] !== "automatic")
                    ]
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("custommaintenance.info_document"),
                    name: token + "_document",
                    value: config["document"],
                    width: 800,
                    cls: "input_drop_target",
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function (/* e */) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver: function (/* target, dd, e, data */) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop: function (target, dd, e, data) {
                                    var record = data.records[0];
                                    var dropped_data = record.data;

                                    if (dropped_data.elementType === "document") {
                                        this.setValue(dropped_data.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                }
            ]
        });

        fieldset = new Ext.form.FieldSet({
            xtype: 'fieldset',
            title: title,
            collapsible: true,
            collapsed: false,
            autoHeight:true,
            defaults: {
                labelWidth: 200
            },
            items: items
        });

        return fieldset;
    },

    createDateField: function(name, value, hidden) {
        if (typeof hidden === "undefined") { hidden = false; }
        return new Ext.form.field.Date({
            id: name,
            name: name,
            width: 130,
            xtype: 'datefield',
            format: "d.m.Y",
            value: value,
            hidden: hidden
        });
    },

    createTimeField: function(name, value, hidden) {
        if (typeof hidden === "undefined") { hidden = false; }
        return new Ext.form.field.Time({
            id: name,
            name: name,
            width: 100,
            xtype: 'timefield',
            value: value,
            format: "H:i",
            hidden: hidden
        });
    }

});
