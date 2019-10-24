import Component from '../lib/Component';
import Utils from '../lib/Utils';
import ComponentFinder from '../lib/ComponentFinder';

var EVENTS = {
    REMOVE_ELEMENT: 'collection.remove_element'
};

export default class OrderCollection extends Component {
    static get id() {
        return 'OrderCollection';
    }

    propTypes() {
        return {
            prototype: {type: 'string'}
        }
    }

    static get events() {
        return EVENTS;
    }

    init() {
        super.init();

        let entityContainer = Utils.single_element(this.$node, 'entity_container');
        let childItems = ComponentFinder.all_childrens(entityContainer);
        this.state = {index: childItems.length};

        let addButton = Utils.single_element(this.$node, 'add_element');
        addButton.on('click', (e) => {
            e.preventDefault();
            this.addElement();
        });
        this.$node.on(EVENTS.REMOVE_ELEMENT, (e, elementNode) => this.removeElement($(elementNode)));

    }

    addElement(expanded = false) {
        let prototype = this.$node.data('prototype');

        let newItem = $(prototype.replace(/__name__/g, this.state.index));
        newItem.attr('data-expanded', JSON.stringify(expanded));
        let entityContainer = Utils.single_element(this.$node, 'entity_container');
        entityContainer.append(newItem);

        this.state.index++;

        $(document).trigger(Component.events.INIT_COMPONENTS, newItem);
        return newItem;
    }

    removeElement($elementNode) {
        $elementNode.trigger('remove');
    }

}

