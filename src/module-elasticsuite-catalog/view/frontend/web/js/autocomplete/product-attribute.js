define(['underscore'], function(_) {
    var Renderer = {
        render : function (data) {
            var data = data.filter(function(item) {
                return item.type == "product_attribute"; 
            }).map(function(item) {
                return item['attribute_label']
            }).reduce(function(prev, item) {
                if (item in prev) {
                    prev[item]++;
                } else {
                    prev[item] = 1;
                }
                return prev;
            }, {});

            data = _.pairs(data).sort(function(item1, item2) {
                return item2[0] - item1[0]
            }).map(function(item) {return item[0]});

            if (data.length > 2) {
                data = data.slice(0, 2);
                data.push('...');
            }

            return data.join(', ');
        }
    }
    return Renderer;
});
