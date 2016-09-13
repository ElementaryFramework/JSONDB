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
            wrapper: 'section#main',
            menu: 'nav.menu',
            breakPoint: 1024,
            animation: 'slide-left'
        });

        $.grid.scrollTo({
            scroller: ".scrollTo",
            speed: 2000
        });

        var AffixScrollSpy = $.grid.affix({
            element: '.scrollspy',
            offset: {
                top: function () {
                    return this.top = $("article.manual-content").offset().top - $("header#header").height();
                },
                bottom: function () {
                    return this.bottom = $('html').outerHeight(!0) - $('#scrollspy-limit').offset().top - $("header#header").height();
                }
            }
        });

        var AffixClassList = $.grid.affix({
            element: '.class-list',
            offset: {
                top: function(){
                    return this.top = 1;
                },
                bottom: function(){
                    return this.bottom = $('footer#footer').outerHeight(!0);
                }
            }
        });

        $window.on('resize', function() {
            AffixClassList
                .affix('setBottom', $('footer#footer').outerHeight(!0))
                .affix('checkPosition');
            AffixScrollSpy
                .affix('setTop', $("article.manual-content").offset().top - $("header#header").height())
                .affix('setBottom', $('html').outerHeight(!0) - $('#scrollspy-limit').offset().top - $("header#header").height())
                .affix('checkPosition');
        });

        $window.on('scroll', function() {
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