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
        "showbox"   : "#msgbox ul",
        "inputbox"  : "#inputbox",
        "server"    : "ws://192.168.2.129:8808",
        'last_health':0,
        'heartbeat_timer': 0,
        'online_list' : ".onlinelists_inner ul",
        'online_selecter' : "",
        'online_num' : ".online_num",
        'onelin_loading': '.loading',
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

            this.opt.ws = new ReconnectingWebSocket(this.opt.server);

            this.opt.ws.onopen = function () {
                $.iCaht.showWelcomeMessage();
                $.iCaht.opt.heartbeat_timer = setInterval( function(){keepalive($.iCaht.opt.ws)}, 180000 );
                $.iCaht.send("cmd-getOnlineList:getOnlineList",true);
            }

            this.opt.ws.onmessage = this.receive;

            this.opt.ws.onclose = this.close;

            this.opt.ws.onerror = this.error;

            return this;
        },

        //关闭连接
        close : function(event) {

            console.log('Client notified socket has closed',event);
        },

        //连接错误
        error : function () {
            $.iCaht.parseMessage({type:2, message:':) 服务器连接失败～～'});
        },
        
        //发送消息
        send: function(data, noecho) {
            this.opt.ws.send(data);
            if(!noecho) {
                var inpubox = $($.iCaht.opt.inputbox);
                var val = inpubox.val();
                    inpubox.val('');
                $.iCaht.parseMessage({type:1, message:val});
            }
            return this;
        },

        //回复处理
        receive: function(event) {
            console.log(event);
            var msgobj = $.parseJSON(event.data);
            $.iCaht.parseMessage(msgobj);

            return this;
        },

        //解析服务器返回的消息
        //根据不同的类型处出处理
        parseMessage: function (msg) {
            //1000以上的为特殊消息，预留
            /**
             * 消息类型
             * 0 欢迎消息
             * 1 发送本地输出消息
             *
             * 100 普通对话消息
             * 101 私聊消息
             *
             * 200 系统消息iwz
             *
             * 1000 服务器推送的在线会员列表消息
             *      服务器会在用户时入频道及每隔300秒会推送在线列表及在线人数
             */

            var msgtype = {
                'welcome':0,
                'echomsg':1,
                'connect_error':2,
                'normal' : 100,
                'personal' :101,
                'sysnotice' :200,
                'onlinemember': 1000
            };


            //处理消息
            if(msg.type < 1000) {
                var m = {}
                m.showtime  = true; //是否显示发送时间
                m.message   = msg.message;//消息主体，可能是数组
                m.class     = "normal"; //消息的class属性
                m.head      = ""; //消息前缀
                m.from      = ""; //发送用户名
                m.to        = ""; //接收消息的用户名
                m.clear     = false; //显示消息前是否需要清理窗口

                switch (msg.type) {
                    case msgtype.welcome:
                        m.showtime = true;
                        m.class = "welcomemessage";
                        m.clear = true;

                        break;
                    case msgtype.connect_error:
                        m.showtime = true;
                        m.class = "error";
                        m.clear = true;
                        break;
                    case msgtype.echomsg:
                        m.from = "你";
                        m.class = "mymessage";
                        m.head = "对所有人说 : ";
                        break;

                    case msgtype.normal:
                        m.head = "对所有人说 : ";

                        break;
                    case msgtype.personal:

                        break;
                    case msgtype.sysnotice:

                        break;
                }

                var html  = '<li class="message_item '+ m.class +'">';
                if (m.showtime) {
                    html += '<span class="sendtime">[' + new Date().format("hh:mm:ss") + ']</span> ';
                }
                if (m.from) {
                    html += m.from;
                }
                if (m.head) {
                    html += m.head;
                }
                if (m.to) {
                    html += m.to;
                }
                html += "##message##";
                html += '</li>';

                if (m.message instanceof Array) {
                    var tmp = '';
                    for (var i in m.message) {
                        tmp += html.replace(/##message##/, m.message[i]);
                    }
                    html = tmp;
                } else {
                    html = html.replace(/##message##/, m.message);
                }

                $.iCaht.appendMsg(html, m.clear);
                m = {}

            }  else {
            //处理其它服务端的推送事件
                switch (msg.type) {
                    case msgtype.onlinemember:
                        $.iCaht.createOnlineMemberlistHtml(msg.message);

                        break;
                    default:

                        break;
                }
            }

        },

        createOnlineMemberlistHtml: function (l) {
            var html = '';
            $.each(l,function(n,e) {
                html += '<li>';
                html += '<img class="avatar" src="http://tp3.sinaimg.cn/1221788390/180/1289279591/0">';
                html += '<p class="username">'+ e.fd +'</p>';
                html += '</li>';
            });

            $($.iCaht.opt.onelin_loading).hide(400);
            $($.iCaht.opt.online_num).html(l.length);
            $($.iCaht.opt.online_list).html(html);

        },

        appendMsg: function ( msgString , clear) {
            var showbox = $($.iCaht.opt.showbox);
            if(clear) {
                showbox.html('');
            }
            showbox.append(msgString);
        },

        showWelcomeMessage : function () {
            var msg = {
                type:0,
                message: [
                    "欢迎进入QChat交友聊天室！",
                    "如果需要帮助请发送 help"
                ]
            }
            $.iCaht.parseMessage(msg);
        },

        //绑定发送消息事件
        bind: function () {
            if(this.opt.button) {
                $(this.opt.button).on("click", function () {
                    $.iCaht.send($($.iCaht.opt.inputbox).val());
                })
            }

            $(this.opt.inputbox).on("keydown", function(e) {
                if(e.ctrlKey && e.which == 13 || e.which == 10) {
                    $($.iCaht.opt.button).click();
                }
            })
        },

        log: function(data) {
            console.log(data);
        }
    }

})(jQuery);