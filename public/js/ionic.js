var IonicPage = {
    csrfToken: '',
    lastCommentId: null,
    contentId: null,
    contentType: null,

    refreshShoutbox: function() {
        $('.shoutbox-refresh').prop('disabled', true);
        $('.shoutbox-submit').prop('disabled', true);

        $.get(IONIC_BASE_URL+'shoutbox/refresh/global', function(response) {
            $('#shoutbox-container').html(response);

            $('.shoutbox-delete').click(function(){
                var id = $(this).prop('id').replace('shoutbox-delete-', '');

                IonicPage.deleteShoutboxPost(id);
            });

            $('.shoutbox-refresh').prop('disabled', false);
            $('.shoutbox-submit').prop('disabled', false);
        });
    },

    setupRelationRefresh: function(seconds, id) {
        if (seconds < 1) return;

        seconds *= 1000;

        setInterval(function() {
            $.get(IONIC_BASE_URL+'live/refresh/'+id, function(response) {
                $('#relation-messages').html(response);
            });
        }, seconds);
    },

    deleteShoutboxPost: function(id) {
        $.post(IONIC_BASE_URL+'shoutbox/delete/'+id, {csrf_token: this.csrfToken}, function(response) {
            if (response.status == true)
            {
                $('#shoutbox-post-'+id).hide('slow', function() { $('#shoutbox-post-'+id).remove(); });
            }
        }, 'json');
    },

    addShoutboxPost: function(content) {
        $('.shoutbox-refresh').prop('disabled', true);
        $('.shoutbox-submit').prop('disabled', true);

        $.post(IONIC_BASE_URL+'shoutbox/post/global', {csrf_token: this.csrfToken, post: content}, function(response) {
            if (response.status == true)
            {
                IonicPage.refreshShoutbox();
            }
            else
            {
                $('.shoutbox-refresh').prop('disabled', false);
                $('.shoutbox-submit').prop('disabled', false);
            }
        }, 'json');
    },

    setShoutboxAutoRefresh: function(seconds) {
        if (seconds < 1) seconds = 1;

        seconds *= 1000;

        setInterval(function() { IonicPage.refreshShoutbox(); }, seconds);
    },

    initKarma: function(cid, ctype) {
        $('#karma-add').click(function() {
            $.post(IONIC_BASE_URL+'users/karma/up', { id: cid, type: ctype, csrf_token: IonicPage.csrfToken }, function(response) {
                if (response.status)
                {
                    $('#karma-indicator').html(response.points).css('color', response.color);
                    $('#karma-options').remove();
                }
            }, 'json');
        });

        $('#karma-minus').click(function() {
            $.post(IONIC_BASE_URL+'users/karma/down', { id: cid, type: ctype, csrf_token: IonicPage.csrfToken }, function(response) {
                if (response.status)
                {
                    $('#karma-indicator').html(response.points).css('color', response.color);
                    $('#karma-options').remove();
                }
            }, 'json');
        });
    },

    initCommentsStuff: function() {
        $('body').on('click', '.post-karma-add', function() {
            var cid = $(this).parent().prop('id').replace('post-karma-options-', '');

            $.post(IONIC_BASE_URL+'comments/karma/up', { id: cid, csrf_token: IonicPage.csrfToken }, function(response) {
                if (response.status)
                {
                    $('#post-karma-'+cid).html(response.points).css('color', response.color);
                    $('#post-karma-options-'+cid).remove();
                }
            }, 'json');
        });

        $('body').on('click', '.post-karma-minus', function() {
            var cid = $(this).parent().prop('id').replace('post-karma-options-', '');

            $.post(IONIC_BASE_URL+'comments/karma/down', { id: cid, csrf_token: IonicPage.csrfToken }, function(response) {
                if (response.status)
                {
                    $('#post-karma-'+cid).html(response.points).css('color', response.color);
                    $('#post-karma-options-'+cid).remove();
                }
            }, 'json');
        });

        $('body').on('click', '.ionic-post-delete', function() {
            var cid = $(this).prop('id').replace('delete-post-', '');

            $.post(IONIC_BASE_URL+'comments/delete/'+cid, {csrf_token: IonicPage.csrfToken}, function(response) {
                if (response.status)
                {
                    $('#comment-id-'+cid).slideUp('slow', function() {$(this).remove();});
                }
            }, 'json');
        });
    },

    initCommentPagination: function(last, contentId, contentType) {
        this.lastCommentId = last;
        this.contentId = contentId;
        this.contentType = contentType;

        $('.ionic-load-comments').click(function(){
            var elem = $(this);

            elem.prop('disabled', true);

            $.get(IONIC_BASE_URL+'comments/pagination/'+IonicPage.lastCommentId+'/'+IonicPage.contentId+'/'+IonicPage.contentType, function(response) {
                if (response.status)
                {
                    if (response.count <= response.per_page || response.comments.length <= 0)
                    {
                        $('.ionic-load-comments').remove();
                    }

                    $('#ionic-comments-container').append(response.comments);

                    IonicPage.lastCommentId = response.last_comment;
                }

                elem.prop('disabled', false);
            }, 'json');
        });
    }
};

$(function() {
    IonicPage.csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('.shoutbox-refresh').click(function(){
        if (!$(this).prop('disabled'))
        {
            IonicPage.refreshShoutbox();
        }
    });

    $('.shoutbox-delete').click(function(){
        var id = $(this).prop('id').replace('shoutbox-delete-', '');

        IonicPage.deleteShoutboxPost(id);
    });

    $('.shoutbox-submit').click(function(){
        if ($(this).prop('disabled')) return;

        var post = $('#shoutbox-user-input').val();
        $('#shoutbox-user-input').val('');

        if (post)
        {
            IonicPage.addShoutboxPost(post);
        }
    });

    $('#ionic-notifications span').click(function() {
        $('#ionic-notifications-list').slideToggle('slow');
    });

    $('.ionic-delete-notification').click(function() {
        var id = $(this).prop('id').replace('notification-id-', '');
        var parent = $(this).parent();
        $.post(IONIC_BASE_URL+'users/delete_notification/'+id, {csrf_token: IonicPage.csrfToken}, function(response) {
            if (response.status)
            {
                parent.remove();

                if ($('#ionic-notifications-list li').length <= 0)
                {
                    $('#ionic-notifications').remove();
                }
            }
        }, 'json');
    });

    $('.cc-cookie-accept').click(function() {
        $.cookie("cookie_accept", "cookie_accept", {
            expires: 365,
            path: '/'
        });

        $(".cc-cookies").fadeOut();
    });

    if ($.datepicker != undefined)
    {
        $.datepicker.regional['pl'] = {
            closeText: 'Zamknij',
            prevText: '&#x3c;Poprzedni',
            nextText: 'Następny&#x3e;',
            currentText: 'Dziś',
            monthNames: ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
            'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'],
            monthNamesShort: ['Sty','Lu','Mar','Kw','Maj','Cze',
            'Lip','Sie','Wrz','Pa','Lis','Gru'],
            dayNames: ['Niedziela','Poniedziałek','Wtorek','Środa','Czwartek','Piątek','Sobota'],
            dayNamesShort: ['Nie','Pn','Wt','Śr','Czw','Pt','So'],
            dayNamesMin: ['N','Pn','Wt','Śr','Cz','Pt','So'],
            weekHeader: 'Tydz',
            dateFormat: 'dd.mm.yy',
            firstDay: 1,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ''};
        $.datepicker.setDefaults($.datepicker.regional['pl']);
    }
});