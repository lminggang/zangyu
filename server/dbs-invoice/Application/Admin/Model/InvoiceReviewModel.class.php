<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

class InvoiceReviewModel extends Model {

    /**
     * 获取发票真实信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function getReviewInfo($invoice_id = 0)
    {
        $reviewInfo = $this->getInvoiceReviewInfoById($invoice_id);

        if(empty($reviewInfo)){
            $reviewInfo = $this->initInvoiceReviewInfo($invoice_id);
        }

        return $reviewInfo;
    }

    /**
     * 临时获取发票信息 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  string $invoice_code [发票代码]
     * @param  string $invoice_num  [发票号码]
     * @param  string $release_date [开票日期]
     * @param  string $verify_code  [发票校验码]
     */
    public function tempInvoiceReview($invoice_code = '', $invoice_num = '', $release_date = '', $verify_code = '', $no_tax_amount = '')
    {
        $invoiceId = $this->getInvoiceIdByInvoiceCode($invoice_code, $invoice_num);

        if($invoiceId){
            return U("Invoice/checkReview", 'id=' . $invoiceId);
        }
        

        $release_date = date("Ymd", strtotime($release_date));

        $data = array();
        $data['checkCode'] = substr($verify_code, -6, 6);
        $data['fpdm'] = $invoice_code;
        $data['fphm'] = $invoice_num;
        $data['kprq'] = $release_date;
        $data['noTaxAmount'] = $no_tax_amount;

        $invoice_UUID = md5($data['checkCode'] . $data['fpdm'] . $data['fphm'] . $data['kprq'] . $data['noTaxAmount']);
        $invoice_UUID = strtoupper(substr($invoice_UUID, 8, 16));

        $redisKey = "TMEM_INVOICE_" . $invoice_UUID;

        $redis = D("Redis");


        if($redis->TTL($redisKey) > 0){
            return U("Invoice/show_temp_invoice_review", 'uuid=' . $invoice_UUID);
        }

        $res = $this->reqReviewSvc($data);

        if(empty($res) || $res['success'] == false){
            $res = array(
                "errNo" => "1001",
                "errMsg" => empty($res['data']) ? "请求接口失败" : $res['data'],
            );
            return $res;
        }

        $invoice_UUID = md5($data['checkCode'] . $data['fpdm'] . $data['fphm'] . $data['kprq']);
        $invoice_UUID = strtoupper(substr($invoice_UUID, 8, 16));

        $redisKey = "TMEM_INVOICE_" . $invoice_UUID;


        $redis->set($redisKey, json_encode($res));
        $redis->expire($redisKey, 600);
        
        return U("Invoice/show_temp_invoice_review", 'uuid=' . $invoice_UUID);
    }

    /**
     * 获取临时发票展示信息 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  string $UUID [发票唯一id]
     */
    public function getTempReviewInfo($UUID = '')
    {
        $redisKey = "TMEM_INVOICE_" . $UUID;

        $redis = D("Redis");

        if($redis->TTL($redisKey) < 1){
            return false;
        }

        $res = json_decode($redis->get($redisKey), true);
        
        $res['updateTime'] = date("Y-m-d H:i:s", (int)$res['updateTime'] / 1000);

        return $res;
    }

    /**
     * 获取发票记录id 
     * Author Raven
     * Date 2019-08-13
     * Params [params]
     * @param  string $invoice_code [发票代码]
     * @param  string $invoice_num  [发票号码]
     */
    public function getInvoiceIdByInvoiceCode($invoice_code='', $invoice_num = '')
    {
        $Dao = M('invoice_review_info');
        $where = [
            'fpdm' => $invoice_code,
            'fphm' => $invoice_num
        ];

        $invoiceId = $Dao
            ->where($where)
            ->order('id desc')
            ->getField('fk_invoice_id');

        return $invoiceId;
    }

    /**
     * 初始化发票真实信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function initInvoiceReviewInfo($invoice_id = 0)
    {
        $invoiceInfo = $this->getInvoiceInfoById($invoice_id);
        if(empty($invoiceInfo)){
            return false;
        }

        $release_date = date("Ymd", strtotime($invoiceInfo['release_date']));

        $data = array();
        $data['checkCode'] = substr($invoiceInfo['verify_code'], -6, 6);
        $data['fpdm'] = $invoiceInfo['invoice_code'];
        $data['fphm'] = $invoiceInfo['invoice_num'];
        $data['kprq'] = $release_date;

        $res = $this->reqReviewSvc($data);

        if(empty($res) || $res['success'] == false){
            echo json_encode($res, JSON_UNESCAPED_UNICODE) . "<br><br>";
            return false;
        }

        $Dao = M("invoice_review_info");

        $Dao->startTrans();

        $addInfo = $this->addReviewInfo($invoice_id, $res);

        if(empty($addInfo)){
            $Dao->rollback();
            return false;
        }

        $addGoods = $this->addReviewGoods($invoice_id, $res['goodsData']);

        if(empty($addGoods)){
            $Dao->rollback();
            return false;
        }

        $Dao->commit();

        $res = $this->getInvoiceReviewInfoById($invoice_id);

        return $res;
    }

    /**
     * 添加真实发票信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param integer $invoice_id [发票id]
     * @param array   $params     [添加的数据对象]
     */
    public function addReviewInfo($invoice_id = 0, $params = array())
    {
        unset($params['goodsData']);
        unset($params['success']);

        $params['taxamount'] = strval($params['taxamount'] * 100);
        $params['goodsamount'] = strval($params['goodsamount'] * 100);
        $params['sumamount'] = strval($params['sumamount'] * 100);
        $params['del'] = $params['del'] == "Y" ? 1 : 2;

        $data = array();
        $data['fk_invoice_id'] = $invoice_id;
        $data['createtime'] = date("Y-m-d H:i:s");


        $data = array_merge($data, $params);

        $Dao = M("invoice_review_info");

        $add = $Dao->add($data);

        return intval($add);
    }

