(function ($) {

    let $container = '';
    let $checkbox = '';

    $(function () {

        $container = $('#wp-admin-bar-awpp_adminbar-criticalcss');
        $checkbox = $container.find('input#awpp-check-criticalcss');
        if (!$container.length || !$checkbox.length) {
            return;
        }

        const styles = $('head').find('link[rel=stylesheet]');

        $checkbox.on('change', function () {

            $.each(styles, function (index, e) {

                let $e = $(e);
                let link;
                let id = $e.attr('id');

                if ($e.attr('data-href') === undefined) {
                    link = $e.attr('href');
                } else {
                    link = $e.attr('data-href');
                }

                if (id.includes('admin-bar') || id.includes('adminbar') || id.includes('dashicons')) {
                    return true;
                }

                if ($checkbox.prop('checked')) {
                    $e.attr('data-href', link);
                    $e.removeAttr('href');
                } else {
                    $e.attr('href', link);
                }
            });
        });
    });

})(jQuery);