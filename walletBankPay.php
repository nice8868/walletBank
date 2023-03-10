<?php
// +----------------------------------------------------------------------
// | 电子银行
// +----------------------------------------------------------------------
// | Copyright (c) 2021~2022 https://www.github.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: nice8868 Team <279451209@qq.com>
// +----------------------------------------------------------------------

namespace app\services\pay;



/**
 * 电子银行支付
 * @author nice_11 date 2021/12/04
 * Class WalletPayServices
 * @package app\services\pay
 */
class WalletPayServices
{
    public $time = 0;
    public $config = [
        //测试环境
        'dev'=>[
            'url'       =>  'https://dev.com:9083/routejson',
            'app_id'    =>  '1',//商户ID
            'walletId'  =>  '1',//钱包ID
            ''
        ],
        //生产环境
        'serv' => [
            'url'   => 'https://serv.com',
            'app_id'    => '',
        ]
    ];

    public $app_id  =   null;//系统appid
    public $url     =   null;//请求url
    public $walletId=   null;//系统钱包id
    public function __construct($dev = 'dev'){
        date_default_timezone_set("Asia/Shanghai");
        $this->timestamp=   $_SERVER['REQUEST_TIME'] ?? time();
        $this->time     =   date('Y-m-d H:i:s', $this->timestamp);
        $this->app_id   =   $this->config[$dev]['app_id'];
        $this->url      =   $this->config[$dev]['url'];
        $this->walletId =   $this->config[$dev]['walletId'];
        $this->issrId();
        //$this->reqSn();
    }

    public static function getSign($data,$isUrlEncode=true){
        $str = self::getString($data, $isUrlEncode);
        //使用SSL
        $certs = array();
        $pfxPath = __DIR__.'/wallet/gnete.pfx';
        $pfxPwd = '123456';
        openssl_pkcs12_read(file_get_contents($pfxPath), $certs, $pfxPwd);
        if(!$certs) return ;

        $signature = '';
        openssl_sign($str, $signature, $certs['pkey'], "SHA1");

        return bin2hex($signature);
    }

    //是否需要biz_content url编码
    public static function getString($data, $isUrlEncode = true){
        $str = '';
        foreach ($data as $key => $value) {
            if($isUrlEncode && $key == 'biz_content') $value = urlencode(utf8_encode($value));
            $str .= $key.'='.$value.'&';
        }
        $str = trim($str, '&');
        //百分号转小写
        if($isUrlEncode){
            $str = preg_replace_callback('/%[0-9A-F]{2}/', function(array $matches)
            {
                return strtolower($matches[0]);
            },$str);
        }
        return $str;
    }

    /**
     * 获取getCerSign
     * @param $creStr
     * @return string|void
     */
    public static function getCerSign($creStr){
        //使用SSL
        $certs = array();
        $pfxPath = __DIR__.'/wallet/49c4ca5f189c433400933.p12';
        $pfxPwd = '123456';
        openssl_pkcs12_read(file_get_contents($pfxPath), $certs, $pfxPwd);
        if(!$certs) return ;
        $signature = '';
        openssl_sign($creStr, $signature, $certs['pkey'], "SHA256");

        return bin2hex($signature);
    }

