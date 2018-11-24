<?php

namespace app\wechat\controller;

use app\wechat\service\MediaService;
use think\Controller;
use think\Db;

/**
 * 微信素材预览
 * Class Review
 * @package app\wechat\controller
 */
class Review extends Controller
{

    /**
     * 显示手机预览
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $content = str_replace("\n", "<br>", $this->request->get('content', '', 'urldecode')); // 内容
        $type = $this->request->get('type', 'text'); // 类型
        // 图文处理
        if ($type === 'news' && is_numeric($content) && !empty($content)) {
            $news = MediaService::getNewsById($content);
            $this->assign('articles', $news['articles']);
        }
        // 文章预览
        if ($type === 'article' && is_numeric($content) && !empty($content)) {
            $article = Db::name('WechatNewsArticle')->where('id', $content)->find();
            if (!empty($article['content_source_url'])) {
                $this->redirect($article['content_source_url']);
            }
            $this->assign('vo', $article);
        }
        $this->assign('type', $type);
        $this->assign('content', $content);
        $this->assign($this->request->get());
        // 渲染模板并显示
        return $this->fetch();
    }

}
