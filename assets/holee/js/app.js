/**
 * app js
 */
function changeCurrency(code){
    var url = '/ajax/set-currency';
    $.getJSON(url, {currency:code}, function(json){
        location.reload();
    });
}

$(function() {
    $(document).on('change', '#currency_code', function(){
        changeCurrency($(this).val());
    });

    $(document).on('click', '.currency-list a[data-code], .currency-list-mobile a[data-code]', function(){
        changeCurrency($(this).data('code'));
    });
});

$(document).ready(function(){
    // currency
    $.getJSON('/ajax/get-currency-area', function(json){
        if (json.error) {
            // 
        } else {
            $('#currency-area').html(json.result);
        }
    })
    //cart num
    $.getJSON('/cart/info', function(json){
        if (json.error) {
            // 
        } else {
            var num = json.result.num, extra = json.extra;
            $('#cart-num').html(num);
            $('#cart-num-mobile').html(num);
            if (extra) {
                if (extra.show_status == 3) {
                    // show the sidebar
                    $('#static-sidebar-main,#static-sidebar-mobile,#static-sidebar-btns').removeClass('hidden');
                    // first time show, send ga event
                    if (extra.first_time_show > 0 || 1) {
                        try {
                            ga('send', 'event', 'static_sidebar', 'show', '', {nonInteraction: true});
                        } catch (e){}
                    }
                }
            }
        }
    });

});

var loadingMask = {
    open : function() {
        var text = "Loading...";
        if (arguments.length > 0) {
            text = arguments[0];
        }

        $('#loading-text').html(text);
        $('#loading-mask').show();
    },
    close : function() {
        $('#loading-mask').hide();
    },
    fadeOut : function() {
        $('#loading-mask').fadeOut();
    }
};

var modalDialog = {
    timer : null,
    pop : function(text) {
        $('#modal-content').html(text);
        if (arguments.length > 1) {
            // auto close
            if (this.timer) {
                clearTimeout(this.timer);
            }

            this.timer = setTimeout(function(){modalDialog.close();}, arguments[1]);
        }

        $('#modal-dialog').fadeIn();
    },
    close : function() {
        // clear timer
        if (this.timer) {
            clearTimeout(this.timer);
        }

        // clsoe
        $('#modal-dialog').fadeOut();
    }
};

var adPoper = {
    init : function(){
        $('#ad-poper-btn-close').on('click', function(){adPoper.close();return false;});
        $('#btn-pop-continue').on('click', function(){adPoper.close();return false;});
        $('.pop-ad').on('click', function(){adPoper.show();return false;});
        $('#ad-poper .ad-the-code[data-code]').on('click', function(){
            var val = $(this).data('code');
            if ($('#coupon-code').length > 0){
                $('#coupon-code').val(val);
                $('#coupon-code-mobile').val(val);
                $('#code-container-mobile').show();
                adPoper.close();
            }
        });
        $('#ad-poper').on('click', function(){
            return false;
        });
    },
    show : function(){
        $("#ad-poper").show();
        $('body').one('click tap', function(){
            adPoper.close();
        });
    },
    close : function(){
        //console.log('close');
        $("#ad-poper").fadeOut('fast');
    }
};

$(document).ready(function(){
    $('#subscription-modal').on('show.bs.modal', function (e) {
        var $this = $(this);
        var $modal_dialog = $this.find('.modal-dialog');
        // 关键代码，如没将modal设置为 block，则$modala_dialog.height() 为零
        $this.css('display', 'block');
        $modal_dialog.css({'margin-top': Math.max(0, ($(window).height() - $modal_dialog.height()) / 2) });
    });
    $("#get-my-discounted").on('click', function(){
        var url = '/ajax/get-my-discounted';
        var first_name=$("#first-name").val();
        var email=$("#email").val();
        var privacy_policy=$("#privacy-policy");
        if(privacy_policy.is(':checked')==false){
            $("#privacy-policy-notice").html('<span style="color: red;">This is a required field.</span>');
            return;
        }
        loadingMask.open();
        $.getJSON(url, {first_name:first_name,email:email}, function(json){
            loadingMask.close();
            var result=json.result;
            if(result.err==0){
                $('#subscription-content').html(result.content);
                $('#subscription-modal').modal('show');
            }else{
                alert(result.msg)
            }
        });
    });
    $("#get-my-discounted-1").on('click', function(){
        var url = '/ajax/get-my-discounted';
        var first_name=$("#first-name-1").val();
        var email=$("#email-1").val();
        var privacy_policy=$("#privacy-policy-1");
        if(privacy_policy.is(':checked')==false){
            $("#privacy-policy-notice-1").html('<span style="color: red;">This is a required field.</span>');
            return;
        }
        loadingMask.open();
        $.getJSON(url, {first_name:first_name,email:email}, function(json){
            loadingMask.close();
            var result=json.result;
            if(result.err==0){
                $('#subscription-content').html(result.content);
                $('#subscription-modal').modal('show');
            }else{
                alert(result.msg)
            }
        });
    });
    $('#btn-chat-closed').click(function(){
        $(this).hide();
        $('#btn-chat-opened').parent().show().animate({left: '+190px'}, "slow");
    });
    $('#btn-chat-opened').click(function(){
        $('#btn-chat-opened').parent().hide();
        $('#btn-chat-closed').show();
    });

    $('#btn-search-dropdown').on('click', function () {
        $('#search-area').slideToggle('fast');
    });

    $(window).scroll(function(){
        var clientHeight=window.screen.availHeight - 75;
        if ($(this).scrollTop() > clientHeight) {
            $('#top-scroll').fadeIn(1000);
        } else {
            $('#top-scroll').fadeOut(1000);
        }
    });
    // scroll-to-top animate
    $('#top-scroll').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 750);
        return false;
    });

    $(".scrolltop").on('click', function() {
        $("html, body").animate({ scrollTop: 0 }, 300);
    });

    $(".slide-btn[data-status]").on('click', function () {
        if ($(this).data('status') == 1) {
            $('#static-sidebar').addClass('open').addClass('mobile-open');
        } else {
            $('#static-sidebar').removeClass('open').removeClass('mobile-open');
        }
    });

    // page poper
    $(document).on('click', 'a[data-info]', function() {
        var key = $(this).data('info');
        if (!key) {
            return true;
        }
        if (key != $('#page-info-title').prop('data-last')) {
            // request the data
            $.getJSON('/cms/load-page', {key : key}, function(json) {
                if (json.error) {
                    console.log('load page error:' + json.msg);
                    // alert(json.msg);
                } else {
                    var data = json.result;
                    $('#page-info-title').html(data.title).prop('data-last', data.url);
                    $('#page-info-content').html(data.content);
                    // pop
                    $('#page-info-modal').modal('show');
                }
            })
        } else {
            // just pop it
            $('#page-info-modal').modal('show');
        }
        return false;
    });

    /*
     $('input,select').focus(function(){
     $('#header').css('position','static');
     $('#footer').css('position','static');
     });
     $('input,select').blur(function(){
     $('#header').css('position','fixed');
     $('#footer').css('position','fixed');

     });
     */
    $(window).on('scroll', function(){
        if ($(document).scrollTop() > 40){
            $("#bar-logo-link").show();
        } else {
            $("#bar-logo-link").hide();
        }
    }).trigger('scroll');

    // adPoper.init();
});
