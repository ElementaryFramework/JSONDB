(function($){

    $(function(){

        var $window = $(window);

        GRID.init({
            breakPoints: {
                mobile: 480,
                tablet: 1024
            }
        });

        $.grid.menuToggle({
            menu: 'nav.menu',
            breakPoint: 768,
            animation: 'slide-left'
        });

        $.grid.scrollTo({
            scroller: ".scrollTo",
            speed: 2000
        });

        $.grid.affix({
            element: '.scrollspy',
            offset: {
                top: function(){
                    return this.top = $("article.manual-content").offset().top - $("header#header").height();
                },
                bottom: function(){
                    return this.bottom = $('html').height() - parseInt($('#scrollspy-limit').offset().top);
                }
            }
        }).affix('checkPosition');

        $window.on('scroll.docs.nav', function() {
            var scrollHeight = $window[0].scrollHeight;
            var scrollTop    = $window.scrollTop();

            $(".scrollspy a").each(function() {
                var $a = $(this);
                var id = $a.attr('href');
                var offset = $(id).offset().top - $("header#header").height();
                var $li = $a.closest('li[data-scrollspy-for="'+$a.attr('data-scrollspy-for')+'"]');
                if (scrollTop >= offset) {
                    $('.scrollspy a').removeClass('current');
                    $('.scrollspy li').removeClass('active');
                    $li.addClass('active');
                    $a.addClass('current');
                }
            });
        }).trigger('scroll.docs.nav');
    });

})(jQuery);