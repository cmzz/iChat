;(function($) {
    Date.prototype.format = function(format){ //author: meizz
        var o = {
            "M+" : this.getMonth()+1, //month
            "d+" : this.getDate(),    //day
            "h+" : this.getHours(),   //hour
            "m+" : this.getMinutes(), //minute
            "s+" : this.getSeconds(), //second
            "q+" : Math.floor((this.getMonth()+3)/3),  //quarter
            "S" : this.getMilliseconds() //millisecond
        }
        if(/(y+)/.test(format)) format=format.replace(RegExp.$1,
            (this.getFullYear()+"").substr(4 - RegExp.$1.length));
        for(var k in o)if(new RegExp("("+ k +")").test(format))
            format = format.replace(RegExp.$1,
                RegExp.$1.length==1 ? o[k] :
                    ("00"+ o[k]).substr((""+ o[k]).length));
        return format;
    }

    var keepalive = function ( ws ){
        var time = new Date();
        if($.iCaht.opt.last_health != -1 && ( time.getTime() - $.iCaht.opt.last_health > $.iCaht.opt.health_timeout ) ){
            //此时即可以认为连接断开，可设置重连或者关闭连接
            $("#keeplive_box").html( "服务器没有响应." ).css({"color":"red"});
            //ws.close();
        }
        else{
            $("#keeplive_box").html( "连接正常" ).css({"color":"green"});
            if( ws.bufferedAmount == 0 ){
                ws.send( '~H#C~' );
            }
        }
    }

    var defaults = {
        "button"    : "#btn-send",
        "showbox"   : "#msgbox",
        "inputbox"  : "#inputbox",
        "server"    : "ws://192.168.2.129:8808",
        'last_health':0,
        'heartbeat_timer': 0

    }

    $.iCaht = {
        opt: {},

        //初使化
        init: function(options) {
            this.opt = $.extend(defaults,options);
            this.bind();


            if(!this.opt.server) {
                this.log("请设置服务器")
                return false;
            }

            this.opt.ws = new ReconnectingWebSocket(this.opt.server)
            this.opt.ws.onopen = function () {
                $($.iCaht.opt.showbox).append(new Date().format("hh:mm:ss") + " : 欢迎进入iChat聊天室！ <br />");
                $.iCaht.opt.heartbeat_timer = setInterval( function(){keepalive($.iCaht.opt.ws)}, 1000 );
            }
            this.opt.ws.onmessage = function(event) {
                console.log('Client received a message',event.data);
                $($.iCaht.opt.showbox).append(event.data + "<br />")
            };
            this.opt.ws.onclose = function(event) {
                console.log('Client notified socket has closed',event);
            };

            return this;
        },

        //发送消息
        send: function(data) {
            this.opt.ws.send(data)
            return this;
        },

        //回复处理
        receive: function() {


            return this;
        },

        //绑定发送消息事件
        bind: function () {
            if(this.opt.button) {
                $(this.opt.button).on("click", function () {

                    $.iCaht.send($($.iCaht.opt.inputbox).val());
                })
            }
        },

        log: function(data) {
            console.log(data);
        }
    }

})(jQuery);