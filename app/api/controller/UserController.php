<?php

namespace app\api\controller;

use app\admin\model\Sms;
use app\admin\model\Withdraw;
use app\api\basic\Base;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\User;
use Respect\Validation\Validator;
use support\Request;
use Tinywan\Jwt\JwtToken;

class UserController extends Base
{
    protected $noNeedLogin = ['login'];
    function login(Request $request)
    {
        $code = $request->post('code');
        $h5 = $request->isTerminal('h5');

        $miniopenid = '';
        $h5_openid = '';
        if ($h5){
            //公众号
            $app = new \EasyWeChat\OfficialAccount\Application(config('wechatofficial'));
            $oauth = $app->getOAuth();
            $ret = $oauth->userFromCode($code);
            $h5_openid = $ret->getId();
            $unionid = '';
        }else{
            //小程序
            $app = new \EasyWeChat\MiniApp\Application(config('wechatmini'));
            $utils = $app->getUtils();
            $ret = $utils->codeToSession($code);
            $miniopenid = $ret['openid'];
            $unionid = $ret['unionid'];
        }


        $user = User::where('unionid',$unionid)->first();
        if (!$user){
            $user = User::create([
                'nickname' => '用户' . Util::alnum(),
                'avatar' => '/avatar.png',
                'join_time' => Carbon::now()->toDateTimeString(),
                'join_ip' => $request->getRealIp(),
                'last_time' => Carbon::now()->toDateTimeString(),
                'last_ip' => $request->getRealIp(),
                'unionid' => $unionid,
                'mini_openid' => $miniopenid,
                'h5_openid' => $h5_openid,
            ]);
        }else{
            $user->last_time = Carbon::now()->toDateTimeString();
            $user->last_ip = $request->getRealIp();
            $user->save();
        }
        $token = JwtToken::generateToken([
            'id' => $user->id,
            'client' => JwtToken::TOKEN_CLIENT_MOBILE
        ]);
        return $this->success('登陆成功',['user'=>$user,'token'=>$token]);
    }

    function bindMobile(Request $request)
    {
        $code = $request->post('code');
        //小程序
        $app = new \EasyWeChat\MiniApp\Application(config('wechatmini'));
        $api = $app->getClient();
        $ret = $api->postJson('/wxa/business/getuserphonenumber', [
            'code' =>$code
        ]);
        $ret = json_decode($ret);
        if ($ret->errcode != 0) {
            return $this->fail( '获取手机号失败');
        }
        $mobile =$ret->phone_info->phoneNumber;
        $row = User::find($request->user_id);
        $row->mobile = $mobile;
        $row->save();

        return $this->success('成功');
    }

    function changeMobile(Request $request)
    {
        $mobile = $request->post('mobile');
        $captcha = $request->post('captcha');
        if (!$mobile || !Validator::mobile()->validate($mobile)) {
            return $this->fail('手机号不正确');
        }
        $smsResult = Sms::check($mobile, $captcha, 'changemobile');
        if (!$smsResult) {
            return $this->fail('验证码不正确');
        }
        $user = User::find($request->user_id);
        $user->mobile = $mobile;
        $user->save();
        return $this->success();
    }


    function withdraw(Request $request)
    {
        $amount = $request->post('amount');
        $type = $request->post('type');#类型:0=入金,1=出金
        $day = $request->post('day', '');
        $image = $request->post('image');
        $user = User::find($request->user_id);

        if ($type == 1) {
            if ($user->money < $amount ) {
                return $this->fail('余额不足');
            }
            User::score(-$amount, $user->id, '出金', 'money');
        }
        $data=[
            'user_id' => $user->id,
            'amount' => $amount,
            'image' => $image,
            'type' => $type
        ];
        if (!empty($day)){
            $data['day'] = $day;
        }
        Withdraw::create($data);
        return $this->success();
    }

    function getWithdrawList(Request $request)
    {
        $month = $request->post('month');
        $date = Carbon::parse($month);
        $status = $request->post('status', ''); #状态:0=待审核,1=审核通过,2=审核不通过
        $rows = Withdraw::where(['user_id' => $request->user_id])->when(!empty($status) || $status == 0, function (Builder $query) use ($status) {
            $query->where('status', $status);
        })->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->get();
        return $this->success('成功', $rows);
    }

    function getUserInfo(Request $request)
    {
        $user = User::find($request->user_id);
        return $this->success('成功', $user);
    }



}
