$(document).ready(function () {
    $(window).scroll(function () {
        if ($(this).scrollTop() > 10) {
            $(".head-menu").addClass("head-menu-active");
        }
        if ($(this).scrollTop() == 0) {
            $(".head-menu").removeClass("head-menu-active")
        }
        var clientHeight = window.screen.availHeight - 60;
        if ($(this).scrollTop() > clientHeight) {
            $('#top-scroll').fadeIn(800);
        } else {
            $('#top-scroll').fadeOut(800);
        }
    });
    // scroll-to-top animate
    $('#top-scroll').click(function () {
        $("html, body").animate({ scrollTop: 0 }, 750);
        return false;
    });
    //head menu
    $(".head-menucontent a").on("click", function () {
        $(this).addClass("active");
        $(this).siblings().removeClass("active");
    });
    //mobile menu
    $(".head-mobile-menu").on("click", function () {
        $(".head-menu-mobile").addClass("active");
        console.log("11")
    });
    $(".head-menu-close,.head-menulist-bg").on("click", function () {
        $(".head-menu-mobile").removeClass("active")
    });

    $(".head-tab a").on("click", function () {
        $(this).addClass("active");
        $(this).siblings().removeClass("active");
        var i = $(this).index();
        
        if (i == 1) {            
            loadStyles()
        }
        else {
            removeStyles();
        }
    })
    //引入ar样式
    function loadStyles() {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = 'css/style-ar.css';
        document.getElementsByTagName('head')[0].appendChild(link);
    }
    //移除ar样式
    function removeStyles() {
        var filename = 'css/style-ar.css';
        var targetelement = "link";
        var targetattr = "href";
        var allsuspects = document.getElementsByTagName(targetelement)
        for (var i = allsuspects.length; i >= 0; i--) {
            if (allsuspects[i] && allsuspects[i].getAttribute(targetattr) != null && allsuspects[i].getAttribute(targetattr).indexOf(filename) != -1) {
                allsuspects[i].parentNode.removeChild(allsuspects[i])
            }
        }
    }
});

