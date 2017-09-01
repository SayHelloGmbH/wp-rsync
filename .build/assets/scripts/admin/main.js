import 'magnific-popup';

(function ($) {

    $(function () {

        window.wprsync_do_sync = function (e, path, version = false, name = '', category = '') {

            const $trigger = $(e);
            let data = {
                action: 'wprsync_ajax_sync',
                path: path,
                version: version,
                name: name,
                category: category
            };

            $.magnificPopup.open({
                items: {
                    src: ajaxurl + '?' + $.param(data)
                },
                type: 'ajax',
                callbacks: {
                    ajaxContentAdded: function () {

                        let $container = $('#wprsync-popup');

                        /**
                         * plain anser toggle
                         */
                        $container.find('#toggle_exec').on('click', function () {
                            $(this).find('span').toggle();
                            $container.find('.plain-answer').slideToggle();
                        });

                        /**
                         * do sync
                         */
                        $container.find('button#sync-now').on('click', function () {

                            $container.find('.loading').fadeIn();
                            data.action = 'wprsync_ajax_run_sync';

                            const $msg = $container.find('#message');

                            jQuery.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                dataType: 'json',
                                data: data,
                            }).done(function (data) {

                                if (data['type'] === null || data['type'] !== 'success') {

                                    /**
                                     * error
                                     */
                                    let msg_content = data['message'];
                                    if (msg_content === '' || msg_content === undefined) {
                                        msg_content = 'undefined error';
                                    }
                                    $msg.html('<p>' + msg_content + '</p>');
                                    $msg.attr('class', 'notice notice-error');

                                    $container.find('.file-list')
                                        .find('#sync-now')
                                        .find('#toggle_exec')
                                        .find('.plain-answer').slideUp();

                                } else {

                                    /**
                                     * success
                                     */
                                    $msg.html('<p>' + data.message + '</p>');
                                    $msg.attr('class', 'notice notice-success');
                                    $.each(data.parsed_exec.files, function (key, atts) {
                                        $container.find('.file-list li#' + key).addClass('-done');
                                        $container.find('.file-list li#' + key + ' ._add').html(atts.add);
                                    });
                                    $container.find('.plain-answer ._command code').html(data.cmd);
                                    $container.find('.plain-answer ._answer code').html(data.plain_exec);
                                    $trigger.parents('tr').find('td._latest-sync .date').text(data.latest_sync.date);
                                    if (data.latest_sync.version) {
                                        $trigger.parents('tr').find('td._latest-sync .version').text(data.latest_sync.version);
                                    }
                                    $container.find('#sync-now').fadeOut();
                                }

                                $container.find('.loading').fadeOut();
                            });

                        });
                        /*
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
                        */
                    }
                }
            }, 0);
        };
    });
})(jQuery);