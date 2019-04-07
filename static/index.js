$(function () {
    layui.use(['layer', 'upload'], function () {
        var layer = layui.layer;
        var upload = layui.upload;


        // 当前页面Bogy对象
        var $body = $('body');
        $.form = new function () {
            this.iframe = function (url, title) {
                if ($(window).height() > 800) {
                    var area = ['800px', '530px'];
                } else {
                    var area = ['90%', '90%'];
                }
                return layer.open({ title: title || '窗口', type: 2, area: area, fix: true, maxmin: false, content: url });
            }
        }


        /*! 注册 data-file 事件行为 */
        $body.on('click', '[data-file]', function () {
            var method = $(this).attr('data-file') === 'one' ? 'one' : 'mtl';
            var type = $(this).attr('data-type') || 'jpg,png', field = $(this).attr('data-field') || 'file';
            var title = $(this).attr('data-title') || '文件上传', uptype = $(this).attr('data-uptype') || '';
            var filetype = $(this).attr('data-filetype') || 0;
            var url = '/index.php/admin/plugs/upfile.html?mode=' + method + '&uptype=' + uptype + '&type=' + type + '&field=' + field + '&filetype=' + filetype;
            $.form.iframe(url, title || '文件管理');
        });

        $body.on('click', '[data-mobile-file]', function () {
            var method = $(this).attr('data-layui-file') === 'one' ? 'one' : 'mtl';
            var type = $(this).attr('data-type') || 'jpg,png', field = $(this).attr('data-field') || 'file';
            var title = $(this).attr('data-title') || '文件上传', uptype = $(this).attr('data-uptype') || '';
            var filetype = $(this).attr('data-filetype') || 0;
            var url = '/index.php/admin/plugs/upfileMobile.html?view=layui&mode=' + method + '&uptype=' + uptype + '&type=' + type + '&field=' + field + '&filetype=' + filetype;
            $.form.iframe(url, title || '文件管理');
            return;
        })

        /*! 注册 data-modal 事件行为 */
        $body.on('click', '[data-modal]', function () {
            var url = $(this).attr('data-modal');
            var title = $(this).attr('data-title') || '';
            var index = layer.open({
                type: 2,
                closeBtn :0,
                title: title,
                content: [url, 'no'] //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以
            });
            layer.full(index);

        });

    });



})