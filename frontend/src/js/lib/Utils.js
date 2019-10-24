
export default class Utils {

    static single_element($node, elementName) {
        //todo prevent select elements of child components
        return $node.find(`[data-component-role=${elementName}]`);
    }

    static callMethod($node, name) {
        var data = {};
        $node.trigger(name, data);
        return data.returnResult;
    }

    static registerMethod($node, name, method) {
        $node.on(name, function(e, data){
            data.returnResult = method();
        });
    }
}