import Component from '../lib/Component';
import Utils from '../lib/Utils';

export default class Filter extends Component {
    static get id() {
        return 'Filter';
    }

    init() {
        super.init();
        this.filterTable = Utils.single_element(this.$node, 'filter_table');
        this.fieldSelector = Utils.single_element(this.$node, 'field_selector');
        this.initHideButtons();

        let self = this;

        let hideButtons = Utils.single_element(this.$node, 'hide_filter_row');
        hideButtons.each(function (idx, hideButton) {
            $(hideButton).on('click', function () {
                let rowName = $(this).attr('data-field');
                self.hideRow(rowName);

            })
        });

        let displayButtons = this.fieldSelector.find('li');
        displayButtons.each(function (idx, displayButton) {
            $(displayButton).on('click', function () {
                let rowName = $(this).attr('data-field');
                self.revertRowVisibility(rowName);
            });
        })
    }

    initHideButtons() {
        this.$node.find('li[data-field]').each((idx, element) => {
            const fieldName = $(element).attr('data-field');
            const tableRow = this.filterTable.find(`tr[data-field='${fieldName}']`);
            const needDisplayTableRow = tableRow.attr('data-display');

            if (needDisplayTableRow) {
                Utils.single_element($(element), 'row_visibility').prop('checked', true);
            } else {
                Utils.single_element($(element), 'row_visibility').prop('checked', false);
                tableRow.find(":input").attr("disabled", true);
            }
        });
    }

    hideRow(rowName) {
        let tableRow = this.filterTable.find(`tr[data-field='${rowName}']`);
        tableRow.hide();
        tableRow.find(":input").attr("disabled", true);
        let rowSelector = this.fieldSelector.find(`li[data-field='${rowName}']`);
        Utils.single_element(rowSelector, 'row_visibility').prop('checked', false);
    }

    showRow(rowName) {
        let tableRow = this.filterTable.find(`tr[data-field='${rowName}']`);
        tableRow.find(":input").attr("disabled", false);
        tableRow.show();
        let rowSelector = this.fieldSelector.find(`li[data-field='${rowName}']`);
        Utils.single_element(rowSelector, 'row_visibility').prop('checked', true);
    }

    revertRowVisibility(rowName) {
        let tableRow = this.filterTable.find(`tr[data-field='${rowName}']`);
        if (tableRow.is(':visible')) {
            this.hideRow(rowName);
        } else {
            this.showRow(rowName);
        }
    }
}