var $ = jQuery.noConflict();
var mba_tags_meta_box;
(function ($) {
    mba_tags_meta_box = {
        run: function () {
            this.click('.block__related_tags-control');
        },
        click: function (elem) {
            $(elem).find('select').on('change', function () {

                $(this).closest('.block__related_tags').find('.block__related_tags-overlay').addClass('show');
                var url = $(this).attr('data-meta-url');
                mba_tags_meta_box.ajax_request($(this).val(), url, $(this));
            });
        },
        ajax_request: function (val, url, selector) {

            var wrap = selector.closest('.block__related_tags');
            var wrapper = wrap.find('.block__related_tags-items');

            var data = {
                'param': val
            };

            function action(response, status, xhr) {
                wrapper.html(response.html);
                wrap.find('.block__related_tags-overlay').removeClass('show');
            }
            $.post(url, data, action, 'json');
        }

    };
    $(document).ready(function ($) {
        mba_tags_meta_box.run();
    });
})(jQuery);
