var cart = {
    request_url : '/cart/',
    add : function(gid, number) { //添加物品
        var callbackFunc = arguments.length>2?arguments[2]:function(){};
        this.request('add', {gid:gid, number:number}, callbackFunc);
    },
    del : function (gid) { //删除物品
        var callbackFunc = arguments.length>1?arguments[1]:function(){};
        this.request('delete', {gid:gid}, callbackFunc);
    }, 
    decrease : function (gid, number) { //减少物品
        var callbackFunc = arguments.length>2?arguments[2]:function(){};
        this.request('decrease', {gid:gid, number:number}, callbackFunc);
    } , 
    increase : function (gid, number) { //减少物品
        var callbackFunc = arguments.length>2?arguments[2]:function(){};
        this.request('increase', {gid:gid, number:number}, callbackFunc);
    },
    update : function (gid, number) { //更新物品数量
        var callbackFunc = arguments.length>2?arguments[2]:function(){};
        this.request('update', {gid:gid, number:number}, callbackFunc);
    } , 
    clear : function () { //清除购物车
        var callbackFunc = arguments.length>0?arguments[0]:function(){};
        this.request('clear', {}, callbackFunc);
    },
    refresh : function() {
        var callbackFunc = arguments.length>0?arguments[0]:function(){};
        this.request('info', {}, callbackFunc);
    },
    request : function (action, data, cb) {
        var o = this;
        if (action != 'info'){
            try {
                loading.show();
            } catch (e) {}
        }    
        
        $.post(this.request_url + action, data, function(json){
            try {
                loading.hide();
            } catch (e) {}
            o.callback(json);
            if (typeof(cb) == 'function'){
                cb(json);
            }
        }, 'json');
    },
    callback : function (data){
        if (data.error) {
            dialog.alert(data.msg);
        } else {
            //刷新顶部的购物点了
            var result = data.result;
            try {
                if (result.html != 'undefined'){
                    $('#cart-body').html(result.html);
                }
            } catch (e) {
                //alert(e.message);
            }
        }
    }
};

function removeCartItem(gid) {
    if (confirm("Are you sure you would like to remove this item from the shopping cart?")) {
        cart.del(gid, function(data){
            if (data.error) {
                
            } else {
                dialog.alert('Success to remove!');
                var result = data.result;
                if (typeof(result.fresh) != 'undefined') {
                    location.reload();
                }
            }
        });
    }
}

var loading = {
    show : function (){
        $('#loading-bg').show();
        $('#loading').show();
    },
    hide : function () {
        $('#loading-bg').hide();
        $('#loading').hide();
    }
};

var dialog = {
    owner : $('#dialog-modal'),
    content : $('#dialog-modal-content'),
    init : function (){
        try {
            $('#dialog-modal').dialog({autoOpen: false, modal: true, resizable: false, buttons:[{
                text: "Ok",
                click: function() { $(this).dialog("close"); }
            }]});
        } catch (e){}
    },
    alert : function(msg) {
        var callbackFunc = arguments.length>1?arguments[1]:function(){};
        var callbackArgs = arguments.length>2?arguments[2]:{};
        
        $('#dialog-modal-content').html(msg);
        $('#dialog-modal').dialog( "option", "title", 'Message' ).dialog('open');
    } 
};
var success_dialog = {
    init : function (){
        try {
            $('#dialog-success').dialog({width:"380px", autoOpen: false, modal: true, resizable: false, buttons:[{
                text: "Continue shopping",
                click: function() { $(this).dialog("close"); }
            }, {text:'View cart & checkout', click:function(){goToCheckOut();}}]});
        } catch (e){console.log(e.message);}
    },
    pop : function (html) {
        $('#dialog-success-content').html(html);
        $('#dialog-success').dialog('open');
    }
}