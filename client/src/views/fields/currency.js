/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

Espo.define('views/fields/currency', 'views/fields/float', function (Dep) {

    return Dep.extend({

        type: 'currency',

        editTemplate: 'fields/currency/edit',

        detailTemplate: 'fields/currency/detail',

        detailTemplate1: 'fields/currency/detail-1',

        detailTemplate2: 'fields/currency/detail-2',

        listTemplate: 'fields/currency/list',

        listTemplate1: 'fields/currency/list-1',

        listTemplate2: 'fields/currency/list-2',

        listLinkTemplate: 'fields/currency/list-link',

        detailTemplateNoCurrency: 'fields/currency/detail-no-currency',

        maxDecimalPlaces: 3,

        data: function () {
            var currencyValue = this.model.get(this.currencyFieldName) || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency');
            return _.extend({
                currencyFieldName: this.currencyFieldName,
                currencyValue: currencyValue,
                currencyOptions: this.currencyOptions,
                currencyList: this.currencyList,
                currencySymbol: this.getMetadata().get(['app', 'currency', 'symbolMap', currencyValue]) || ''
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.currencyFieldName = this.name + 'Currency';
            this.currencyList = this.getConfig().get('currencyList') || ['USD'];
            var currencyValue = this.currencyValue = this.model.get(this.currencyFieldName) || this.getConfig().get('defaultCurrency');
        },

        getCurrencyFormat: function () {
            return this.getConfig().get('currencyFormat') || 1;
        },

        _getTemplateName: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var prop
                if (this.mode == 'list') {
                    var prop = 'listTemplate' + this.getCurrencyFormat().toString();
                } else {
                    var prop = 'detailTemplate' + this.getCurrencyFormat().toString();
                }
                if (this.options.hideCurrency) {
                    prop = 'detailTemplateNoCurrency';
                }

                if (prop in this) {
                    return this[prop];
                }
            }
            return Dep.prototype._getTemplateName.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$currency = this.$el.find('[name="' + this.currencyFieldName + '"]');
                this.$currency.on('change', function () {
                    this.model.set(this.currencyFieldName, this.$currency.val());
                }.bind(this));
            }
        },

        fetch: function () {
            var value = this.$element.val();
            value = this.parse(value);

            var data = {};

            var currencyValue = this.$currency.val();
            if (value === null) {
                currencyValue = null;
            }

            data[this.name] = value;
            data[this.currencyFieldName] = currencyValue
            return data;
        },
    });
});

