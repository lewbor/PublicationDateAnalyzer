import Component from '../lib/Component';
import Utils from '../lib/Utils';

export default class ColumnsSelectorSimple extends Component {
    static get id() {
        return 'ColumnsSelectorSimple';
    }

    init() {
        super.init();

        this.controls = {
            selectedFields: Utils.single_element(this.$node, 'selected_fields'),
            targetColumnSelectors: Utils.single_element(this.$node, 'target_columns')
        };

        let inputs = this.controls.targetColumnSelectors.find('input');
        inputs.on('change', () => {
            this.syncModel();
        });

    }

    syncModel() {
        let selectedFields = this.controls.targetColumnSelectors.find('input:checked').map(function () {
            return this.value;
        }).get().join(',');

        this.controls.selectedFields.val(selectedFields);

    }
}