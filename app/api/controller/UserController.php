<?php

namespace app\api\controller;

use app\admin\model\Sms;
use app\admin\model\Withdraw;
use app\api\basic\Base;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\User;
use Respect\Validation\Validator;
use support\Request;
use Tinywan\Jwt\JwtToken;
use Intervention\Image\ImageManagerStatic as Image;

class UserController extends Base
{
    protected $noNeedLogin = ['login'];

    function login(Request $request)
    {
        $code = $request->post('code');
        $h5 = $request->isTerminal('h5');
        $miniopenid = '';
        $h5_openid = '';
        $nickname = '';
        $avatar = '';
        if ($h5) {
            //公众号
            $app = new \EasyWeChat\OfficialAccount\Application(config('wechatofficial'));
            $oauth = $app->getOAuth();
            $ret = $oauth->userFromCode($code);
            $h5_openid = $ret->getId();
            $raw = $ret->getRaw();
            $nickname = $raw['nickname'];
            $unionid = $raw['unionid'];
            $avatar = $raw['headimgurl'];
        } else {
            //小程序
            $app = new \EasyWeChat\MiniApp\Application(config('wechatmini'));
            $utils = $app->getUtils();
            $ret = $utils->codeToSession($code);
            $miniopenid = $ret['openid'];
            $unionid = $ret['unionid'];
        }


        $user = User::where('unionid', $unionid)->first();
        if (!$user) {
            if ($h5) {
                $url = $avatar;
                // 创建 Guzzle 客户端
                $client = new Client();
                // 发送 GET 请求获取远程图片
                $response = $client->request('GET', $url);
                // 检查响应状态码
                if ($response->getStatusCode() != 200) {
                    return $this->fail('获取头像失败');
                }
                // 获取图片内容
                $imageContent = $response->getBody()->getContents();
                // 使用 Intervention/image 处理图片
                $image = Image::make($imageContent);
                // 获取图片扩展名
                $ext = 'png';
                // 生成唯一的文件名
                $name = bin2hex(pack('Nn', time(), random_int(1, 65535)));
                // 保存图片到本地
                $relative_path = 'upload/avatar/' . date('Ym');
                $real_path = base_path() . "/public/$relative_path";
                if (!is_dir($real_path)) {
                    mkdir($real_path, 0777, true);
                }
                // 裁剪和保存不同尺寸的图片
                $size = min($image->width(), $image->height());
                $image->crop($size, $size);
                $image->resize(300, 300);
                $path = "$real_path/$name.lg.$ext";
                $image->save($path);
                $image->resize(120, 120);
                $path = "$real_path/$name.md.$ext";
                $image->save($path);

                $image->resize(60, 60);
                $path = "$real_path/$name.$ext";
                $image->save($path);

                $image->resize(30, 30);
                $path = "$real_path/$name.sm.$ext";
                $image->save($path);
                $avatar = "/$relative_path/$name.md.$ext";
            }
            $user = User::create([
                'nickname' => !empty($nickname) ? $nickname : '用户' . Util::alnum(),
                'avatar' => !empty($avatar) ? $avatar : '/avatar.png',
                'join_time' => Carbon::now()->toDateTimeString(),
                'join_ip' => $request->getRealIp(),
                'last_time' => Carbon::now()->toDateTimeString(),
                'last_ip' => $request->getRealIp(),
                'unionid' => $unionid,
                'mini_openid' => $miniopenid,
                'h5_openid' => $h5_openid,
            ]);
        } else {
            $user->last_time = Carbon::now()->toDateTimeString();
            $user->last_ip = $request->getRealIp();
            $user->save();
        }
        $token = JwtToken::generateToken([
            'id' => $user->id,
            'client' => JwtToken::TOKEN_CLIENT_MOBILE
        ]);
        return $this->success('登陆成功', ['user' => $user, 'token' => $token]);
    }

    function bindMobile(Request $request)
    {
        $code = $request->post('code');
        //小程序
        $app = new \EasyWeChat\MiniApp\Application(config('wechatmini'));
        $api = $app->getClient();
        $ret = $api->postJson('/wxa/business/getuserphonenumber', [
            'code' => $code
        ]);
        $ret = json_decode($ret);
        if ($ret->errcode != 0) {
            return $this->fail('获取手机号失败');
        }
        $mobile = $ret->phone_info->phoneNumber;
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
            if ($user->money < $amount) {
                return $this->fail('余额不足');
            }
            User::score(-$amount, $user->id, '出金', 'money');
        }
        $data = [
            'user_id' => $user->id,
            'amount' => $amount,
            'image' => $image,
            'type' => $type,
            'memo'=>$type == 0 ? '入金' : '出金',
        ];
        if (!empty($day)) {
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
        })->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->orderByDesc('id')->get();
        return $this->success('成功', $rows);
    }

    function getUserInfo(Request $request)
    {
        $user = User::with(['admin'])->find($request->user_id);
        return $this->success('成功', $user);
    }

    function setAvatar(Request $request)
    {
        $avatar = $request->post('avatar');

    }

    function editUserInfo(Request $request)
    {
        $avatar = $request->post('avatar');
        $nickname = $request->post('nickname');
        $wechat = $request->post('wechat');
        $birthday = $request->post('birthday');
        $city = $request->post('city');

        $data = $request->post();

        $row = User::find($request->user_id);
        foreach ($data as $key => $value) {
            if (!empty($value) || $value == 0) {
                $row->setAttribute($key, $value);
            }
        }
        $row->save();
        return $this->success('修改成功');
    }

    #绑定业务员
    function bindAdmin(Request $request)
    {
        $invitecode = $request->post('invitecode');
        $user = User::find($request->user_id);
        if (!empty($user->admin_id)){
            return $this->fail('已绑定业务员');
        }
        $admin = Admin::where('invitecode', $invitecode)->first();
        if (empty($admin)) {
            return $this->fail('邀请码不正确');
        }
        $user->admin_id = $admin->id;
        $user->save();
        return $this->success('绑定成功');
    }


}
