(function($){

    $(function(){

        var $window = $(window);

        GRID.init();

        $.grid.menuToggle({
            wrapper: 'section#main',
            menu: 'nav.menu',
            breakPoint: 1024,
            animation: 'slide-left',
            closeOnScroll: true
        });
        
        $('div.class-show-list').on('click', function () {
            $('section.class-desc').css('display', 'none');
            $('aside.class-list').css('display', 'block');
            $('div.class-hide-list').css('display', 'block');
        });

        $('div.class-hide-list').on('click', function () {
            $('div.class-hide-list').css('display', 'none');
            $('aside.class-list').css('display', 'none');
            $('section.class-desc').css('display', 'block');
        });

        $.grid.scrollTo({
            scroller: ".scrollTo",
            speed: 2000
        });

        $('.tooltip').each(function () {
            $(this).tooltip({
                contentAsHTML: true,
                position: $(this).attr('data-tooltip-position'),
                content: $(this).attr('data-tooltip-content')
            });
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
            AffixClassList && AffixClassList
                .affix('setBottom', $('footer#footer').outerHeight(!0))
                .affix('checkPosition');
            AffixScrollSpy && AffixScrollSpy
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
        }).trigger('scroll');
        
        GRID.onBreakPointChange('mobile', function () {
            $.grid.detach('.class-list', 'affix');
            $('aside.class-list').css('display', 'none');
            $('section.class-desc').css('display', 'block');
        });

        GRID.onBreakPointChange('tablet_narrow', function () {
            $('aside.class-list').css('display', 'none');
            $('section.class-desc').css('display', 'block');
        });

        GRID.onBreakPointChange('tablet_wide', function () {
            $('aside.class-list').css('display', 'block');
            $('section.class-desc').css('display', 'block');
            AffixScrollSpy = $.grid.affix({
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
            AffixClassList = $.grid.affix({
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
        });
    });

})(jQuery);