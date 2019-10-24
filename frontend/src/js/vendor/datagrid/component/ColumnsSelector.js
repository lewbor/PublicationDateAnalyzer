import Component from '../lib/Component';
import Utils from '../lib/Utils';

export default class ColumnsSelector extends Component {
    static get id() {
        return 'ColumnsSelector';
    }

    init() {
        super.init();

        this.controls = {
            allColumnsList: Utils.single_element(this.$node, 'all_columns_list'),
            targetColumns: Utils.single_element(this.$node, 'target_columns'),
            selectedFields: Utils.single_element(this.$node, 'selected_fields'),

            addColumnButton: Utils.single_element(this.$node, 'add_column_button'),
            delColumnButton: Utils.single_element(this.$node, 'del_column_button'),
            moveUpColumnButton: Utils.single_element(this.$node, 'move_up_column_button'),
            moveDownColumnButton: Utils.single_element(this.$node, 'move_down_column_button')
        };


        this.controls.addColumnButton.on('click', () => {
            let selectedOptions = this.controls.allColumnsList.find('option:selected');
            if (selectedOptions.length) {
                selectedOptions.each((idx, selectedOption) => {
                    let selectedValue = $(selectedOption).val();
                    let selectedLabel = $(selectedOption).text();
                    if (this.controls.targetColumns.find(`option[value='${selectedValue}']`).length === 0) {
                        let newOption = $('<option></option>')
                            .attr('value', selectedValue)
                            .text(selectedLabel);
                        this.controls.targetColumns.append(newOption);
                    }
                });
                this.syncModel();

            }
        });

        this.controls.delColumnButton.on('click', () => {
            this.controls.targetColumns.find('option:selected').remove();
            this.syncModel();
        });

        this.controls.moveUpColumnButton.on('click', () => {
            let selectedOption = this.controls.targetColumns.find('option:selected');
            if (selectedOption.length) {
                selectedOption.first().prev().before(selectedOption);
                this.syncModel();
            }
        });

        this.controls.moveDownColumnButton.on('click', () => {
            let selectedOption = this.controls.targetColumns.find('option:selected');
            if (selectedOption.length) {
                selectedOption.last().next().after(selectedOption);
                this.syncModel();
            }
        });

    }

    syncModel() {
        let selectedFields = this.controls.targetColumns.find('option').map(function () {
            return this.value;
        }).get().join(',');

        this.controls.selectedFields.val(selectedFields);

    }
}