    /**
     * 在需要验证密码或交易证书时先调用此接口申请随机因子。可提前批量申请，单次申请上限 100 个，默认申请 1 个。
    未验证前有效期 24 小时，验证后即失效
     * @return array
     */
    public function plugRandomKey($params = [],$uid = 0){
        return $this->msgType('2046')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 开户C端
     * @param array $params
     * @param int $uid
     * @return void
     */
    public function openAcct($params = [] , $uid = 0){
        return $this->msgType('2001')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 激活电子钱包
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function activeAcct($params = [],$uid = 0){
        return $this->msgType('2002')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * @param $mobileNo 手机号
     * @param $smsTempltCode 短信模板编码 COMMON一般验证码;REGIST开户;ACTIVATE：激活 ;TRADE：交易可直接使用一般验证码
     * @param $smsBizType 短信业务类型。开户时可以不填，若填 4 则会验证该手机号是否已经开户。
    1：绑定第三方用户2：重置密码3：变更手机号4：开户5：银行卡签约 6：交易
     * @return bool
     */
    public function sendSmsAuthCode($params = [],$smsType = 'vaild'){
        if($smsType == 'send'){
            return $this->msgType('2020')->msgBody($params)->bizContent()->toDo();
        }else if($smsType=='valid'){
            return $this->msgType('2021')->msgBody($params)->bizContent()->toDo();
        }
    }

    /**
     * 查询电子钱包信息
     * @param $params
     * @return void
     */
    public function queryAcctInfo($params = [] , $uid = 0){
        return $this->msgType('1002')->msgBody(['walletId'=>$params['walletId']])->bizContent()->toDo();
    }
    /**
     * 注销c端开户
     * @param $params
     * @param $uid
     * @return array
     */
    public function acctCancel($params = [],$uid = 0){
        return $this->msgType('2066')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 查询 B 端或 C 端电子账户的余额。
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryAcctBal($params = [],$uid = 0){
        return $this->msgType('1004')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 锁定解锁账户 用于锁定/解锁 B 端或者 C 端电子账户，也可以对授信账户进行启用/停用。
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function lockUnlockAcct($params = [], $uid = 0){
        return $this->msgType('2017')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 冻结/解冻账户金额 用于冻结/解冻 B 端或者 C 端电子账户的余额
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function freezeUnfreezeAcctBal($params = [], $uid = 0){
        return $this->msgType('2018')->msgBody($params)->bizContent()->toDo();
    }
    /*
     * 修改密码
     * 用于修改 B 端或者 C 端电子账户的支付密码。
     */
    public function modifyPwd($params = [],$uid =0){
        return $this->msgType('2016')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 重置支付密码(B 端)
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function resetBtypeAcctPwd($params = [],$uid =0){
        return $this->msgType('2045')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 查询账户关联信息
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryAcctRelatedInfo($params = [],$uid = 0){
        return $this->msgType('1003')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询 B 端或 C 端电子账户交易结果
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryTransResult($params = [],$uid = 0){
        return $this->msgType('1005')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 查询批量交易结果
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryBatchTransResult($params = [], $uid = 0){
        return $this->msgType('1010')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 查询交易明细
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryTransList($params = [] ,$uid = 0){
        return $this->msgType('1006')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询交易明细
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryBillInfo($params = [] ,$uid = 0){
        return $this->msgType('1013')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 手续费费率查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryFeeRate($params = [] ,$uid = 0){
        return $this->msgType('1015')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 手续费查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryFee($params = [] ,$uid = 0){
        return $this->msgType('1016')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 账户绑定/解绑/设置默认银行卡
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function acctBindBankCard($params = [], $uid = 0){
        return $this->msgType('2019')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询账户银行卡
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryBindBankCard($params = [], $uid = 0){
        return $this->msgType('1007')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 银行卡签约申请持卡人进行银行卡签约申请接口。申请成功后，持卡人会收到由银行发送的短信验证
    码，在进行签约确认时需要将短信验证码上送。
    只有 C 端账户可以进行银行卡签约，完成签约后账户可以进行充值操作
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function bankAcctSignApply($params = [], $uid = 0){
        return $this->msgType('2034')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 银行卡签约确认
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function bankAcctSignConfirm($params = [], $uid = 0){
        return $this->msgType('2035')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 银行卡解约
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function bankAcctSignCancel($params = [], $uid = 0){
        return $this->msgType('2036')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 代扣充值
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function cpsDsRecharge($params = [], $uid = 0){
        return $this->msgType('2037')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 异步提现从电子账户提现到指定银行卡。调用接口时先插入提现登记表，再等待定时任务进行
    代付处理
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function asyncWithdraw($params = [], $uid = 0){
        return $this->msgType('2100')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 转账/汇款/收款 可以在 B 端或 C 端电子账户之间进行转账
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function transfer($params = [], $uid = 0){
        return $this->msgType('2015')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 批量转账可以在 B 端或 C 端电子账户之间进行批量转账
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function batchTransfer($params = [], $uid = 0){
        return $this->msgType('2027')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 异步汇款由电子账户直接汇款到银行账户。调用接口时先插入汇款登记表，再等待定时任务进
    行代付处理。
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function asyncRemit($params = [], $uid = 0){
        return $this->msgType('2101')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 批量汇款。批量由电子账户汇款到银行卡账户
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function batchRemit($params = [], $uid = 0){
        return $this->msgType('2044')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 授权签约/解约根据协议授权企业定期从其电子账户中扣取相应款项，满足 C2B 或 B2B 免密扣款。被
    授权电子账户作为入款方，可向授权电子账户收款
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function withholdGrant($params = [], $uid = 0){
        return $this->msgType('2023')->msgBody($params)->bizContent()->toDo();
    }

    /**
     * 单笔收款对于已经签约的授权关系，可以调用此接口对授权电子账户进行收款
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function receivable($params = [], $uid = 0){
        return $this->msgType('2024')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 批量收款对于已经签约的授权关系，可以调用此接口对授权电子账户进行批量收款
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function batchReceivable($params = [], $uid = 0){
        return $this->msgType('2025')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 1 消费类优惠查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryDiscountsMarketing($params = [], $uid = 0){
        return $this->msgType('1028')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 1 消费类下单
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function consumeApplyOrder($params = [], $uid = 0){
        return $this->msgType('2056')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 1 消费类支付
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function consumePay($params = [], $uid = 0){
        return $this->msgType('2057')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 1 消费类退款
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function consumeRefund($params = [], $uid = 0){
        return $this->msgType('2058')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 1 查询消费类订单详情
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryConsumePayOrderInfo($params = [], $uid = 0){
        return $this->msgType('1029')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询消费类退款详情
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryConsumeRefundOrderInfo($params = [], $uid = 0){
        return $this->msgType('1030')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 关闭订单
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function closeOrder($params = [], $uid = 0){
        return $this->msgType('2059')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询电子券通过电子账户 id 查询该账户下拥有的电子券
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryCouponByWalletId($params = [], $uid = 0){
        return $this->msgType('1033')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 查询电子券详情
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryCouponByCouponId($params = [], $uid = 0){
        return $this->msgType('1034')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 分页查询电子券通过电子账户 id 查询所有电子券信息
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryCouponByWalletIdByPage($params = [], $uid = 0){
        return $this->msgType('1066')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  电子账户收货地址新增
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function addReceiveAddress($params = [], $uid = 0){
        return $this->msgType('2122')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  电子账户收货地址编辑
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function updateReceiveAddress($params = [], $uid = 0){
        return $this->msgType('2123')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  电子账户收货地址查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryReceiveAddressByPage($params = [], $uid = 0){
        return $this->msgType('1065')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  资金共管
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function coadminGrant($params = [], $uid = 0){
        return $this->msgType('2052')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  查询共管授权详情
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryCoadminGrant($params = [], $uid = 0){
        return $this->msgType('1026')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  审批共管授权
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function coadminGrantApprove($params = [], $uid = 0){
        return $this->msgType('2053')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  查询共管账户余额
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryCoadminGrantAcctBal($params = [], $uid = 0){
        return $this->msgType('1027')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  共管资金转账
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function coadminTransfer($params = [], $uid = 0){
        return $this->msgType('2054')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  共管资金批量转账含有共管资金属性的批量转账操作。支持的转账类型与单笔共管资金转账相同
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function coadminBatchTransfer($params = [], $uid = 0){
        return $this->msgType('2055')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  红包发放使用好易联电子账户发放红包。支持拼手气红包（随机金额）和普通红包（固定金额）
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function redPacketIssue($params = [], $uid = 0){
        return $this->msgType('2042')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  红包领取
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function redPacketRec($params = [], $uid = 0){
        return $this->msgType('2043')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  红包详情查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryRedPacketDetail($params = [], $uid = 0){
        return $this->msgType('1019')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  红包发放记录查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryIssuedRedPacket($params = [], $uid = 0){
        return $this->msgType('1020')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  红包领取记录查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryRecRedPacket($params = [], $uid = 0){
        return $this->msgType('1021')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  待领取红包查询
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryWaitingRecRedPacket($params = [], $uid = 0){
        return $this->msgType('1038')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  获取随机因子
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function getPlugRandomKey($params = [], $uid = 0){
        return $this->msgType('2046')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  查询银行卡信息
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function queryBankAcctInfo($params = [], $uid = 0){
        return $this->msgType('1012')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  身份认证 用于验证 C 端电子账户信息是否正确
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function identifyAuthenticate($params = [], $uid = 0){
        return $this->msgType('2030')->msgBody($params)->bizContent()->toDo();
    }
    /**
     *  验证支付密码 用于验证电子账户支付密码密文是否正确。仅做验证用，随机因子验证完则失效
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function validatePayPwd($params = [], $uid = 0){
        return $this->msgType('2029')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 验证交易证书
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function checkPayCert($params = [], $uid = 0){
        return $this->msgType('2047')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 修改用户手机号
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function modifyUserMobile($params = [], $uid = 0){
        return $this->msgType('2033')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 批量获取电子回单
     * @param $params
     * @param $uid
     * @return array|false|void
     */
    public function batchDownloadElecReceipt($params = [], $uid = 0){
        return $this->msgType('2127')->msgBody($params)->bizContent()->toDo();
    }
    /**
     * 设置请求
     * @param $method
     * @return $this
     */
    public function method( $method = ''){
        $this->method = $method;
        return $this->method;
    }

    /**
     * 设置请求流水号
     * @param $reqSn
     * @return $this
     */
    public function reqSn( $reqSn = ''){
        $this->reqSn = !empty($reqSn) ? $reqSn :'R'.date('YmdHis').rand(1000,9999);
        return $this->reqSn;
    }

    /**
     * 应答时间
     * @return false|string
     */
    public function sndDate(){
        $this->sndDate = date('Y-m-d H:i:s');
        return $this->sndDate;
    }
    /**
     * 发起方标识
     * @param $wallbc
     * @return mixed|string
     */
    public function issrId($wallbc = 'wallbc'){
        $this->issrId = $wallbc;
        return $this;
    }

    /**
     * 报文编号
     * @param $msgType
     * @return $this
     */
    public function msgType($msgType = 2000){
        $this->msgType = $msgType;
        $arr=[
            '2001'=>'gnete.wallbc.123.openAcct',//开户C端
            '2002'=>'gnete.wallbc.123.activeAcct',//激活账户
            '2020'=>'gnete.wallbc.123.sendSmsAuthCode',//发送短信验证码
            '2021'=>'gnete.wallbc.123.validSmsAuthCode',//验证短信验证码
            '2017'=>'gnete.wallbc.123.lockUnlockAcct',//锁定/解锁账户
            '2018'=>'gnete.wallbc.123.freezeUnfreezeAcctBal',//冻结/解冻账户金额

        ];
        $this->method($arr[$this->msgType]);
        //$this->method = $arr[$this->msgType];
        return $this;
    }
    /**
     * 请求业务参数
     * @param $msgBody
     * @return $this
     */
    public function msgBody($msgBody = []){
        $this->msgBody = $msgBody;
        return $this;
    }
    /*
     * 内容
     */
    public function bizContent(){
        $this->biz_content =[
            'msgBody'   =>$this->msgBody,
            'issrId'    => $this->issrId,//发起方标识
            'msgType'   => $this->msgType,//报文编号
            'reqSn'     => $this->reqSn,//请求流水号
            'sndDate'   => $this->sndDate,//应答时间
        ];
        return $this;
    }

    /**
     * 发送请求
     * @return array|false|void
     */
    public function toDo(){
        $data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->time,
            'method' => $this->method,
            'v' => '1.0.1',
            'biz_content'=> json_encode($this->biz_content),
            'sign_alg' => '0'
        ];
        $sigin = self::getSign($data, false);
        $data['sign'] = $sigin;
        $info = self::getString($data, false);
        //post传递
        return self::requestPost($this->url, $info);
    }
    /**
     * 请求
     * @param $url
     * @param $info
     * @return void
     */
    public static function requestPost($url , $info = []){
        $ch = curl_init();
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $info);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }
}
