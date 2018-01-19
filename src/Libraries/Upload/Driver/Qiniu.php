<?php
/**
 * this is part of xyfree
 *
 * @file Curl.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2014-05-09 20:43
 *
 */

namespace DongPHP\System\Libraries\Upload\Driver;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu
{

    protected $accessKey = '';
    protected $secretKey = '';
    protected $bucket = '';

    private $errorStr;
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;


    /**
     * 构造函数，用于设置上传根路径
     * @param array $config FTP配置
     */
    public function __construct($config)
    {
        $this->accessKey = $config['accessKey'];
        $this->secretKey = $config['secretKey'];
        $this->bucket    = $config['bucket'];
    }

    /**
     * 检测上传根目录(七牛上传时支持自动创建目录，直接返回)
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath)
    {
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * 检测上传目录(七牛上传时支持自动创建目录，直接返回)
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * 创建文件夹 (七牛上传时支持自动创建目录，直接返回)
     * @param  string $savepath 目录名称
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath)
    {
        return true;
    }

    /**
     * 保存指定文件
     * @param  array $file 保存的文件信息
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file)
    {

        $auth = new Auth($this->accessKey, $this->secretKey);

        $bucket   = $this->bucket;
        $token    = $auth->uploadToken($bucket);
        $filePath = $file['tmp_name'];

        $key = $file['savepath'].$file['savename'];

        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            $this->errorStr = $err->message();
            return false;
        } else {
            $file['path'] = $file['savepath'].$file['savename'];
            return true;
        }
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->errorStr;
    }
}
