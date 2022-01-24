(function ($) {

    /**
     * Collection of inputs user is still typing in
     */
    const StillTyping = {}

    /**
     * Check if user is still typing
     *
     * @param {Object} input
     *
     * @returns {Boolean}
     */
    const IsStillTyping = (input) => {
        return StillTyping[input.attr('id')] !== undefined;
    }

    /**
     * Clear typing timeout
     *
     * @param {Object} input
     *
     * @returns {Void}
     */
    const ClearTypingInput = (input) => {
        clearInterval(StillTyping[input.attr('id')]);
        delete StillTyping[input.attr('id')];
    }

    /**
     * Set delayed callback
     *
     * @param {Object}   input
     * @param {Function} cb
     *
     * @returns {Void}
     */
    const SetDelayedCallback = (input, cb) => {
        if (IsStillTyping(input)) {
            clearInterval(StillTyping[input.attr('id')]);
        }

        StillTyping[input.attr('id')] = setTimeout(() => {
            ClearTypingInput(input);
            cb();
        }, 500);
    }

    /**
     * Get project locals
     *
     * @param {String} variable
     *
     * @returns {Mixed}
     */
    const GetLocal = (variable) => NotiLocals[variable];

    /**
     * Log screen initialization
     */
    const InitializeLogScreen = () => {
        // Initialize the events
        const table = $('#log-table').DataTable({
            lengthChange: true,
            dom: '<"tablenav top"<"tablenav-pages"p>>t<"tablenav bottom"<"alignleft"l><"tablenav-pages"p>>',
            pagingType: 'full_numbers',
            ordering: false,
            processing: true,
            stateSave: false,
            serverSide: true,
            ajax: {
                url: GetLocal('apiEntpoint') + '/events',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': GetLocal('apiNonce')
                },
                data: (params) => ({
                    length: params.length,
                    offset: params.start,
                    search: $('#event-search-input').val(),
                    date_range: $('#date-selector').val(),
                    event_level: $('#event-level').val(),
                    event_type: $('#event-type').val()
                })
            },
            language: {
                search: '',
                searchPlaceholder: 'Type to search in log',
                paginate: {
                    first: '«',
                    previous: '‹',
                    next: '›',
                    last: '»'
                },
                lengthMenu: "Show _MENU_ events",
                processing: "Loading..."
            },
            initComplete: () => {
                const date = $('<div/>', {
                    class: 'alignleft actions'
                });

                const dateSelect = $('<select/>', {
                    id: 'date-selector'
                }).bind('change', function() {
                    table.ajax.reload();
                });

                dateSelect.append(`<option value="">Filter By Date</option>`);
                dateSelect.append(`<option value="today">Today</option>`);
                dateSelect.append(`<option value="yesterday">Yesterday</option>`);
                dateSelect.append(`<option value="-7 days">Past 7 days</option>`);
                dateSelect.append(`<option value="-30 days">Past 30 days</option>`);

                date.append(dateSelect);

                $('#log-table_wrapper .top').append(date);

                // Filter by event type
                const event = $('<div/>', {
                    class: 'alignleft actions'
                });

                const eventSelect = $($('#event-type-container').html());

                $(eventSelect).bind('change', function() {
                    table.ajax.reload();
                });

                event.append(eventSelect);

                $('#log-table_wrapper .top').append(event);
            },
            // columnDefs: [
            //     { className: 'column-description desc', targets: [0] }
            // ],
            // createdRow: function (row, data) {
            //     // Building the description
            //     $('td:eq(0)', row).html($('<p/>').html(data[0]));
            //     // $('td:eq(0)', row).append($('<div/>', {
            //     //     class: 'second'
            //     // }).html('Occurred: <strong>' + data[3] + '</strong>'));
            // }
        });

        $('#log-table').on('processing.dt', (e, settings, processing) => {
            if (processing) {
                $('#log-table #the-list').html(
                    '<tr class="odd"><td colspan="3">Loading...</td></tr>'
                );
            }
        });

        // Initialize the search input
        $('#event-search-input').bind('keyup', () => {
            SetDelayedCallback($(this), () => table.ajax.reload());
        });

        $('.subsubsub a').each(function() {
            $(this).bind('click', (e) => {
                e.preventDefault();

                // Remove highlight
                $('.subsubsub a').removeClass('current');
                $(this).addClass('current');

                $('#event-level').val($(this).data('type'));
                table.ajax.reload();
            });
        });
    }

    /**
     * Event Types screen
     */
    const InitializeEventTypeScreen = () => {
        const table = $('#event-types-table').DataTable({
            lengthChange: true,
            dom: '<"tablenav top"<"tablenav-pages"p>>t<"tablenav bottom"<"alignleft"l><"tablenav-pages"p>>',
            pagingType: 'full_numbers',
            ordering: false,
            processing: true,
            stateSave: false,
            serverSide: true,
            ajax: {
                url: GetLocal('apiEntpoint') + '/event-types',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': GetLocal('apiNonce')
                },
                data: (params) => ({
                    length: params.length,
                    offset: params.start,
                    status: $('#event-type-status').val(),
                    search: $.trim($('#event-type-search-input').val()),
                    category: $('#cat').val(),
                }),
                dataSrc: (json) => {
                    return json.data;
                }
            },
            language: {
                search: '',
                searchPlaceholder: 'Type to search in list',
                paginate: {
                    first: '«',
                    previous: '‹',
                    next: '›',
                    last: '»'
                },
                lengthMenu: "Show _MENU_ event types"
            },
            columnDefs: [
                { className: 'check-column', targets: [0] },
                { className: 'plugin-title column-primary', targets: [1] },
                { className: 'column-description desc', targets: [2] }
            ],
            initComplete: () => {
                // Bulk actions
                const bulk = $('<div/>', {
                    class: 'alignleft actions bulkactions'
                });

                const actionSelect = $('<select/>', {
                    id: 'bulk-action-selector'
                });

                actionSelect.append(`<option value="">Bulk Actions</option>`);
                actionSelect.append(`<option value="activate">Activate</option>`);
                actionSelect.append(`<option value="deactivate">Deactivate</option>`);
                actionSelect.append(`<option value="subscribe">Subscribe</option>`);
                actionSelect.append(`<option value="unsubscribe">Unsubscribe</option>`);
                actionSelect.append(`<option value="trash">Trash</option>`);

                bulk.append(actionSelect);

                bulk.append($('<input/>', {
                    type: 'button',
                    class: 'button action',
                    value: 'Apply'
                }).bind('click', function() {
                    const action = $('#bulk-action-selector').val();
                    const ids    = [];

                    // Get all the selected items
                    $('.event-id:checked').each(function() {
                        ids.push($(this).val());
                    });

                    if (action && ids.length > 0) {
                        $.ajax(GetLocal('apiEntpoint') + '/bulk/event-type', {
                            type: 'PUT',
                            dataType: 'json',
                            headers: {
                                'X-WP-Nonce': GetLocal('apiNonce')
                            },
                            data: {
                                action,
                                ids
                            },
                            success: function (response) {
                                //
                            },
                            error: function () {
                                //
                            },
                            complete: function () {
                                table.ajax.reload();
                            }
                        });
                    }
                }));

                $('#event-types-table_wrapper .top').append(bulk);

                // Filter by event category
                const event = $('<div/>', {
                    class: 'alignleft actions'
                });

                const cats = $($('#category-list').html());

                $(cats).bind('change', function() {
                    table.ajax.reload();
                });

                event.append(cats);

                $('#event-types-table_wrapper .top').append(event);
            },
            createdRow: function (row, data) {
                $('td:eq(0)', row).html('<input value="' + data[0] + '" class="event-id" name="events" type="checkbox" />');

                // Building the name of the event type with allowed actions
                $('td:eq(1)', row).html('<strong>' + data[1] + '</strong>');

                const actions = $('<div/>', { class: 'row-actions visible' });

                if (data[4] !== 'trash') {
                    if (data[5]['edit']) {
                        actions.append('<span class="0"><a href="' + data[5]['edit'] + '">Edit</a> | </span>');
                    } else {
                        actions.append('<span class="0"><a href="#" disabled>Edit</a> | </span>');
                    }

                    if (data[5]['duplicate']) {
                        actions.append('<span class="0"><a href="' + data[5]['duplicate'] + '">Duplicate</a> | </span>');
                    } else {
                        actions.append('<span class="0"><a href="#" disabled>Duplicate</a> | </span>');
                    }
                }

                if (data[4] === 'publish') {
                    if (data[5]['deactivate']) {
                        actions.append('<span class="0"><a href="' + data[5]['deactivate'] + '">Deactivate</a> | </span>');
                    } else {
                        actions.append('<span class="0"><a href="#" disabled>Deactivate</a> | </span>');
                    }
                } else if (data[4] === 'draft') {
                    if (data[5]['activate']) {
                        actions.append('<span class="0"><a href="' + data[5]['activate'] + '">Activate</a> | </span>');
                    } else {
                        actions.append('<span class="0"><a href="#" disabled>Activate</a> | </span>');
                    }
                }

                if (data[5]['subscribe']) {
                    actions.append('<span class="0"><a href="' + data[5]['subscribe'] + '">Subscribe</a> | </span>');
                } else {
                    actions.append('<span class="0"><a href="' + data[5]['unsubscribe'] + '">Unsubscribe</a> | </span>');
                }

                if (data[4] === 'trash') {
                    if (data[5]['restore']) {
                        actions.append('<span class="0"><a href="' + data[5]['restore'] + '">Restore</a> | </span>');
                        actions.append('<span class="delete"><a href="' + data[5]['delete'] + '" class="delete">Delete Permanently</a></span>');
                    } else {
                        actions.append('<span class="0"><a href="#" disabled>Restore</a> | </span>');
                        actions.append('<span class="0"><a href="#" disabled>Delete Permanently</a></span>');
                    }
                } else {
                    if (data[5]['trash']) {
                        actions.append('<span class="delete"><a href="' + data[5]['trash'] + '" class="delete">Trash</a></span>');
                    } else {
                        actions.append('<span class="delete"><a href="#" class="delete" disabled>Trash</a></span>');
                    }
                }

                $('td:eq(1)', row).append(actions);

                // Building the description
                $('td:eq(2)', row).html($('<p/>').html(data[2]));
                $('td:eq(2)', row).append($('<div/>', {
                    class: 'second'
                }).html('Minimum Required Version: <strong>' + data[6] + '</strong>'));
            }
        });

        $('#event-types-table').on('processing.dt', (e, settings, processing) => {
            if (processing) {
                $('#event-types-table #the-list').html(
                    '<tr class="odd"><td colspan="3">Loading...</td></tr>'
                );
            }
        });

        // Initialize the search input
        $('#event-type-search-input').bind('keyup', () => {
            SetDelayedCallback($(this), () => table.ajax.reload());
        });

        $('.subsubsub a').each(function() {
            $(this).bind('click', (e) => {
                e.preventDefault();

                // Remove highlight
                $('.subsubsub a').removeClass('current');
                $(this).addClass('current');

                $('#event-type-status').val($(this).parent().attr('class'));
                table.ajax.reload();
            });
        });
    }

    $(document).ready(function () {
        const screen = $('#noti-page-id').val();

        if (screen === 'log') {
            InitializeLogScreen();
        } else if (screen === 'event-types') {
            InitializeEventTypeScreen();
        }
    });
})(jQuery);