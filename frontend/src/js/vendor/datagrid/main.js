import Builder from './lib/Builder';
import components from './component_list';

$(document).ready(function () {
    let builder = new Builder(components);
    builder
        .bootstrap({
            afterComponentCreated: function (node) {
                node.attr('data-initialization-state', 'complete');
            }
        })
        .init($(document.body));
});