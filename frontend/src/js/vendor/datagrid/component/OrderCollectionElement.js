import Component from '../lib/Component';
import Utils from '../lib/Utils';
import ComponentFinder from '../lib/ComponentFinder';
import OrderCollection from './OrderCollection';

export default class OrderCollectionElement extends Component {
    static get id() {
        return 'OrderCollectionElement';
    }

    init() {
        super.init();

        let parentComponent = ComponentFinder.parent(this.$node, 'order_collection');
        let removeElementButton = Utils.single_element(this.$node, 'remove_element');

        removeElementButton.on('click', () => {
            parentComponent.trigger(OrderCollection.events.REMOVE_ELEMENT, this.$node);
        });
    }

}
