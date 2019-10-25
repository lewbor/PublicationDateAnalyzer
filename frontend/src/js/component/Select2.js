import 'jquery';
import 'select2/dist/js/select2.full';
import 'select2/dist/js/i18n/ru';

import Component from '../lib/Component';

const EVENTS = {
    ENTITY_INITIALIZED: 'entity_ajax.entity_initialized',
    ENTITY_CREATED: 'entity_ajax.entity_created',
};

const TRANSLATIONS = {
    'ru': {
        'placeholder': 'Выберите элемент'
    },
    'en': {
        'placeholder': 'Choice element'
    }
};

export default class Select2 extends Component {
    static get id() {
        return 'Select2';
    }

    propTypes() {
        return {
            width: {type: 'string', default: '100%'},
            multiple: {type: 'boolean', default: false},
            language: {type: 'string', 'default': 'ru'},
            allowClear: {type: 'boolean', 'default': false},
        }
    }

    static get events() {
        return EVENTS;
    }

    init() {
        this.state = {initialized: false};
        // Now select2 dont support html5 validation. But if we want to show that field is
        // required we need to use required flag.
        this.$node.removeAttr('required');
        const select2Options = this.buildOptions();
        this.$node.select2(select2Options);
    }

    buildOptions() {
        // Dont use multiple property because option 'multiple' is not allowed for Select2 when attached to a <select> element
        // use select multiple attribute instead
        return {
            width: this.props.width,
            language: this.props.language,
            placeholder: TRANSLATIONS[this.props.language].placeholder,
            allowClear: this.props.allowClear
        };
    }
}