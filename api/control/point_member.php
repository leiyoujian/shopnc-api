<?php
/**
 * 积分中心个人操作
 *
 *
 *
 */


defined('InShopNC') or exit('Access Invalid!');

class point_memberControl extends apiMemberControl
{

    public function __construct()
    {
        parent::__construct();
        /**
         * 验证是否开启
         */
        if (C('pointprod_isuse') != '1') {
            @header('location: ' . SHOP_SITE_URL);
            die;
        }
    }

    /**
     *列表
     */
    public function giftsOp()
    {

        $model_pointprod = Model('pointprod');

        //展示状态
        $pgoodsshowstate_arr = $model_pointprod->getPgoodsShowState();
        //开启状态
        $pgoodsopenstate_arr = $model_pointprod->getPgoodsOpenState();
        $model_member = Model('member');
        //查询会员等级
        $membergrade_arr = $model_member->getMemberGradeArr();

        //查询兑换商品列表
        $where = array();
        $where['pgoods_show'] = $pgoodsshowstate_arr['show'][0];
        $where['pgoods_state'] = $pgoodsopenstate_arr['open'][0];
        //会员级别
        $level_filter = array();
        if (isset($_GET['level'])) {
            $level_filter['search'] = intval($_GET['level']);
        }
        if (intval($_GET['isable']) == 1) {
            $level_filter['isable'] = intval($this->member_info['level']);
        }
        if (count($level_filter) > 0) {
            if (isset($level_filter['search']) && isset($level_filter['isable'])) {
                $where['pgoods_limitmgrade'] = array(array('eq', $level_filter['search']), array('elt', $level_filter['isable']), 'and');
            } elseif (isset($level_filter['search'])) {
                $where['pgoods_limitmgrade'] = $level_filter['search'];
            } elseif (isset($level_filter['isable'])) {
                $where['pgoods_limitmgrade'] = array('elt', $level_filter['isable']);
            }
        }
        //查询仅我能兑换和所需积分
        $points_filter = array();
        if (intval($_GET['isable']) == 1) {
            $points_filter['isable'] = $this->member_info['member_points'];
        }
        if (intval($_GET['points_min']) > 0) {
            $points_filter['min'] = intval($_GET['points_min']);
        }
        if (intval($_GET['points_max']) > 0) {
            $points_filter['max'] = intval($_GET['points_max']);
        }
        if (count($points_filter) > 0) {
            asort($points_filter);
            if (count($points_filter) > 1) {
                $points_filter = array_values($points_filter);
                $where['pgoods_points'] = array('between', array($points_filter[0], $points_filter[1]));
            } else {
                if ($points_filter['min']) {
                    $where['pgoods_points'] = array('egt', $points_filter['min']);
                } elseif ($points_filter['max']) {
                    $where['pgoods_points'] = array('elt', $points_filter['max']);
                } elseif ($points_filter['isable']) {
                    $where['pgoods_points'] = array('elt', $points_filter['isable']);
                }
            }
        }

        //排序
        switch ($_GET['orderby']) {
            case 'stimedesc':
                $orderby = 'pgoods_starttime desc,';
                break;
            case 'stimeasc':
                $orderby = 'pgoods_starttime asc,';
                break;
            case 'pointsdesc':
                $orderby = 'pgoods_points desc,';
                break;
            case 'pointsasc':
                $orderby = 'pgoods_points asc,';
                break;
        }
        $orderby .= 'pgoods_sort asc,pgoods_id desc';

        $pointprod_list = $model_pointprod->getPointProdList($where, '*', $orderby, '', $this->page);
        $pageCount = $model_pointprod->gettotalpage();
        output_data(array('gifts' => $pointprod_list), mobile_page($pageCount));
    }

