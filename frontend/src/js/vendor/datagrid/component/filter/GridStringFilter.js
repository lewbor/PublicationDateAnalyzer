import Component from "../../lib/Component";
import Utils from "../../lib/Utils";

export default class GridStringFilter extends Component {
    static get id() {
        return 'GridStringFilter';
    }

    init() {
        super.init();
        this.typeSelector = Utils.single_element(this.$node, 'type_selector');
        this.searchField = Utils.single_element(this.$node, 'search_field');

        this._processType();

        this.typeSelector.on('change', (e) => {
            this._processType();
        });
    }

    _processType() {
        const optionValue = this.typeSelector.find('option:selected').val();
        switch (optionValue) {
            case 'em':
            case 'nem':
                this._expandOperator();

                break;
            default:
                this._collapseOperator();
                break;
        }
    }

    _expandOperator() {
        this.searchField.hide();
        this.typeSelector.removeClass('col-md-3').addClass('col-md-12');
    }

    _collapseOperator() {
        this.searchField.show();
        this.typeSelector.removeClass('col-md-12').addClass('col-md-3');
    }

}