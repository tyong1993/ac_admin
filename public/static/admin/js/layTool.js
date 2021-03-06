function LayTool () {
    var version = 'v1.0';
}

LayTool.prototype.layerIndex = null;

// iframe 弹层
LayTool.prototype.open = function (url, title, width, height) {
    layui.use('layer', function () {
        var layer = layui.layer,
            $ = layui.$;

        if (!width) {
            width = '80%';
        }

        if (!height) {
            height = '80%';
        }

        layer.ready(function () {
            var index = layer.open({
                type: 2,
                title: title,
                shade: 0.3,//遮罩透明度
                maxmin:true,//最大化最小化
                shadeClose: true,//点击遮罩是否关闭
                area: [width, height],
                content: url
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
        });
    });
};

/**
 * confirm 弹层
 * icon:0感叹号,1勾,2叉,3问号,4锁,5哭,6笑
 */
LayTool.prototype.confirm = function (title,confirm,cancel,icon) {
    layui.use('layer', function () {
        var layer = layui.layer;
        if(!title){
            title = '确定要执行该操作?';
        }
        if(typeof icon == 'undefined'){
            icon = 3;
        }
        layer.confirm(title, {
            title: '友情提示',
            icon: icon,
            btn: ['确定', '取消']
        },confirm ,cancel);
    });
};

/**
 * alert 弹层
 * icon:0感叹号,1勾,2叉,3问号,4锁,5哭,6笑
 */
LayTool.prototype.alert = function (content, icon, title, closeBtn) {

    layui.use('layer', function () {
        var layer = layui.layer;

        if (!title) {
            title = '友情提示';
        }

        if (!icon) {
            icon = 1;
        }

        if (0 != closeBtn) {
            closeBtn = 1;
        }

        layer.alert(content, {
            title: title,
            icon: icon,
            closeBtn: closeBtn
        });
    });
};

/**
 * msg 弹层
 * icon:0感叹号,1勾,2叉,3问号,4锁,5哭,6笑
 */
LayTool.prototype.msg = function (content, icon, time) {
    var conf={
        icon:6,
        time:500
    };
    if (icon) {
        conf.icon = icon;
    }
    if (time) {
        conf.time = time;
    }
    layui.use('layer', function () {
        var layer = layui.layer;
        layer.msg(content, conf);
    });
};

// 加载中
LayTool.prototype.loading = function (type) {
    if (typeof type == 'undefined') {
        type = 2;
    }
    layui.use('layer', function () {
        var layer = layui.layer;
        layTool.layerIndex = layer.load(type);
    });
};

// 隐藏加载中
LayTool.prototype.hideLoading = function () {

    setTimeout(function () {
        layui.use('layer', function () {
            var layer = layui.layer;

            layer.close(layTool.layerIndex);
        });
    }, 100);
};

layTool = new LayTool();


