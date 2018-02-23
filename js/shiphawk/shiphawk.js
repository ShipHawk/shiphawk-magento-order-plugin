(function(){

    function insertAfter(referenceNode, newNode) {
        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
    }

    function loadSuggestions(event, container) {

        var input = event.element();

        var minlength = 3;

        var url = 'shiphawk/index/search';

        url = baseMagentoUrl + url;
        var parameters = {
            search_tag: input.value
        };

        if ( input.value.length >= minlength ) {
            new Ajax.Request(url, {
                method: 'post',
                parameters: parameters,
                onSuccess: function(transport)  {

                    var responce_html  = JSON.parse(transport.responseText);

                    if (responce_html.shiphawk_error) {
                        alert(responce_html.shiphawk_error);
                    }else{
                        if(responce_html.responce_html) {
                            var list = shjQuery('.shiphawk_item_type_suggest', container);
                            list.html(responce_html.responce_html);
                            list.show();
                        }
                    }

                },
                onLoading: function(transport)
                {
                }
            });
        }
    }

    function attachItemTypeSuggestors() {
        shjQuery("input[id^=shiphawk_item_][id$=type], #shiphawk_type_of_product").each(function(i, el){
            var suggestionsContainer = shjQuery("<div class='shiphawk_item_type_suggest'></div>");
            shjQuery(el).after(suggestionsContainer);

            shjQuery(suggestionsContainer).on('click', 'li.shiphawk_item_type_label', function(event){
                var li = shjQuery(event.target);
                var typeId = li.data('typeId');

                if (shjQuery(el).attr('id') == 'shiphawk_type_of_product') { // Item #1
                    shjQuery("#shiphawk_type_of_product_value").val(typeId);
                } else {
                    // eg shiphawk_item_2_type_id
                    shjQuery("#" + shjQuery(el).attr('id') + '_id' ).val(typeId);
                }


                shjQuery(el).val(li.text());
                suggestionsContainer.hide();
            })


            var td = shjQuery(el).parents('td');
            var typeloader;
            el.on('keyup', function(event){
                clearTimeout(typeloader);
                typeloader = setTimeout(function(){ loadSuggestions(event, td); }, 750);
            })
        })
    }

    function trTitle(text){
        return "<tr><td colspan=3 style='text-align:left; font-weight: bold; padding-top: 25px'>"+ text +"</td></tr>";
    }

    function addSectionTitles(){
        shjQuery("label[for=shiphawk_origin_firstname]").parents("tr").before(trTitle("Origin"))
        shjQuery("label[for=shiphawk_discount_fixed]").parents("tr").before(trTitle("Markup or Discount"))
        shjQuery("label[for=shiphawk_item_value]").parents("tr").before(trTitle("Value & Carrier Type"))

        shjQuery("label[for=shiphawk_type_of_product]").parents("tr").before(trTitle("Item #1"))
        var itemNumbers = [2,3,4,5,6,7,8,9,10];
        itemNumbers.each(function(itemNumber){
            var tr = shjQuery("label[for=shiphawk_item_"+ itemNumber +"_type]").parents("tr")
            tr.before(trTitle("Item #"+ itemNumber))
        });
    }

    function hideItemTypeIds(){
        shjQuery("#shiphawk_type_of_product_value, input[id^=shiphawk_item_][id$=_type_id]").parents("tr").hide();
    }

    document.observe("dom:loaded", function() {
        hideItemTypeIds();
        attachItemTypeSuggestors();
        addSectionTitles();
    });
})()