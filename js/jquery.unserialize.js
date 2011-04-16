(function($) {
    $.fn.unserialize = function(values) {

        if (!values) {
            return this;
        }

        values = values.split("&");

        var serialized_values = [];
        $.each(values, function() {
            var properties = this.split("=");

            if ((typeof properties[0] != 'undefined') && (typeof properties[1] != 'undefined')) {
                serialized_values[properties[0].replace(/\+/g, " ")] = properties[1].replace(/\+/g, " ");
            }
        });

        values = serialized_values;

        $(this).find(":input").removeAttr('checked').each(function() {
            var tag_name = $(this).attr("name");
            if (values[tag_name] !== undefined) {
                if ($(this).attr("type") == "checkbox") {
                    $(this).attr("checked", "checked");
                } else {
                    $(this).val(values[tag_name]);
                }
            }
        })
    }
})(jQuery);