    /**
     *详细信息
     *
     */
    public function giftOp()
    {
        $pid = intval($_GET['id']);
        if (!$pid) {
            output_error("缺少id参数");
            die;
        }
        $model_pointprod = Model('pointprod');
        //查询兑换礼品详细
        $prodinfo = $model_pointprod->getOnlinePointProdInfo(array('pgoods_id' => $pid));
        if (empty($prodinfo)) {
            output_error("没有礼品详细信息");
            die;
        }
        //更新礼品浏览次数
        $tm_tm_visite_pgoods = cookie('tm_visite_pgoods');
        $tm_tm_visite_pgoods = $tm_tm_visite_pgoods ? explode(',', $tm_tm_visite_pgoods) : array();
        if (!in_array($pid, $tm_tm_visite_pgoods)) {//如果已经浏览过该商品则不重复累计浏览次数
            $result = $model_pointprod->editPointProdViewnum($pid);
            if ($result['state'] == true) {//累加成功则cookie中增加该商品ID
                $tm_tm_visite_pgoods[] = $pid;
            }
        }
        output_data(array('gift' => $prodinfo));
    }


    /**
     * 代金券列表
     */
    public function vouchersOp()
    {
        $model_voucher = Model('voucher');

        //代金券模板状态
        $templatestate_arr = $model_voucher->getTemplateState();

        //查询代金券列表
        $where = array();
        $where['voucher_t_state'] = $templatestate_arr['usable'][0];
        $where['voucher_t_end_date'] = array('gt', time());
        if (intval($_GET['sc_id']) > 0) {
            $where['voucher_t_sc_id'] = intval($_GET['sc_id']);
        }
        if (intval($_GET['price']) > 0) {
            $where['voucher_t_price'] = intval($_GET['price']);
        }
        //查询仅我能兑换和所需积分
        $points_filter = array();
        if (intval($_GET['isable']) == 1) {
            $points_filter['isable'] = $this->member_info['member_points'];
        }
        if (intval($_GET['points_min']) > 0) {
            $points_filter['min'] = intval($_GET['points_min']);
        }
        if (intval($_GET['points_max']) > 0) {
            $points_filter['max'] = intval($_GET['points_max']);
        }
        if (count($points_filter) > 0) {
            asort($points_filter);
            if (count($points_filter) > 1) {
                $points_filter = array_values($points_filter);
                $where['voucher_t_points'] = array('between', array($points_filter[0], $points_filter[1]));
            } else {
                if ($points_filter['min']) {
                    $where['voucher_t_points'] = array('egt', $points_filter['min']);
                } elseif ($points_filter['max']) {
                    $where['voucher_t_points'] = array('elt', $points_filter['max']);
                } elseif ($points_filter['isable']) {
                    $where['voucher_t_points'] = array('elt', $points_filter['isable']);
                }
            }
        }
        //排序
        switch ($_GET['orderby']) {
            case 'exchangenumdesc':
                $orderby = 'voucher_t_giveout desc,';
                break;
            case 'exchangenumasc':
                $orderby = 'voucher_t_giveout asc,';
                break;
            case 'pointsdesc':
                $orderby = 'voucher_t_points desc,';
                break;
            case 'pointsasc':
                $orderby = 'voucher_t_points asc,';
                break;
        }
        $orderby .= 'voucher_t_id desc';
        $voucherlist = $model_voucher->getVoucherTemplateList($where, '*', 0, $this->page, $orderby);

        //查询代金券面额
        // $pricelist = $model_voucher->getVoucherPriceList();
        $pageCount = $model_voucher->gettotalpage();
        output_data(array('vouchers' => $voucherlist), mobile_page($pageCount));
    }

    /**
     * 兑换代金券
     */
    public function voucherexchangeOp()
    {
        $vid = intval($_POST['vid']);

        if ($vid <= 0) {
            echo 0;
            die;
        }
        $model_voucher = Model('voucher');
        //验证是否可以兑换代金券
        $data = $model_voucher->getCanChangeTemplateInfo($vid, intval($this->member_info['member_id']), intval($_POST['store_id']));
        if ($data['state'] == false) {
            echo 0;
            die;
        }
        //添加代金券信息
        $data = $model_voucher->exchangeVoucher($data['info'], $this->member_info['member_id'], $this->member_info['member_name']);
        if ($data['state'] == true) {
            echo 1;
        } else {
            echo 0;
            die;
        }
    }

    /**
     * 积分礼品购物车
     */
    public function cartOp()
    {
        $cart_goods = array();
        $model_pointcart = Model('pointcart');
        $cart_goods = $model_pointcart->getPCartListAndAmount(array('pmember_id' => $this->member_info['member_id']));
        output_data(array('gifts' => $cart_goods));
    }

