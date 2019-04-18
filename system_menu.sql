/*
 Navicat MySQL Data Transfer

 Source Server         : 本地
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : localhost:3306
 Source Schema         : motion

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 18/04/2019 18:18:42
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for system_menu
-- ----------------------------
DROP TABLE IF EXISTS `system_menu`;
CREATE TABLE `system_menu`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父id',
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `node` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '节点代码',
  `icon` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '菜单图标',
  `url` varchar(400) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '链接',
  `params` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '链接参数',
  `target` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '_self' COMMENT '链接打开方式',
  `sort` int(11) UNSIGNED NULL DEFAULT 0 COMMENT '菜单排序',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0:禁用,1:启用)',
  `create_by` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_system_menu_node`(`node`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 72 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统菜单表' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of system_menu
-- ----------------------------
INSERT INTO `system_menu` VALUES (1, 0, '系统设置', '', '', '#', '', '_self', 9000, 1, 10000, '2018-01-19 15:27:00');
INSERT INTO `system_menu` VALUES (2, 10, '后台菜单', '', 'fa fa-leaf', 'admin/menu/index', '', '_self', 10, 1, 10000, '2018-01-19 15:27:17');
INSERT INTO `system_menu` VALUES (3, 10, '系统参数', '', 'fa fa-modx', 'admin/config/index', '', '_self', 20, 1, 10000, '2018-01-19 15:27:57');
INSERT INTO `system_menu` VALUES (4, 11, '访问授权', '', 'fa fa-group', 'admin/auth/index', '', '_self', 20, 1, 10000, '2018-01-22 11:13:02');
INSERT INTO `system_menu` VALUES (5, 11, '用户管理', '', 'fa fa-user', 'admin/user/index', '', '_self', 10, 1, 0, '2018-01-23 12:15:12');
INSERT INTO `system_menu` VALUES (6, 11, '访问节点', '', 'fa fa-fort-awesome', 'admin/node/index', '', '_self', 30, 1, 0, '2018-01-23 12:36:54');
INSERT INTO `system_menu` VALUES (7, 0, '后台首页', '', '', 'admin/index/main', '', '_self', 1000, 1, 0, '2018-01-23 13:42:30');
INSERT INTO `system_menu` VALUES (8, 16, '系统日志', '', 'fa fa-code', 'admin/log/index', '', '_self', 10, 1, 0, '2018-01-24 13:52:58');
INSERT INTO `system_menu` VALUES (9, 10, '文件存储', '', 'fa fa-stop-circle', 'admin/config/file', '', '_self', 30, 1, 0, '2018-01-25 10:54:28');
INSERT INTO `system_menu` VALUES (10, 1, '系统管理', '', '', '#', '', '_self', 200, 1, 0, '2018-01-25 18:14:28');
INSERT INTO `system_menu` VALUES (11, 1, '访问权限', '', '', '#', '', '_self', 300, 1, 0, '2018-01-25 18:15:08');
INSERT INTO `system_menu` VALUES (16, 1, '日志管理', '', '', '#', '', '_self', 400, 1, 0, '2018-02-10 16:31:15');
INSERT INTO `system_menu` VALUES (17, 0, '微信管理', '', '', '#', '', '_self', 8000, 1, 0, '2018-03-06 14:42:49');
INSERT INTO `system_menu` VALUES (18, 17, '公众号配置', '', '', '#', '', '_self', 0, 1, 0, '2018-03-06 14:43:05');
INSERT INTO `system_menu` VALUES (19, 18, '微信授权绑定', '', 'fa fa-cog', 'wechat/config/index', '', '_self', 0, 1, 0, '2018-03-06 14:43:26');
INSERT INTO `system_menu` VALUES (20, 18, '关注默认回复', '', 'fa fa-comment-o', 'wechat/keys/subscribe', '', '_self', 0, 1, 0, '2018-03-06 14:44:45');
INSERT INTO `system_menu` VALUES (21, 18, '无反馈默认回复', '', 'fa fa-commenting', 'wechat/keys/defaults', '', '_self', 0, 1, 0, '2018-03-06 14:45:55');
INSERT INTO `system_menu` VALUES (22, 18, '微信关键字管理', '', 'fa fa-hashtag', 'wechat/keys/index', '', '_self', 0, 1, 0, '2018-03-06 14:46:23');
INSERT INTO `system_menu` VALUES (23, 17, '微信服务定制', '', '', '#', '', '_self', 0, 1, 0, '2018-03-06 14:47:11');
INSERT INTO `system_menu` VALUES (24, 23, '微信菜单管理', '', 'fa fa-gg-circle', 'wechat/menu/index', '', '_self', 0, 1, 0, '2018-03-06 14:47:39');
INSERT INTO `system_menu` VALUES (25, 23, '微信图文管理', '', 'fa fa-map-o', 'wechat/news/index', '', '_self', 0, 1, 0, '2018-03-06 14:48:14');
INSERT INTO `system_menu` VALUES (26, 17, '微信粉丝管理', '', 'fa fa-user', '#', '', '_self', 0, 1, 0, '2018-03-06 14:48:33');
INSERT INTO `system_menu` VALUES (27, 26, '微信粉丝列表', '', 'fa fa-users', 'wechat/fans/index', '', '_self', 20, 1, 0, '2018-03-06 14:49:04');
INSERT INTO `system_menu` VALUES (28, 26, '微信黑名单管理', '', 'fa fa-user-times', 'wechat/fans_block/index', '', '_self', 30, 1, 0, '2018-03-06 14:49:22');
INSERT INTO `system_menu` VALUES (29, 26, '微信标签管理', '', 'fa fa-tags', 'wechat/tags/index', '', '_self', 10, 1, 0, '2018-03-06 14:49:39');
INSERT INTO `system_menu` VALUES (32, 0, '商城管理', '', '', '#', '', '_self', 2000, 0, 0, '2018-03-20 16:46:07');
INSERT INTO `system_menu` VALUES (33, 32, '商品管理', '', '', '#', '', '_self', 0, 0, 0, '2018-03-20 16:46:21');
INSERT INTO `system_menu` VALUES (34, 33, '产品管理', '', 'fa fa-modx', 'store/goods/index', '', '_self', 0, 0, 0, '2018-03-20 16:46:45');
INSERT INTO `system_menu` VALUES (35, 33, '规格管理', '', 'fa fa-hashtag', 'store/goods_spec/index', '', '_self', 0, 0, 0, '2018-03-20 16:47:08');
INSERT INTO `system_menu` VALUES (36, 33, '分类管理', '', 'fa fa-product-hunt', 'store/goods_cate/index', '', '_self', 0, 0, 0, '2018-03-20 16:47:50');
INSERT INTO `system_menu` VALUES (37, 33, '品牌管理', '', 'fa fa-scribd', 'store/goods_brand/index', '', '_self', 0, 0, 0, '2018-03-20 16:48:05');
INSERT INTO `system_menu` VALUES (38, 32, '订单管理', '', '', '#', '', '_self', 0, 0, 0, '2018-04-21 15:07:36');
INSERT INTO `system_menu` VALUES (39, 38, '订单列表', '', 'fa fa-adjust', 'store/order/index', '', '_self', 0, 0, 0, '2018-04-21 15:07:54');
INSERT INTO `system_menu` VALUES (40, 32, '商城配置', '', '', '#', '', '_self', 0, 0, 0, '2018-04-21 15:08:17');
INSERT INTO `system_menu` VALUES (41, 40, '参数管理', '', '', '#', '', '_self', 0, 0, 0, '2018-04-21 15:08:25');
INSERT INTO `system_menu` VALUES (42, 40, '快递公司', '', 'fa fa-mixcloud', 'store/express/index', '', '_self', 0, 0, 0, '2018-04-21 15:08:50');
INSERT INTO `system_menu` VALUES (43, 0, '编写计划', '', '', '#', '', '_self', 3000, 1, 0, '2018-11-18 20:30:42');
INSERT INTO `system_menu` VALUES (44, 43, '动作库管理', '', '', '#', '', '_self', 0, 1, 0, '2018-11-18 20:33:12');
INSERT INTO `system_menu` VALUES (45, 44, '动作库列表', '', 'fa fa-child', 'motion/motion/index', '', '_self', 0, 1, 0, '2018-11-18 20:33:36');
INSERT INTO `system_menu` VALUES (46, 44, '动作库类型', '', 'fa fa-square-o', 'motion/motion_type/index', '', '_self', 0, 1, 0, '2018-11-20 20:52:54');
INSERT INTO `system_menu` VALUES (50, 43, '会员管理', '', '', '#', '', '_self', 0, 1, 0, '2018-11-22 16:17:37');
INSERT INTO `system_menu` VALUES (51, 50, '会员列表', '', '', 'motion/member/index', '', '_self', 0, 1, 0, '2018-11-22 16:19:57');
INSERT INTO `system_menu` VALUES (52, 43, '教练管理', '', '', '#', '', '_self', 0, 1, 0, '2018-11-22 16:35:42');
INSERT INTO `system_menu` VALUES (53, 52, '教练列表', '', '', 'motion/coach/index', '', '_self', 0, 1, 0, '2018-11-22 16:35:54');
INSERT INTO `system_menu` VALUES (56, 43, '备课管理', '', '', '#', '', '_self', 0, 1, 0, '2018-11-23 15:10:38');
INSERT INTO `system_menu` VALUES (57, 56, '会员管理', '', '', 'motion/lesson/index', '', '_self', 0, 1, 0, '2018-11-23 15:10:53');
INSERT INTO `system_menu` VALUES (58, 56, '我的会员', '', '', 'motion/lesson/my', '', '_self', 0, 1, 0, '2018-11-25 10:46:23');
INSERT INTO `system_menu` VALUES (59, 43, '留言管理', '', '', '#', '', '_self', 0, 0, 0, '2018-11-28 11:42:34');
INSERT INTO `system_menu` VALUES (60, 59, '全部留言', '', '', 'motion/message/index', '', '_self', 0, 0, 0, '2018-11-28 11:43:18');
INSERT INTO `system_menu` VALUES (62, 56, '批量备课', '', '', '/motion/lesson/batch', '', '_self', 0, 1, 0, '2019-03-02 18:15:23');
INSERT INTO `system_menu` VALUES (63, 0, '私教管理', '', '', '#', '', '_self', 0, 1, 0, '2019-04-10 14:11:04');
INSERT INTO `system_menu` VALUES (64, 63, '会员管理', '', '', '#', '', '_self', 0, 1, 0, '2019-04-10 14:11:54');
INSERT INTO `system_menu` VALUES (65, 64, '会员列表', '', '', 'pt/member/index', '', '_self', 0, 1, 0, '2019-04-10 14:14:12');
INSERT INTO `system_menu` VALUES (66, 63, '项目管理', '', '', '#', '', '_self', 0, 1, 0, '2019-04-12 14:08:11');
INSERT INTO `system_menu` VALUES (67, 66, '项目列表', '', '', 'pt/product/index', '', '_self', 0, 1, 0, '2019-04-12 14:13:35');
INSERT INTO `system_menu` VALUES (68, 63, '课程管理', '', '', '#', '', '_self', 0, 1, 0, '2019-04-12 15:37:50');
INSERT INTO `system_menu` VALUES (69, 68, '团课列表', '', '', 'pt/course/index', '', '_self', 0, 1, 0, '2019-04-12 15:38:27');
INSERT INTO `system_menu` VALUES (70, 68, '课程日程', '', '', 'pt/classes/schedule', '', '_self', 0, 1, 0, '2019-04-12 15:51:23');
INSERT INTO `system_menu` VALUES (71, 68, '课程列表', '', '', 'pt/classes/index', '', '_self', 0, 1, 0, '2019-04-18 13:52:18');

SET FOREIGN_KEY_CHECKS = 1;
