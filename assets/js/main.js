(function($){

    $(function(){

        var $window = $(window),
            $body   = $(document.body),
            $header = $('body.homepage .menu-header'),
            $banner = $('#banner');

        GRID.init({
            breakPoints: {
                mobile: 480,
                tablet: 1024
            }
        });

        $.grid.dropdown({
            menu: 'nav.menu',
            animation: 'fade'
        });

        $.grid.menuToggle({
            menu: 'nav.menu',
            breakPoint: 1024,
            animation: 'slide-left'
        });

        $.grid.scrollwatch({
            element: '#header-wrapper',
            anchor: 'top',
            offset: 0,
            on: function() {
                $header.removeClass('reveal');
                $header.addClass('alt');
                $('#header-wrapper').addClass('menu-alted');
            },
            off: function() {
                $header.removeClass('alt');
                $header.addClass('reveal');
                $('#header-wrapper').removeClass('menu-alted');
            }
        });

        $.grid.slider({
            wrapper: '#slider-wrapper',
            timeOut: 5000,
            animSpeed: 1000,
            animType: 'fade'
        });

        $('.slider-transition-chooser').change(function() {
            var trans  = $(this).val();
            var Slider = $('#slider-wrapper').data('grid.slider');
            if (Slider) {
                Slider.options.animType = trans;
                Slider.start();
            }
            console.log($('#slider-wrapper').find('.simpleSlider-slide'));
        });

        var empty = function () {
            var Progress = $('#fullandempty').data('grid.progress');
            Progress.set_color('#c00');
            Progress.set_width('0%');
            Progress.animate();
        };

        var full = function () {
            var Progress = $('#fullandempty').data('grid.progress');
            Progress.set_color('#060');
            Progress.set_width('100%');
            Progress.animate();
        };

        $.grid.progress({
            bar: '#fullandempty',
            width: "100%",
            delay: 2000,
            color: "#060",
            onFull: empty,
            onEmpty: full
        });

        $.grid.tooltip({
            element: '#tooltip-bottom',
            position: 'bottom',
            content: 'This is a simple tooltip !'
        });

        $.grid.tooltip({
            element: '#tooltip-top',
            position: 'top',
            content: 'This is a simple tooltip !'
        });

        $.grid.tooltip({
            element: '#tooltip-left',
            position: 'left',
            content: 'This is a simple tooltip !'
        });

        $.grid.tooltip({
            element: '#tooltip-right',
            position: 'right',
            content: 'This is a simple tooltip !'
        });

        $.grid.tooltip({
            element: '#tooltip-multi',
            multiple: true,
            position: 'right',
            content: 'This is a simple tooltip !'
        }).tooltip({
            position: 'top',
            multiple: true,
            content: 'This is a simple tooltip !'
        }).tooltip({
            position: 'left',
            multiple: true,
            content: 'This is a simple tooltip !'
        }).tooltip({
            position: 'bottom',
            multiple: true,
            content: 'This is a simple tooltip !'
        });

        $.grid.tooltip({
            element: '#tooltip-html',
            position: 'bottom',
            contentAsHTML: true,
            content: '<div class="grid" style="width: 250px"><div class="g4"><span class="image fit"><img src="./images/pic01.jpg" /></span></div><div class="g8">Commodo id natoque malesuada sollicitudin elit suscipit.</div></div>'
        });

        $.grid.alert({
            container: "#scrollTest",
            text: 'Exited !! :-(',
            type: "warning",
            speed: 100,
            cleanBefore: true,
            hideAfter: false
        });

        var onCarousel = function() {
            var Alert = $('#scrollTest').data('grid.alert');
            Alert.set_text('Entered !! :-)');
            Alert.set_type('success');
            Alert.set_anim('shake-vertical');
            Alert.set_intensity('10px');
            Alert.show();
        };

        var offCarousel = function() {
            var Alert = $('#scrollTest').data('grid.alert');
            Alert.set_text('Exited !! :-(');
            Alert.set_type('warning');
            Alert.set_anim('shake-horizontal');
            Alert.show();
        };

        $.grid.scrollwatch({
            element: ".reel",
            anchor:  "bottom",
            on: onCarousel,
            off: offCarousel
        });

        $.grid.carousel({
            carousel: '.carousel'
        });

        $.grid.panels({
            wrapper: ".panels",
        });

        $.grid.alert({
            container: "#alert-wrapper",
            text: "Just choose what type of alert you want to show !",
            cleanBefore: true,
            withClose: true,
            hideAfter: false
        });

        $("#show-info").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $("#alert-wrapper").data('grid.alert')
                .set_text("Do you know ? This site is entirely designed with G.R.I.D !")
                .set_type("info")
                .set_speed(500)
                .set_anim('fade')
                .show();
        });

        $("#show-success").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $("#alert-wrapper").data('grid.alert')
                .set_text("You have successfully showed this notification ! You can continue now...")
                .set_type("success")
                .set_speed(100)
                .set_anim('shake-horizontal')
                .show();
        });

        $("#show-warning").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $("#alert-wrapper").data('grid.alert')
                .set_text("Hey RUN !! In 5 minutes, your pc/tablet/smartphone will BOOM !!!")
                .set_type("warning")
                .set_speed(100)
                .set_intensity("10px")
                .set_anim('shake-vertical')
                .show();
        });

        $("#show-loading").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $("#alert-wrapper").data('grid.alert')
                .set_text("Wait the time for me to drink a coffee...")
                .set_type("loading")
                .set_speed(500)
                .set_anim('fade')
                .show();
        });

        $("#shakeBoxH").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $("#shake").data('grid.shakeIt')
                ? $("#shake").data('grid.shakeIt').set_anim('horizontal').set_intensity('20px').shake()
                : $.grid.shakeIt({
                    element: "#shake",
                    animation: "horizontal",
                    speed: 100,
                    intensity: "20px"
                });
        });

        $("#shakeBoxV").click(function(e){
            e.preventDefault();
            $("#shake").data('grid.shakeIt')
                ? $("#shake").data('grid.shakeIt').set_anim('vertical').set_intensity('10px').shake()
                : $.grid.shakeIt({
                    element: "#shake",
                    animation: "vertical",
                    speed: 100,
                    intensity: "10px"
                });
        });

        $("#shakePage").click(function(e){
            e.preventDefault();
            $("#page").data('grid.shakeIt')
                ? $("#page").data('grid.shakeIt').set_anim('horizontal').set_intensity('20px').shake()
                : $.grid.shakeIt({
                    element: "#page",
                    animation: "horizontal",
                    speed: 100,
                    intensity: "20px"
                });
        });

        $("#shakeDev").click(function(e){
            e.preventDefault();
            $("#alert-shake").data('grid.alert')
                ? $("#alert-shake").data('grid.alert').show()
                : $.grid.alert({
                    container: "#alert-shake",
                    text: "Are you serious ??",
                    type: "warning",
                    anim: "fade",
                    speed: 500,
                    cleanBefore: true,
                    hideAfter: true,
                    timeOut: 2000
                });
        });

        $.grid.scrollTo({
            scroller: ".scrollTo",
            speed: 2000
        });

        $.grid.affix({
            element: '.scrollspy',
            offset: {
                top: function(){
                    return this.top = $('.menu-header').height();
                },
                bottom: function(){
                    return this.bottom = $("#footer-wrapper").outerHeight(!0);
                }
            }
        }).affix('checkPosition');

        $window.on('scroll.docs.nav', function() {
            var scrollHeight = $window[0].scrollHeight;
            var scrollTop    = $window.scrollTop();
            var maxScroll    = scrollHeight - $window.height()

            $(".scrollspy a").each(function() {
                var $a = $(this);
                var id = $a.attr('href');
                var offset = $(id).offset().top;
                var $li = $a.closest('li[data-scrollspy-for="'+$a.attr('data-scrollspy-for')+'"]');
                if (scrollTop >= offset-1) {
                    $('.scrollspy a').removeClass('current');
                    $('.scrollspy li').removeClass('active');
                    $li.addClass('active');
                    $a.addClass('current');
                }
            });
        }).trigger('scroll.docs.nav');
    });

})(jQuery);