    /**
     * 添加真实发票商品
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param integer $invoice_id [发票id]
     * @param array   $params     [添加的数据对象]
     */
    public function addReviewGoods($invoice_id = 0, $params = array())
    {
        $Dao = M("invoice_review_goods");

        $data = array();

        foreach ($params as $key => $value) {
            $tmp = array();
            $tmp['fk_invoice_id'] = $invoice_id;
            $tmp['goods_name'] = $value['name'];
            $tmp['goods_spec'] = $value['spec'];
            $tmp['goods_unit'] = $value['unit'];
            $tmp['goods_cnt'] = $value['amount'];
            $tmp['goods_price'] = strval(number_format($value['priceUnit'], 2, '.', '') * 100);
            $tmp['goods_amount'] = strval($value['priceSum'] * 100);
            $tmp['goods_tax_rate'] = $value['taxRate'];
            $tmp['goods_tax_amount'] = strval($value['taxSum'] * 100);
            $tmp['createtime'] = date("Y-m-d H:i:s");

            $add = $Dao->add($tmp);

            if($add == false){
                return false;
            }
        }

        return true;
    }

    /**
     * 请求发票验真伪服务 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  array  $data [请求参数]
     */
    public function reqReviewSvc($data = array())
    {
        $host = "https://fapiao.market.alicloudapi.com";
        $path = "/invoice/query";
        $method = "GET";
        $appcode = "12e7608940fe45f68dc3135f1380765d";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = http_build_query($data);

        $bodys = "";
        $url = $host . $path . "?" . $querys;
        
        //DLOG(sprintf("uri : %s params : %s", $path, json_encode($data, JSON_UNESCAPED_UNICODE)), "run", "invoice_svc");

        $startTime = microtime(true) * 1000;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);

        if(empty($result) == false){
            $result = explode("\n", $result);
            $result = end($result);

        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $endTime = microtime(true) * 1000;
        $runTime =  number_format($endTime - $startTime, 3);

        //DLOG(sprintf("code : %s res : %s [runtime = " . $runTime . " ms]", $code, $result), "run", "invoice_svc");
        
        return json_decode($result, true);
    }


    /**
     * 获取发票信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function getInvoiceInfoById($invoice_id = 0)
    {
        $where = array(
            "id" => $invoice_id
        );

        $Dao = M("invoice_info");

        $invoiceInfo = $Dao
            ->field("verify_code,invoice_code,invoice_num,release_date")
            ->where($where)
            ->find();

        return $invoiceInfo;
    }

    /**
     * 通过发票id获取发票真实信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  integer $invoice_id [发票id]
     */
    public function getInvoiceReviewInfoById($invoice_id = 0)
    {
        $infoDao = M("invoice_review_info");
        $goodsDao = M("invoice_review_goods");

        $where = array(
            "fk_invoice_id" => $invoice_id
        );

        $reviewInfo = $infoDao
            ->field("fpdm,fphm,kprq,xfMc,xfNsrsbh,xfContact,xfBank,gfMc,gfNsrsbh,code,num,del,taxamount,goodsamount,sumamount,quantityAmount,remark")
            ->where($where)
            ->find();

        if(empty($reviewInfo)){
            return array();
        }

        $goodsInfo = $goodsDao
            ->field("goods_name,goods_spec,goods_unit,goods_cnt,goods_price,goods_amount,goods_tax_rate,goods_tax_amount")
            ->where($where)
            ->select();

        $reviewInfo = $this->formatReviewInfo($reviewInfo);
        $goodsInfo = $this->formatGoodsInfo($goodsInfo);

        $res = array(
            "invoice" => $reviewInfo,
            "goods" => $goodsInfo
        );

        return $res;
    }

    /**
     * 格式化发票真实信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  array  $review_info [发票信息]
     */
    public function formatReviewInfo($review_info = array())
    {
        $review_info['taxamount'] = number_format($review_info['taxamount'] / 100, 2);
        $review_info['goodsamount'] = number_format($review_info['goodsamount'] / 100, 2);
        $review_info['sumamount'] = number_format($review_info['sumamount'] / 100, 2);

        return $review_info;
    }

    /**
     * 格式化商品信息 
     * Author Raven
     * Date 2018-07-26
     * Params [params]
     * @param  array  $goods_info [商品信息]
     */
    public function formatGoodsInfo($goods_info = array())
    {
        foreach ($goods_info as $key => $value) {
            $goods_info[$key]['goods_price'] = number_format($value['goods_price'] / 100, 2);
            $goods_info[$key]['goods_amount'] = number_format($value['goods_amount'] / 100, 2);
            $goods_info[$key]['goods_tax_amount'] = number_format($value['goods_tax_amount'] / 100, 2);
        }

        return $goods_info;
    }
}
