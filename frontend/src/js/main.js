import $ from 'jquery';
import 'babel-polyfill';
import Builder from './lib/Builder';

import gridComponents from './vendor/datagrid/component_list';
import appComponents from './component/component_list';

$(document).ready(function () {
    let components = [].concat(gridComponents).concat(appComponents);
    let builder = new Builder(components);
    builder
        .bootstrap({
            afterComponentCreated: function (node) {
                node.attr('data-initialization-state', 'complete');
            }
        })
        .init($(document.body));
});