    /**
     * 购物车添加礼品
     */
    public function addOp()
    {

        $pgid = intval($_GET['pgid']);
        $quantity = intval($_GET['quantity']);

        if ($pgid <= 0 || $quantity <= 0) {
            echo 0;
            die;
        }

        //验证积分礼品是否存在购物车中
        $model_pointcart = Model('pointcart');
        $check_cart = $model_pointcart->getPointCartInfo(array('pgoods_id' => $pgid, 'pmember_id' => $this->member_info['member_id']));
        if (!empty($check_cart)) {
            echo 2;
            die;
        }

        //验证是否能兑换
        $data = $model_pointcart->checkExchange($pgid, $quantity, $this->member_info['member_id']);

        if (!$data['state']) {
            switch ($data['error']) {
                case 'ParameterError':
                    echo 0;
                    die;
                    break;
                default:
                    echo 0;
                    die;
                    break;
            }
        }
        $prod_info = $data['data']['prod_info'];
        $insert_arr = array();
        $insert_arr['pmember_id'] = $this->member_info['member_id'];
        $insert_arr['pgoods_id'] = $prod_info['pgoods_id'];
        $insert_arr['pgoods_name'] = $prod_info['pgoods_name'];
        $insert_arr['pgoods_points'] = $prod_info['pgoods_points'];
        $insert_arr['pgoods_choosenum'] = $prod_info['quantity'];
        $insert_arr['pgoods_image'] = $prod_info['pgoods_image_old'];
        if ($model_pointcart->addPointCart($insert_arr)) {
            echo 1;
        }
    }

    /**
     *积分兑换商品
     *
     */
    public function goodexchangeOp()
    {
        $model_pointcart = Model('pointcart');
        //获取符合条件的兑换礼品和总积分
        $data = $model_pointcart->getCartGoodsList($this->member_info['member_id']);
        if (!$data['state']) {
            echo 0;
            die;
        }
        $pointprod_arr = $data['data'];
        unset($data);

        //验证积分数是否足够
        $data = $model_pointcart->checkPointEnough($pointprod_arr['pgoods_pointall'], $this->member_info['member_id']);
        if (!$data['state']) {
            echo 0;
            die;
        }
        unset($data);

        //创建兑换订单
        $data = Model('pointorder')->createOrder($_POST, $pointprod_arr, array('member_id' => $this->member_info['member_id'], 'member_name' => $this->member_info['member_name'], 'member_email' => $this->member_info['member_email']));
        if (!$data['state']) {
            echo 0;

            die;
        }
        $order_id = $data['data']['order_id'];

        if ($order_id <= 0) {
            echo 0;
            die;
        }
        $where = array();
        $where['point_orderid'] = $order_id;
        $where['point_buyerid'] = $this->member_info['member_id'];
        $order_info = Model('pointorder')->getPointOrderInfo($where);
        if (!$order_info) {
            echo 0;
            die;
        }
        echo 1;
//        output_data(array('exchange_order' => $order_info));
    }


    /**
     * 积分礼品购物车删除单个礼品
     */
    public function dropOp()
    {
        $pcart_id = intval($_GET['pc_id']);
        if ($pcart_id <= 0) {
            echo 0;
            die;
        }
        $model_pointcart = Model('pointcart');
        $drop_state = $model_pointcart->delPointCartById($pcart_id, $this->member_info['member_id']);
        if ($drop_state) {
            echo 1;
        } else {
            echo 0;
            die;
        }
    }


