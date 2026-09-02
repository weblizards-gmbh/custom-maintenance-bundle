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
                        title: t('custommaintenance.frontend'),
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
                        xtype: 'fieldset',
                        title: this.translateWithFallback("custommaintenance.hard_fallback", "Harte Fallback-Seiten"),
                        collapsible: true,
                        collapsed: false,
                        autoHeight: true,
                        defaults: {
                            labelWidth: 250
                        },
                        items: [
                            {
                                xtype: "displayfield",
                                value: this.translateWithFallback(
                                    "custommaintenance.hard_fallback_help",
                                    "Waehlen Sie je ein Pimcore-Document als redaktionelle Quelle fuer die harte Maintenance-Seite und die harte Fehlerseite. Die automatische Erzeugung der statischen Artefakte folgt in einem spaeteren Schritt."
                                ),
                                cls: "x-form-display-field"
                            },
                            this.createDocumentSelectorField(
                                "hard_fallback_maintenance_document",
                                this.translateWithFallback("custommaintenance.hard_fallback_maintenance_document", "Maintenance-Quell-Document"),
                                this.data["hard_fallback"] ? this.data["hard_fallback"]["maintenance_document"] : null
                            ),
                            this.createDocumentSelectorField(
                                "hard_fallback_error_document",
                                this.translateWithFallback("custommaintenance.hard_fallback_error_document", "Fehler-Quell-Document"),
                                this.data["hard_fallback"] ? this.data["hard_fallback"]["error_document"] : null
                            ),
                            {
                                xtype: "displayfield",
                                value: this.translateWithFallback(
                                    "custommaintenance.hard_fallback_template_hint",
                                    "Beispiel-Templates liegen im Bundle unter Resources/views/fallback/. Die zugehoerigen Pimcore-Documents werden bewusst nicht automatisch angelegt."
                                ),
                                cls: "x-form-display-field"
                            }
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        title: this.translateWithFallback("custommaintenance.diagnosis", "Diagnose"),
                        collapsible: true,
                        collapsed: false,
                        autoHeight: true,
                        defaults: {
                            labelWidth: 250
                        },
                        items: [
                            {
                                xtype: "displayfield",
                                value: this.translateWithFallback(
                                    "custommaintenance.diagnosis_help",
                                    "Simuliert die aktuelle Konfiguration zu einem frei wählbaren Zeitpunkt, ohne etwas zu speichern."
                                ),
                                cls: "x-form-display-field"
                            },
                            {
                                xtype: 'fieldcontainer',
                                layout: 'hbox',
                                fieldLabel: this.translateWithFallback("custommaintenance.diagnosis_reference", "Simulationszeitpunkt"),
                                combineErrors: true,
                                name: 'diagnosis_reference_container',
                                items: [
                                    this.createDateField("diagnosis_reference_date", new Date()),
                                    this.createTimeField("diagnosis_reference_time", new Date())
                                ]
                            },
                            {
                                xtype: "button",
                                text: this.translateWithFallback("custommaintenance.diagnosis_run", "Diagnose aktualisieren"),
                                handler: this.runDiagnosis.bind(this),
                                iconCls: "pimcore_icon_search"
                            },
                            {
                                xtype: "box",
                                id: "custommaintenance_diagnosis_result",
                                autoEl: {
                                    tag: "div",
                                    html: ""
                                },
                                style: {
                                    marginTop: "12px"
                                }
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: this.translateWithFallback(
                            "custommaintenance.pimcore_protected_title",
                            "Pimcore (geschuetzter Sondereintrag)"
                        ),
                        collapsible: true,
                        collapsed: false,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 250
                        },
                        items: [
                            {
                                xtype: "displayfield",
                                value: this.translateWithFallback(
                                    "custommaintenance.pimcore_protected_notice",
                                    "Dieser native Pimcore-Eintrag ist permanent geschuetzt und kann nicht geloescht werden."
                                ),
                                cls: "x-form-display-field"
                            },
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
            this.runDiagnosis();
        }

        return this.panel;
    },

    showInfo: function(token) {
        var form = this.layout.getForm();
        var disabled = form.findField(token + "_show_info").getValue() !== "automatic";
        form.findField(token + "_show_info_from_date").setHidden(disabled);
        form.findField(token + "_show_info_from_time").setHidden(disabled);
    },

    applyMaintenanceMode: function(token) {
        var form = this.layout.getForm();
        var modeField = form.findField(token + "_maintenance_mode");
        var timeControlFieldset = Ext.getCmp(token + "_timecontrol_fieldset");
        var scheduled = modeField && modeField.getValue() === "scheduled";

        if (timeControlFieldset) {
            timeControlFieldset.setVisible(scheduled);
            timeControlFieldset.setDisabled(!scheduled);
        }

        this.refreshFormLayout();
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
                this.refreshFormLayout();
                pimcore.layout.refresh();
            }.bind(this)
        );
    },

    save: function () {
        var values = this.getFormValues();

        if (!this.validateBeforeSave(values)) {
            return;
        }

        this.persistAdminValues(values);
    },

    getFormValues: function (overrides) {
        var form = this.layout.getForm();
        var values = form.getFieldValues();
        var fields = form.getFields().items;
        var i;

        for (i = 0; i < fields.length; i++) {
            this.normalizeDateLikeFieldValue(fields[i], values);
        }

        if (!overrides) {
            return values;
        }

        return Ext.apply(values, overrides);
    },

    normalizeDateLikeFieldValue: function (field, values) {
        var value;

        if (!field || !field.getName || !field.getName()) {
            return;
        }

        if (field.isXType && field.isXType("datefield")) {
            value = field.getValue();
            values[field.getName()] = value instanceof Date ? Ext.Date.format(value, "Y-m-d") : values[field.getName()];
            return;
        }

        if (field.isXType && field.isXType("timefield")) {
            value = field.getValue();
            values[field.getName()] = value instanceof Date ? Ext.Date.format(value, "H:i") : values[field.getName()];
        }
    },

    refreshFormLayout: function () {
        if (!this.layout) {
            return;
        }

        if (typeof this.layout.updateLayout === "function") {
            this.layout.updateLayout();
            return;
        }

        if (typeof this.layout.doLayout === "function") {
            this.layout.doLayout();
        }
    },

    persistAdminValues: function (values, onSuccess) {
        Ext.Ajax.request({
            url: "/admin/weblizards_custom_maintenance/adminpanel/save",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                var res;

                try {
                    res = Ext.decode(response.responseText);
                } catch(e) {
                    pimcore.helpers.showNotification(t("error"), t("custommaintenance_adminpanel_save_error"), "error");
                    return;
                }

                pimcore.helpers.showNotification(
                    res.title ? res.title : t(res.success ? "success" : "error"),
                    res.message,
                    res.type ? res.type : (res.success ? "success" : "error")
                );

                if (res.success && onSuccess) {
                    onSuccess();
                }
            }
        });
    },

    runDiagnosis: function () {
        var values = this.getFormValues();

        if (!this.validateBeforeSave(values)) {
            return;
        }

        this.requestDiagnosis(values);
    },

    requestDiagnosis: function (values) {
        var resultBox = Ext.getCmp("custommaintenance_diagnosis_result");
        if (resultBox) {
            resultBox.update(this.renderDiagnosisLoading());
        }

        Ext.Ajax.request({
            url: "/admin/weblizards_custom_maintenance/adminpanel/diagnose",
            method: "post",
            params: {
                data: Ext.encode(values)
            },
            success: function (response) {
                var res;

                try {
                    res = Ext.decode(response.responseText);
                } catch (e) {
                    this.showValidationError(this.translateWithFallback(
                        "custommaintenance.diagnosis_error",
                        "Die Diagnose konnte nicht gelesen werden."
                    ));
                    return;
                }

                if (!res.success) {
                    this.showValidationError(res.message);
                    return;
                }

                this.updateDiagnosisResult(res.diagnosis);
            }.bind(this),
            failure: function () {
                this.showValidationError(this.translateWithFallback(
                    "custommaintenance.diagnosis_error",
                    "Die Diagnose konnte nicht gelesen werden."
                ));
            }.bind(this)
        });
    },

    updateDiagnosisResult: function (diagnosis) {
        var resultBox = Ext.getCmp("custommaintenance_diagnosis_result");
        if (!resultBox) {
            return;
        }

        resultBox.update(this.renderDiagnosisHtml(diagnosis));
        this.refreshFormLayout();
    },

    renderDiagnosisLoading: function () {
        return "<div><strong>" + this.escapeHtml(this.translateWithFallback(
            "custommaintenance.diagnosis_loading",
            "Diagnose wird berechnet ..."
        )) + "</strong></div>";
    },

    renderDiagnosisHtml: function (diagnosis) {
        var html = [];
        var i;

        html.push("<div class=\"custommaintenance-diagnosis\">");
        html.push("<p><strong>" + this.escapeHtml(this.translateWithFallback(
            "custommaintenance.diagnosis_reference_result",
            "Ausgewertet für"
        )) + ":</strong> " + this.escapeHtml(diagnosis["evaluated_at"] || "") + "</p>");
        html.push("<table class=\"custommaintenance-diagnosis-table\" style=\"width:100%; border-collapse:collapse;\">");
        html.push("<thead><tr>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.token", "Technischer Token")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.description", "Beschreibung")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.maintenance_mode", "Maintenance-Modus")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.diagnosis_effective_maintenance", "Maintenance effektiv")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.diagnosis_maintenance_reason", "Maintenance-Grund")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.diagnosis_effective_notice", "Hinweis effektiv")) + "</th>");
        html.push("<th style=\"text-align:left; border-bottom:1px solid #ccc; padding:6px;\">" + this.escapeHtml(this.translateWithFallback("custommaintenance.diagnosis_notice_reason", "Hinweis-Grund")) + "</th>");
        html.push("</tr></thead><tbody>");

        for (i = 0; i < diagnosis["entries"].length; i++) {
            html.push(this.renderDiagnosisRow(diagnosis["entries"][i]));
        }

        html.push("</tbody></table></div>");

        return html.join("");
    },

    renderDiagnosisRow: function (entry) {
        return [
            "<tr>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(entry["token"] || "") + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(entry["description"] || "") + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(this.translateMaintenanceMode(entry["maintenance"]["mode"])) + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(this.translateDiagnosisState(entry["maintenance"]["effective"])) + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(this.translateMaintenanceReason(entry["maintenance"]["reason"], entry["maintenance"])) + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(this.translateNoticeState(entry["notice"]["effective"])) + "</td>",
            "<td style=\"padding:6px; border-bottom:1px solid #eee; vertical-align:top;\">" + this.escapeHtml(this.translateNoticeReason(entry["notice"]["reason"], entry["notice"])) + "</td>",
            "</tr>"
        ].join("");
    },

    translateMaintenanceMode: function (mode) {
        switch (mode) {
            case "active":
                return this.translateWithFallback("custommaintenance.active", "Aktiv");
            case "scheduled":
                return this.translateWithFallback("custommaintenance.scheduled", "Zeitgesteuert");
            default:
                return this.translateWithFallback("custommaintenance.inactive", "Inaktiv");
        }
    },

    translateDiagnosisState: function (state) {
        switch (state) {
            case "active":
                return this.translateWithFallback("custommaintenance.active", "Aktiv");
            case "current":
                return this.translateWithFallback("custommaintenance.diagnosis_notice_current", "Current");
            case "upcoming":
                return this.translateWithFallback("custommaintenance.diagnosis_notice_upcoming", "Upcoming");
            default:
                return this.translateWithFallback("custommaintenance.inactive", "Inaktiv");
        }
    },

    translateMaintenanceReason: function (reason, maintenance) {
        switch (reason) {
            case "temporary_activation":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_temporary_activation", "Temporärer Aktiv-Override");
            case "manual_activation":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_manual_activation", "Manuell aktiviert");
            case "scheduled_window":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_scheduled_window", "Zeitfenster ist aktiv");
            default:
                if (maintenance["scheduled_from"]) {
                    return this.translateWithFallback("custommaintenance.diagnosis_reason_inactive_scheduled", "Zeitfenster derzeit nicht aktiv");
                }

                return this.translateWithFallback("custommaintenance.diagnosis_reason_inactive", "Keine aktive Maintenance");
        }
    },

    translateNoticeState: function (state) {
        switch (state) {
            case "current":
                return this.translateWithFallback("custommaintenance.diagnosis_notice_current", "Aktiver Hinweis");
            case "upcoming":
                return this.translateWithFallback("custommaintenance.diagnosis_notice_upcoming", "Geplanter Hinweis");
            case "visible":
                return this.translateWithFallback("custommaintenance.diagnosis_notice_visible", "Sichtbarer Hinweis");
            default:
                return this.translateWithFallback("custommaintenance.diagnosis_notice_inactive", "Kein Hinweis");
        }
    },

    translateNoticeReason: function (reason) {
        switch (reason) {
            case "active_maintenance":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_active_maintenance", "Aktive Maintenance schlägt die Hinweis-Planung");
            case "always":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_always", "Hinweis ist dauerhaft aktiviert");
            case "notice_schedule":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_notice_schedule", "Hinweis-Zeitplanung ist erreicht");
            case "notice_window_not_started":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_notice_not_started", "Hinweis-Zeitplanung hat noch nicht begonnen");
            case "maintenance_not_upcoming":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_notice_not_upcoming", "Keine künftige Maintenance für einen Upcoming-Hinweis");
            case "notice_unconfigured":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_notice_unconfigured", "Hinweis-Zeitplanung ist unvollständig");
            case "never":
                return this.translateWithFallback("custommaintenance.diagnosis_reason_never", "Hinweis ist deaktiviert");
            default:
                return this.translateWithFallback("custommaintenance.diagnosis_reason_inactive", "Kein sichtbarer Hinweis");
        }
    },

    escapeHtml: function (value) {
        return String(value === null || typeof value === "undefined" ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    },

    validateBeforeSave: function(values) {
        var validationError = this.validatePimcoreTiming(values);
        if (validationError) {
            this.showValidationError(validationError);
            return false;
        }

        for (var i = 0; i < this.data["tokens"].length; i++) {
            var token = this.data["tokens"][i];
            validationError = this.validateCustomMaintenanceTiming(token, values);
            if (validationError) {
                this.showValidationError(validationError);
                return false;
            }

            validationError = this.validateCustomNoticeTiming(token, values);
            if (validationError) {
                this.showValidationError(validationError);
                return false;
            }
        }

        return true;
    },

    validatePimcoreTiming: function(values) {
        var startError = this.validateRequiredDateTimePair(
            values["pimcore_from_date"],
            values["pimcore_from_time"],
            this.translateWithFallback(
                "custommaintenance.pimcore_maintenance_requires_start",
                "Die Pimcore-Zeitsteuerung benötigt einen Startzeitpunkt mit Datum und Uhrzeit."
            )
        );
        if (startError) {
            return startError;
        }

        var endError = this.validateRequiredDateTimePair(
            values["pimcore_to_date"],
            values["pimcore_to_time"],
            this.translateWithFallback(
                "custommaintenance.pimcore_maintenance_requires_end",
                "Die Pimcore-Zeitsteuerung benötigt einen Endzeitpunkt mit Datum und Uhrzeit."
            )
        );
        if (endError) {
            return endError;
        }

        if (values["pimcore_show_info"] === "automatic") {
            return this.validateRequiredDateTimePair(
                values["pimcore_show_info_from_date"],
                values["pimcore_show_info_from_time"],
                this.translateWithFallback(
                    "custommaintenance.notice_requires_start",
                    "Der zeitgeplante Hinweis für Pimcore benötigt einen Startzeitpunkt mit Datum und Uhrzeit."
                )
            );
        }

        return null;
    },

    validateCustomMaintenanceTiming: function(token, values) {
        if (values[token + "_maintenance_mode"] !== "scheduled") {
            return null;
        }

        var startError = this.validateRequiredDateTimePair(
            values[token + "_from_date"],
            values[token + "_from_time"],
            this.translateWithFallback(
                "custommaintenance.maintenance_requires_start",
                "Die zeitgesteuerte Maintenance benötigt einen Startzeitpunkt mit Datum und Uhrzeit."
            )
        );
        if (startError) {
            return startError;
        }

        return this.validateOptionalDateTimePair(
            values[token + "_to_date"],
            values[token + "_to_time"],
            this.translateWithFallback(
                "custommaintenance.maintenance_optional_end_complete",
                "Das optionale Ende der zeitgesteuerten Maintenance muss Datum und Uhrzeit vollständig enthalten."
            )
        );
    },

    validateCustomNoticeTiming: function(token, values) {
        if (values[token + "_show_info"] !== "automatic") {
            return null;
        }

        return this.validateRequiredDateTimePair(
            values[token + "_show_info_from_date"],
            values[token + "_show_info_from_time"],
            this.translateWithFallback(
                "custommaintenance.notice_requires_start",
                "Der zeitgeplante Hinweis benötigt einen Startzeitpunkt mit Datum und Uhrzeit."
            )
        );
    },

    validateRequiredDateTimePair: function(dateValue, timeValue, message) {
        var hasDate = this.hasValue(dateValue);
        var hasTime = this.hasValue(timeValue);

        if (!hasDate && !hasTime) {
            return message;
        }

        if (hasDate !== hasTime) {
            return message;
        }

        return null;
    },

    validateOptionalDateTimePair: function(dateValue, timeValue, message) {
        var hasDate = this.hasValue(dateValue);
        var hasTime = this.hasValue(timeValue);

        if (hasDate !== hasTime) {
            return message;
        }

        return null;
    },

    hasValue: function(value) {
        return typeof value !== "undefined" && value !== null && String(value).replace(/^\s+|\s+$/g, "") !== "";
    },

    showValidationError: function(message) {
        pimcore.helpers.showNotification(
            t("error"),
            message,
            "error"
        );
    },

    createDocumentSelectorField: function(name, fieldLabel, documentConfig) {
        var normalizedConfig = documentConfig || {};
        var displayValue = normalizedConfig["path"] ? normalizedConfig["path"] : "";
        var documentId = normalizedConfig["id"] ? String(normalizedConfig["id"]) : "";
        var displayId = name + "_display";
        var idFieldName = name + "_id";
        var pathFieldName = name + "_path";

        return {
            xtype: "fieldcontainer",
            fieldLabel: fieldLabel,
            layout: "hbox",
            items: [
                {
                    xtype: "hidden",
                    name: idFieldName,
                    value: documentId
                },
                {
                    xtype: "textfield",
                    id: displayId,
                    name: pathFieldName,
                    value: displayValue,
                    width: 530,
                    editable: false,
                    cls: "input_drop_target",
                    listeners: {
                        render: function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                ddGroup: "element",
                                getTargetFromEvent: function () {
                                    return el.getEl();
                                },
                                onNodeOver: function (target, dd, e, data) {
                                    var record = data.records[0];
                                    if (record && record.data && record.data.elementType === "document") {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }

                                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                                },
                                onNodeDrop: function (target, dd, e, data) {
                                    var record = data.records[0];
                                    var droppedData = record ? record.data : null;

                                    if (!droppedData || droppedData.elementType !== "document") {
                                        return false;
                                    }

                                    this.applyDocumentSelection(name, {
                                        id: droppedData.id,
                                        path: droppedData.path
                                    });

                                    return true;
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_search",
                    style: "margin-left: 5px",
                    handler: function () {
                        pimcore.helpers.itemselector(false, function (selection) {
                            this.applyDocumentSelection(name, {
                                id: selection.id,
                                path: selection.fullpath
                            });
                        }.bind(this), {
                            type: ["document"]
                        });
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_open",
                    style: "margin-left: 5px",
                    handler: function () {
                        var form = this.layout.getForm();
                        var pathField = form.findField(pathFieldName);

                        if (pathField && pathField.getValue()) {
                            pimcore.helpers.openDocumentByPath(pathField.getValue());
                        }
                    }.bind(this)
                },
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    style: "margin-left: 5px",
                    handler: function () {
                        this.applyDocumentSelection(name, {
                            id: "",
                            path: ""
                        });
                    }.bind(this)
                }
            ]
        };
    },

    applyDocumentSelection: function(name, documentConfig) {
        var form = this.layout.getForm();
        var idField = form.findField(name + "_id");
        var pathField = form.findField(name + "_path");

        if (idField) {
            idField.setValue(documentConfig && documentConfig["id"] ? String(documentConfig["id"]) : "");
        }

        if (pathField) {
            pathField.setValue(documentConfig && documentConfig["path"] ? documentConfig["path"] : "");
        }
    },

    removeCustomMaintenance: function (token, fieldset) {
        var title = fieldset && fieldset.title ? fieldset.title : token;
        var confirmTitle = this.translateWithFallback(
            "custommaintenance.delete_confirm_title",
            "Maintenance-Art loeschen"
        );
        var confirmMessage = this.translateWithFallback(
            "custommaintenance.delete_confirm_message",
            "Soll die Maintenance-Art \"%s\" wirklich geloescht werden?"
        ).replace("%s", title);

        Ext.Msg.confirm(confirmTitle, confirmMessage, function (button) {
            if (button !== "yes") {
                return;
            }

            var updatedTokens = this.data["tokens"].filter(function (currentToken) {
                return currentToken !== token;
            });
            var values = this.getFormValues({
                custom_tokens: updatedTokens.join(",")
            });

            this.persistAdminValues(values, function () {
                this.data["tokens"] = updatedTokens;
                delete this.data["custom"][token];
                if (fieldset && fieldset.ownerCt === this.layout) {
                    this.layout.remove(fieldset, true);
                }
                this.updateCustomTokensField();
                this.refreshFormLayout();
                pimcore.layout.refresh();
            }.bind(this));
        }.bind(this));
    },

    buildNewCustomConfig: function () {
        return {
            active: "false",
            temporary_active: "false",
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
        var maintenanceMode = this.deriveMaintenanceMode(config);
        var updateTitle = function () {
            var form = this.layout.getForm();
            var tokenField = form.findField(token + "_token");
            var descriptionField = form.findField(token + "_description");
            var titleToken = tokenField && tokenField.getValue() ? tokenField.getValue() : token;
            var titleDescription = descriptionField && descriptionField.getValue() ? descriptionField.getValue() : "";

            fieldset.setTitle(titleDescription ? titleDescription : titleToken);
        }.bind(this);

        items.push({
            xtype: "hidden",
            name: token + "_original_token",
            value: token
        });
        items.push({
            xtype: "button",
            text: this.translateWithFallback(
                "custommaintenance.delete",
                "Maintenance-Art loeschen"
            ),
            iconCls: "pimcore_icon_delete",
            style: {
                marginBottom: "10px"
            },
            handler: function () {
                this.removeCustomMaintenance(token, fieldset);
            }.bind(this)
        });
        items.push({
            xtype: "textfield",
            fieldLabel: this.translateWithFallback("custommaintenance.token", "Technischer Token"),
            name: token + "_token",
            value: token,
            width: 425,
            listeners: {
                change: updateTitle
            }
        });
        items.push({
            xtype: "textfield",
            fieldLabel: this.translateWithFallback("custommaintenance.description", "Beschreibung"),
            name: token + "_description",
            value: descriptionValue,
            width: 425,
            listeners: {
                change: updateTitle
            }
        });

        items.push({
            fieldLabel: this.translateWithFallback("custommaintenance.maintenance_mode", "Maintenance-Modus"),
            xtype: "combo",
            width: 425,
            name: token + "_maintenance_mode",
            value: maintenanceMode,
            store: [
                ["inactive", this.translateWithFallback("custommaintenance.inactive", "Inaktiv")],
                ["active", this.translateWithFallback("custommaintenance.active", "Aktiv")],
                ["scheduled", this.translateWithFallback("custommaintenance.scheduled", "Zeitgesteuert")]
            ],
            listeners: {
                select: this.applyMaintenanceMode.bind(this, token)
            },
            mode: "local",
            editable: false,
            forceSelection: true,
            triggerAction: "all"
        });
        items.push({
            xtype: 'fieldcontainer',
            fieldLabel: this.translateWithFallback("custommaintenance.temporary_active", "Temporärer Aktiv-Override"),
            layout: 'hbox',
            items: [
                {
                    xtype: "combo",
                    width: 325,
                    name: token + "_temporary_active",
                    value: config["temporary_active"] ? config["temporary_active"] : "false",
                    store: [
                        ["false", this.translateWithFallback("custommaintenance.is_not_temporarily_active", "Nicht aktiv")],
                        ["true", this.translateWithFallback("custommaintenance.is_temporarily_active", "Temporär aktiv")]
                    ],
                    mode: "local",
                    editable: false,
                    forceSelection: true,
                    triggerAction: "all"
                },
                {
                    xtype: 'displayfield',
                    value: this.translateWithFallback(
                        "custommaintenance.temporary_active.helptext",
                        "Technischer Aktiv-Override. Überlagert die Basis-Konfiguration vorübergehend, ohne sie zu zerstören."
                    ),
                    style: {
                        marginLeft : "20px"
                    }
                }
            ]
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
        items.push(new Ext.form.FieldSet({
            xtype: 'fieldset',
            id: token + "_timecontrol_fieldset",
            title: t("custommaintenance.timecontrol"),
            collapsible: false,
            autoHeight: true,
            hidden: maintenanceMode !== "scheduled",
            disabled: maintenanceMode !== "scheduled",
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
        }));
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
            listeners: {
                afterrender: function() {
                    this.applyMaintenanceMode(token);
                }.bind(this)
            },
            items: items
        });

        return fieldset;
    },

    deriveMaintenanceMode: function(config) {
        if (config["active"] === "true") {
            return "active";
        }

        if (this.isConfiguredDateTime(config["planned"]["from"])) {
            return "scheduled";
        }

        return "inactive";
    },

    isConfiguredDateTime: function(dateTimeConfig) {
        if (!dateTimeConfig) {
            return false;
        }

        var date = (dateTimeConfig["date"] || "").replace(/^\s+|\s+$/g, "");
        var time = (dateTimeConfig["time"] || "").replace(/^\s+|\s+$/g, "");

        if (!date || !time) {
            return false;
        }

        return !(date === "01.01.1970" && time === "00:00");
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
