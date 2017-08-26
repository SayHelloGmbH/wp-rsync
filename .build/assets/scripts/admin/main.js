import 'magnific-popup';

(function ($) {

    $(function () {

        window.wprsync_do_sync = function (e, path, version = false) {

            const $trigger = $(e);
            let data = {
                action: 'wprsync_ajax_sync',
                path: path,
                version: version
            };

            $.magnificPopup.open({
                items: {
                    src: ajaxurl + '?' + $.param(data)
                },
                type: 'ajax',
                callbacks: {
                    ajaxContentAdded: function () {
                        data.force = true;
                        let $container = $('#wprsync-popup');
                        const $sync_now = $container.find('button#sync-now');
                        $sync_now.on('click', function () {
                            $container.addClass('loading');
                            $container.parent().load(ajaxurl + '?' + $.param(data), function () {
                                $container = $('#wprsync-popup');
                                const new_date = $('#wprsync-popup').find('#new-latest-sync-date').text();
                                const new_version = $('#wprsync-popup').find('#new-latest-sync-version').text();
                                $trigger.parents('tr').find('td._latest-sync').text(new_date);
                                if (new_version !== '') {
                                    $trigger.parents('tr').find('td._latest-sync').append('<br>' + new_version);
                                }
                            });
                        });
                    }
                }
            }, 0);
        };
    });
})(jQuery);