    /**
     * 兑换信息列表
     */
    public function scoreOrdersOp()
    {


//        $model_pointorder = Model('pointorder');
//        $order_list = $model_pointorder->getPointOrderList($where, '*', 10, 0, 'point_orderid desc');
//        $order_idarr = array();
//        $order_listnew = array();
//        if (is_array($order_list) && count($order_list)>0){
//            foreach ($order_list as $k => $v){
//                $order_listnew[$v['point_orderid']] = $v;
//                $order_idarr[] = $v['point_orderid'];
//            }
//        }
//
//        //查询兑换商品
//        if (is_array($order_idarr) && count($order_idarr)>0){
//            $prod_list = $model_pointorder->getPointOrderGoodsList(array('point_orderid'=>array('in',$order_idarr)));
//            if (is_array($prod_list) && count($prod_list)>0){
//                foreach ($prod_list as $v){
//                    if (isset($order_listnew[$v['point_orderid']])){
//                        $order_listnew[$v['point_orderid']]['prodlist'][] = $v;
//                    }
//                }
//            }
//        }
        //兑换信息列表
        $where = array();
        $where['point_buyerid'] = $this->member_info['member_id'];
        $model_pointorder = Model('pointorder');
        $order_list = $model_pointorder->getPointOrderList($where, '*', 10, 0, 'point_orderid desc');
        $pageCount = $model_pointorder->gettotalpage();
        $order_idarr = array();
        $order_listnew = array();
        if (is_array($order_list) && count($order_list) > 0) {
            foreach ($order_list as $k => $v) {
                $order_listnew[] = $v;
                $order_idarr[] = $v['point_orderid'];
            }
        }

        //查询兑换商品
        if (is_array($order_idarr) && count($order_idarr) > 0) {
            $prod_list = $model_pointorder->getPointOrderGoodsList(array('point_orderid' => array('in', $order_idarr)));
            if (is_array($prod_list) && count($prod_list) > 0) {
                foreach ($prod_list as $k => $v) {
//                    if ($order_listnew[$k]['point_orderid'] == $v['point_orderid'])
                    $order_listnew[]['prodlist'] = $v;
//                    if (isset($order_listnew[$v['point_orderid']])){
//                        $order_listnew[]['prodlist'][] = $v;
////                        $order_listnew['prodlist'][] = $v;
//                    }
                }
            }
        }
        output_data(array("score_orders" => $order_listnew), mobile_page($pageCount));
    }


    /**
     *  取消兑换
     */
    public function cancel_orderOp()
    {
        $pid = intval($_GET['order_id']);
        if (!$pid) {
            echo 0;
            die;
        }
        $model_pointorder = Model('pointorder');
        //取消订单
        $data = $model_pointorder->cancelPointOrder($pid, $this->member_info['member_id']);
        if ($data['state']) {
            echo 1;
        } else {
            echo 0;
            die;
        }
    }

    /**
     * 确认收货
     */
    public function receiving_orderOp()
    {
        $data = Model('pointorder')->receivingPointOrder($_GET['order_id']);
        if ($data['state']) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 兑换信息详细
     */
    public function order_infoOp()
    {
        $order_id = intval($_GET['order_id']);
        if ($order_id <= 0) {
            echo 0;
        }
        $model_pointorder = Model('pointorder');
        //查询兑换订单信息
        $where = array();
        $where['point_orderid'] = $order_id;
        $where['point_buyerid'] = $_SESSION['member_id'];
        $order_info = $model_pointorder->getPointOrderInfo($where);
        if (!$order_info) {
            showDialog(L('member_pointorder_record_error'), 'index.php?act=member_pointorder', 'error');
        }
        //获取订单状态
        $pointorderstate_arr = $model_pointorder->getPointOrderStateBySign();
        Tpl::output('pointorderstate_arr', $pointorderstate_arr);

        //查询兑换订单收货人地址
        $orderaddress_info = $model_pointorder->getPointOrderAddressInfo(array('point_orderid' => $order_id));
        Tpl::output('orderaddress_info', $orderaddress_info);

        //兑换商品信息
        $prod_list = $model_pointorder->getPointOrderGoodsList(array('point_orderid' => $order_id));
        Tpl::output('prod_list', $prod_list);

        //物流公司信息
        if ($order_info['point_shipping_ecode'] != '') {
            $data = Model('express')->getExpressInfoByECode($order_info['point_shipping_ecode']);
            if ($data['state']) {
                $express_info = $data['data']['express_info'];
            }
            Tpl::output('express_info', $express_info);
        }

        Tpl::output('order_info', $order_info);
        Tpl::output('left_show', 'order_view');
        Tpl::showpage('member_pointorder_info');
    }

}
