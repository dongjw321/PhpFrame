<?php
namespace DongPHP\System\Libraries;
/**
 * this is part of xyfree
 *
 * @file SearchNew.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-08-11 14:30
 *
 */
use DongPHP\System\Data;

defined('NEW_SOLR_HOST')      || define('NEW_SOLR_HOST', ENVIRONMENT == 'development' ? 'http://192.168.78.39:8080' : 'http://newsearch.xyzs.com:8386');
defined('NEW_SEARCH_URL')     || define('NEW_SEARCH_URL', ENVIRONMENT == 'development' ? 'http://192.168.78.39:8686/xy.solr' : 'http://192.168.11.13:8686/xy.solr');
defined('SERARCH_REDIS_HOST') || define('SERARCH_REDIS_HOST', ENVIRONMENT == 'development' ? '192.168.78.26' : '192.168.11.75');
defined('SERARCH_REDIS_PORT') || define('SERARCH_REDIS_PORT', ENVIRONMENT == 'development' ? '6379' : '6387');

class Search
{
    const USER     = 'slor';
    const PASSWORD = 'Onjdjsl$!W~!S_';

    private $redis;

    /**
     * 已经安装的bundleids
     * @var array
     */
    private $installed_bundleids = array();

    /**
     * 已经安装的itunesids
     * @var array
     */
    private $installed_itunesids = array();

    /**
     * @var array要过滤的设备类型
     */
    private $equipmenttype       = [];


    /**
     * 搜索结果需要返回的字段
     * @var array
     */
    private $filelds       = '*';

    protected $logs = array();

    protected static $special_keyword = array(
        '旅游',
        '地图',
        '旅行',
        '导航',
        '新闻',
        '头条',
        '阅读',
        '看书',
        '漫画',
        '动漫',
        '视频',
        'TV',
        '电视',
        '影音',
        '直播',
        'FM',
        '动画',
        '影院',
        '体育',
        '音乐',
        '听书',
        '随身听',
        '电话',
        '微博',
        '交友',
        '婚恋',
        '助手',
        '铃声',
        '电影',
    );

    private $filter_chars = array('&','^','(',')','?','{','}',';','*','"','~','+','-','\\','[',']','|','!',':','/','　',',','\'','_','.','#','%','（', ')');

    protected static $_instance = null;

    public function __construct($name='search')
    {
    }

    /**
     * @param string $name
     * @return Search
     */
    public static function getInstance($name='search')
    {
        if (!isset(self::$_instance[$name]) || !(self::$_instance[$name] instanceof self)) {
            self::$_instance[$name] = new self($name);
        }
        return self::$_instance[$name];
    }


    public function setInstalledBundleids($bundleids)
    {
        $this->installed_bundleids = $bundleids;
        return $this;
    }

    public function setInstalledItunesids($itunesids)
    {
        $this->installed_itunesids = $itunesids;
        return $this;
    }

    public function setFilelds($filelds='*')
    {
        $this->filelds = is_array($filelds) ? explode(',',$filelds) : urlencode($filelds);
        return $this;
    }

    public function setEquipmenttype($equipmenttype='iphone')
    {
        if (strtolower($equipmenttype) == 'iphone') {
            $this->equipmenttype = [1, 3];
        }
        return $this;
    }

    public function recordLogs($log)
    {
        $this->logs[] = $log;
    }

    public function getLogs()
    {
        $logs = $this->logs;
        $this->logs = array();
        return $logs;
    }

    public function escape($keyword)
    {
        $pos = strpos($keyword, '-');
        if ($pos !== false) {
            $keyword = substr($keyword, 0, $pos);
        }

        return str_replace($this->filter_chars, '', $keyword);
    }

    public function getAnalysis($keyword)
    {
        static $record = array();

        $keyword = $this->escape($keyword);
        if (!$keyword) {
            return false;
        }


        $tmp_key = md5($keyword);
        if (isset($record[$tmp_key]) && $record[$tmp_key]) {
            return $record[$tmp_key];
        }

        $keyword = $this->getKeywordAlias($keyword);

        $analysis_url = NEW_SOLR_HOST . "/solr/applist/analysis/field?analysis.fieldvalue=".urlencode($keyword)."&analysis.fieldname=title&wt=json";
        $start        = microtime();
        $ret          = self::get($analysis_url);
        $list         = json_decode($ret, true);
        $ar_result    = array();

        if (isset($list['analysis'])) {
            if (isset($list['analysis']['field_names']['title']['index'][1])) {
                foreach ($list['analysis']['field_names']['title']['index'][1] as $val) {
                    $ar_result[] = $val['text'];
                }
            }
        }

        $this->recordLogs('analysis url:'.$analysis_url);
        $this->recordLogs('analysis use:'.(microtime()-$start));


        $record[$tmp_key] = $ar_result;

        return $ar_result;
    }


    /**
     * 调用新的搜索服务
     * 设置accord下载量权重，${wight}默认为0.001
    http://192.168.11.9:8281/xy.solr/init/config?wight=${wight}


    更新accord关键字列表,${rows}为空则默认按照每次取100条来遍历数据库（建议设置为1000或者更多）
    http://192.168.11.9:8281/xy.solr/init/accordwords/${rows}

    搜索：
    http://192.168.11.9:8281/xy.solr/applist/select?q=%E6%94%BF%E6%B2%BB%E6%96%B0%E9%97%BB&fl=title,score&fq=equipmenttype:%281%203%29&fq=apptype:1&start=0&rows=20
     * @param $keyword
     * @param array $where
     * @param int $page
     * @param int $size
     * @param null $ar_order
     * @param null $facet_field
     * @param null $field
     * @return array
     * @throws Exception
     */
    public function doSearchNew($keyword, $where=array(), $page=1, $size=20, $ar_order=null, $facet_field=null, $field=null)
    {
        $keyword          = strtoupper($keyword);
        $original_keyword = $keyword;
        $size             = min($size, 100);
        $start            = max(0, ($page - 1) * $size);
        $analysis_string  = urlencode($keyword);

        if (!$analysis_string) {
            $analysis_string = '*';
        }

        $elevate_result = [];
        if ($analysis_string != '*') {
            $elevate_result = $this->getElevate($keyword);
            if ($elevate_result['itunesids']) {
                $this->installed_itunesids += $elevate_result['itunesids'];
                if ($page == 1) {
                    $size = $size - $elevate_result['count'];
                } else {
                    $start = $start - $elevate_result['count'];
                }
            }
        }

        if ($search_where = $this->setSearchWhere($keyword)) {
            $where = $search_where;
            $analysis_string = '*';
            $ar_order = ['weight'=>'desc', 'downloadnum'=>'desc'];
        }

        $post_string  = 'q=' . $analysis_string;//.'+'.urlencode('音乐').'^3';
        $post_string .= '&fl='.$this->filelds;

        if ($this->installed_itunesids) {
            $post_string .= '&fq=!itunesid:('.implode("+", $this->installed_itunesids).')';
        }

        if ($this->installed_bundleids) {
            $post_string .= '&fq=!bundleid:('.implode("+", $this->installed_bundleids).')';
        }

        foreach ($where as $field => $val) {
            if (is_array($val) && $val['val']) {
                switch ($val['op']) {
                    case 'in' :
                        $post_string .= "&fq=$field:(" . implode("+", $val['val']) . ")";
                        break;
                    case 'not':
                        $post_string .= "&fq=!$field:(" . implode("+", $val['val']) . ")";
                        break;
                    case 'between':
                        $post_string .= "&fq=$field:[{$val['val'][0]}+TO+{$val['val'][1]}]";
                        break;
                }
            } else {
                $post_string .= "&fq=$field:$val";
            }
        }

        if ($ar_order &&  is_array($ar_order)) {
            $ar_sort = array();
            foreach ($ar_order as $k => $v) {
                $ar_sort[] = $k . '+' . $v;
            }
            $post_string .= '&sort=' . join(',', $ar_sort);
        }

        if ($facet_field) {
            $post_string .= '&facet=true&facet.field='.$facet_field;
        }

        $post_string .= '&start=' . $start . '&rows=' . $size;
        $url          = NEW_SEARCH_URL."applist/select?" . $post_string;

        $start_time   = microtime(true);
        $ret          = self::get($url);
        $use_time     = microtime(true)-$start_time;
        $this->recordLogs('search url:' . $url);
        $this->recordLogs('search use:' . $use_time);

        //记录慢日志
        if ($use_time > 0.1) {
            TcpLog::save('excutetime', $use_time . '|' . $post_string, 'search.xyzs.com');
        }

        $ar_result    = array('count' => 0, 'data' => array());
        $list         = json_decode($ret, true);
        if (isset($list['response'])) {
            $ar_result['count'] = $list['response']['numFound'];
            $ar_result['data']  = $list['response']['docs'];
            foreach ($ar_result['data'] as &$row) {
                if (isset($row['smalltitle']) && $row['smalltitle']) {
                    $row['title'] .= '-'.$row['smalltitle'];
                }
            }
        } else {
            TcpLog::save('error', $use_time . '|' . $url. '|'. $ret, 'search.xyzs.com');
            //return $ar_result;
        }

        if ($elevate_result) {
            $ar_result['count'] += $elevate_result['count'];
            if ($page == 1) {
                $ar_result['data'] = array_merge($elevate_result['data'], $ar_result['data']);
            }
        }

        if (isset($list['facet_counts'])) {
            $field     = $list['facet_counts']['facet_fields'][$facet_field];
            $tmp_count = count($field);
            for ($i = 0; $i < $tmp_count; $i++) {
                $ar_result['facet_fields'][$field[$i]] = $field[++$i];
            }
        }

        if ($original_keyword) {//新的udp日志
            TcpLog::save('log', $original_keyword . '|' . $ar_result['count'] . '|' . $this->getClientIp(), 'search.xyzs.com');
        }
        return $ar_result;
    }

    /**
     * 资讯搜索
     * @param $keyword
     * @param array $where
     * @param int $page
     * @param int $size
     * @param null $ar_order
     * @param null $facet_field
     * @param null $field
     * @return array
     * @throws Exception
     */
    public function searchNews($keyword = '', $ar_limit = array('page' => 1, 'size' => 20), $ar_field = array('id','title','description','pid','uDate','keyword','img','catid'), $status = 0,$ar_order = array()){
        //查询条件
        $ar_where = array(
            'keyword' => urlencode($keyword),
            'status' => $status,
        );
        // 排序字段，默认按时间降序
        //$ar_order = array('uDate' => 'desc');

        $where = 'q=*:*';
        $ar_where['keyword'] = isset($ar_where['keyword']) && !empty($ar_where['keyword']) ? trim($ar_where['keyword']) : '';
        if ($ar_where['keyword']) {
            $where = 'q=' . $ar_where['keyword'];
            unset($ar_where['keyword']);
        }

        if(!empty($ar_where)){
            foreach($ar_where as $key => $val){
                $where .= '&fq='.($key.':'.$val);
            }
        }

        if (!empty($ar_order)) {
            $ar_sort = array();
            foreach ($ar_order as $k => $v) {
                $ar_sort[] = $k . '+' . $v;
            }
            $sort = '&sort=' . join(',', $ar_sort);
        } else {
            $sort = '&sort=uDate+desc';
        }


        $ar_limit['size'] = !$ar_limit['size'] || $ar_limit['size'] > 50 ? 50 : $ar_limit['size'];

        $start = ($ar_limit['page'] - 1) * $ar_limit['size'];

        if (!empty($ar_field)) {
            $post_string = "$where$sort&start=$start&rows=" . $ar_limit['size'] . "&fl=" . join(',', $ar_field) . "&wt=json&indent=true";
        } else {
            $post_string = "$where$sort&start=$start&rows=" . $ar_limit['size'] . "&wt=json&indent=true";
        }

        $url          = NEW_SEARCH_URL."/news/select?" . $post_string;

        $start_time   = microtime(true);
        $ret          = self::get($url);

        $use_time     = microtime(true)-$start_time;
        $this->recordLogs('search url:' . $url);
        $this->recordLogs('search use:' . $use_time);

        $list = json_decode($ret, 1);

        $ar_result = array('count' => 0, 'data' => array());

        if (isset($list['response'])) {
            $ar_result['count'] = $list['response']['numFound'];
            $ar_result['data'] = $list['response']['docs'];
        }

        return $ar_result;
    }



    public function searchActCode($keyword = '')
    {
      //查询条件
        $ar_where = array(
            'keyword' => urlencode($keyword),
            'status' => 2,
        );
        // 排序字段，默认按时间降序
        $ar_order = array('statdate,desc' , 'rank,desc');

        // limit 
        $ar_limit = array('page' => 1, 'size' => 20);

        $where = 'q=*:*';
        $ar_where['keyword'] = isset($ar_where['keyword']) && !empty($ar_where['keyword']) ? trim($ar_where['keyword']) : '';
        if ($ar_where['keyword']) {
            $where = 'q=' . $ar_where['keyword'];
            unset($ar_where['keyword']);
        }

        if(!empty($ar_where)){
            foreach($ar_where as $key => $val){
                $where .= '&fq='.($key.':'.$val);
            }
        }

        if (!empty($ar_order)) {
            $ar_sort = array();
            foreach ($ar_order as $k => $v) {
                $ar_sort[] = $k . '+' . $v;
            }
            $sort = '&sort=' . join(',', $ar_sort);
        } else {
            $sort = '&sort=uDate+desc';
        }
        $sort = '&sort=statdate+desc,rank+desc';

            $ar_field = array('id');


        $ar_limit['size'] = !$ar_limit['size'] || $ar_limit['size'] > 50 ? 50 : $ar_limit['size'];

        $start = ($ar_limit['page'] - 1) * $ar_limit['size'];

        if (!empty($ar_field)) {
            $post_string = "$where$sort&start=$start&rows=" . $ar_limit['size'] . "&fl=" . join(',', $ar_field) . "&wt=json&indent=true";
        } else {
            $post_string = "$where$sort&start=$start&rows=" . $ar_limit['size'] . "&wt=json&indent=true";
        }

        $url          = NEW_SEARCH_URL."/activationcode/select?" . $post_string;

        $ret          = self::get($url);
        //$ret = httpRequest(SEARCH_DOMAIN_NEWS . "news/select", $post_string);
        //$ret = self::curlGetContents($_url,5);
        $list = json_decode($ret, 1);

        $ar_result = array('count' => 0, 'data' => array());

        if (isset($list['response'])) {
            $ar_result['count'] = $list['response']['numFound'];
            $ar_result['data'] = $list['response']['docs'];
        }

        return $ar_result;
    
    }


    public function doSearch($keyword, $where=array(), $page=1, $size=20, $ar_order=['weight'=>'desc', 'downloadnum'=>'desc'], $facet_field=null, $field=null)
    {
        $original_keyword = $keyword;
        $size             = min($size, 100);
        $start            = max(0, ($page - 1) * $size);
        $analysis_string  = '*';
        if ($keyword) {
            //$keyword          = $this->getKeywordAlias($keyword);
            $analysis_string  = urlencode($this->escape($keyword));
        }

        $elevate_result = array();
        /*if ($analysis_string != '*') {
            $elevate_result = $this->getElevate($original_keyword, $size);
            if ($elevate_result['itunesids']) {
                $this->installed_itunesids += $elevate_result['itunesids'];
                if ($page == 1) {
                    $size = max(0, $size - $elevate_result['count']);
                } else {
                    $start = $start - $elevate_result['count'];
                }
            }
        }

        if ($search_where = $this->setSearchWhere($original_keyword)) {

            if (isset($where['equipmenttype'])) {
                $old_where['equipmenttype'] = $where['equipmenttype'];
            }
            $where = $search_where;
            $analysis_string = '*';
            $ar_order = ['weight'=>'desc', 'downloadnum'=>'desc'];
        }*/

        $post_string  = 'q=' . $analysis_string;//.'+'.urlencode('音乐').'^3';
        $post_string .= '&fl='.$this->filelds;

        if ($this->installed_itunesids) {
            $post_string .= '&fq=!itunesid:('.implode("+", $this->installed_itunesids).')';
        }

        if ($this->installed_bundleids) {
            $post_string .= '&fq=!bundleid:('.implode("+", $this->installed_bundleids).')';
        }

        if ($this->equipmenttype)
        {
            $post_string .='&fq=equipmenttype:('.implode('+',$this->equipmenttype).')';
        }

        foreach ($where as $field => $val) {
            if (is_array($val) && $val['val']) {
                switch ($val['op']) {
                    case 'in' :
                        $post_string .= "&fq=$field:(" . implode("+", $val['val']) . ")";
                        break;
                    case 'not':
                        $post_string .= "&fq=!$field:(" . implode("+", $val['val']) . ")";
                        break;
                    case 'between':
                        $post_string .= "&fq=$field:[{$val['val'][0]}+TO+{$val['val'][1]}]";
                        break;
                }
            } else {
                $post_string .= "&fq=$field:$val";
            }
        }

        if ($ar_order &&  is_array($ar_order)) {
            $ar_sort = array();
            foreach ($ar_order as $k => $v) {
                $ar_sort[] = $k . '+' . $v;
            }
            $post_string .= '&sort=' . join(',', $ar_sort);
        }

        if ($facet_field) {
            $post_string .= '&facet=true&facet.field='.$facet_field;
        }

        $hash_code     = 0;
        $idfa          = null;
        if (isset($_REQUEST['idfa'])) {
            $hash_code = abs(Hash::hashCode($_REQUEST['idfa'])) % 10;
            $idfa      = trim($_REQUEST['idfa']);
        }

        $qf = '&defType=spdismax&qf=title^1+describe^0.05+yy_keyword^0.1+cus_keyword^0.1&tie=1&';
        $qf .='idfa='.$idfa.'&groupid='.$hash_code.'&';

        $post_string .= '&wt=json&'.($analysis_string=='*'? '':$qf).'start=' . $start . '&rows=' . $size;
        $url          = NEW_SEARCH_URL."/applist/query?" . $post_string;
        $start_time   = microtime(true);
        $ret          = self::get($url);
        $use_time     = microtime(true)-$start_time;
        $this->recordLogs('search url:' . $url);
        $this->recordLogs('search use:' . $use_time);

        //记录慢日志
        if ($use_time > 0.1) {
            TcpLog::save('excutetime', $use_time . '|' . $post_string, 'search.xyzs.com');
        }

        $ar_result    = array('count' => 0, 'data' => array());
        $list         = json_decode($ret, true);
        if (isset($list['response'])) {
            $ar_result['count'] = $list['response']['numFound'];
            $ar_result['data']  = $list['response']['docs'];
            /*foreach ($ar_result['data'] as &$row) {
                if (isset($row['smalltitle']) && $row['smalltitle']) {
                    $row['title'] .= '-'.$row['smalltitle'];
                }
            }*/
        } else {
            TcpLog::save('error', $use_time . '|' . $url. '|'. $ret, 'search.xyzs.com');
            //return $ar_result;
        }

        if ($elevate_result) {
            $ar_result['count'] += $elevate_result['count'];
            if ($page == 1) {
                $ar_result['data'] = array_merge($elevate_result['data'], $ar_result['data']);
            }
        }

        if (isset($list['facet_counts'])) {
            $field     = $list['facet_counts']['facet_fields'][$facet_field];
            $tmp_count = count($field);
            for ($i = 0; $i < $tmp_count; $i++) {
                $ar_result['facet_fields'][$field[$i]] = $field[++$i];
            }
        }

        if ($original_keyword) {//新的udp日志
            TcpLog::save('log', $original_keyword . '|' . $ar_result['count'] . '|' . $this->getClientIp(), 'search.xyzs.com');
        }
        return $ar_result;
    }


    private function getElevate($keyword, $size=10)
    {
        $itunesids      = json_decode($this->getRedis()->get('search_elevate_'.md5(strtolower($keyword))), true);
        $elevate_result = array('count' => 0, 'data' => array(), 'itunesids' => $itunesids);
        if ($itunesids) {
            $start_time   = microtime(true);
            $url          = NEW_SEARCH_URL."/applist/query?q=*&fq=itunesid:(". implode("+", $itunesids) . ")&wt=json&rows=".$size."&fl=".$this->filelds;
            $ret          = self::get($url);
            $use_time     = microtime(true)-$start_time;
            $list         = json_decode($ret, true);
            $this->recordLogs('search url:' . $url);
            $this->recordLogs('search use:' . $use_time);
            if (isset($list['response'])) {
                $elevate_result['count'] = $list['response']['numFound'];

                foreach($itunesids as $itunesid) {//根据itunesid排序
                    foreach ($list['response']['docs'] as &$row) {
                        if ($row['itunesid'] == $itunesid) {
                            if (isset($row['smalltitle']) && $row['smalltitle']) {
                                $row['title'] .= '-' . $row['smalltitle'];
                                unset($row['smalltitle']);
                            }
                            $elevate_result['data'][] = $row;
                            unset($row);
                            break;
                        }
                    }
                }

            } else {
                TcpLog::save('error', $use_time . '|' . $url. '|'. $ret, 'search.xyzs.com');
            }
        }

        return $elevate_result;
    }


    public function getSuggest($keyword, $apptype, $device, $offset=10)
    {
        $keyword = urldecode($keyword);
        $keyword = str_replace('%E2%80%86', '', $keyword);
        $keyword = str_replace(urldecode('%E2%80%86'), '', $keyword);
        $keyword = $this->escape(str_replace(' ', '', $keyword));
        if (mb_strlen($keyword) > 6) {
            $keyword = mb_substr($keyword, 0, 6, 'UTF-8');
        }

        $post_string  = 'q='.urlencode($keyword).'*&wt=json&fl='.$this->filelds;//这里的keyword已经是urlencode过的
        if ($apptype == 1) {
            $post_string .= '&fq=apptype:' . $apptype;
        }
        $post_string .= '&fq=equipmenttype:(' . ($device == 1 ? 1 : 2).'+3)';
        $post_string .= '&sort=weight+desc,downloadnum+desc&df=suggest&start=0&rows=' . $offset;

        $start_time   = microtime(true);
        $url          = NEW_SEARCH_URL."/suggest/select?" . $post_string;
        $ret          = self::get($url);
        $result       = array('count' => 0, 'data' => array());
        $use_time     = microtime(true) - $start_time;
        $list         = json_decode($ret, true);
        if (isset($list['response'])) {
            $result['count'] = $list['response']['numFound'];
            $result['data']  = $list['response']['docs'];
        } else {
            TcpLog::save('error', $use_time . '|' . $url . '|'. $ret, 'search.xyzs.com');
            return $result['data'];
        }

        $this->recordLogs('suggest url:' . $url);
        $this->recordLogs('suggest use:' . $use_time);

        return $result['data'];
    }


    public function getByRecommendClassId($recommend_classid, $page = 1, $offset = 20, $with_count = false, $sort='rank')
    {
        $start_time   = microtime();
        $post_string  = 'q=*:*&wt=json&fl='.$this->filelds;
        $post_string .= '&fq=recommend_classid:' . $recommend_classid;

        if ($this->installed_itunesids) {
            $post_string .= '&fq=!itunesid:('.implode("+", $this->installed_itunesids).')';
        }

        if ($this->installed_bundleids) {
            $post_string .= '&fq=!bundleid:('.implode("+", $this->installed_bundleids).')';
        }

        $post_string .= '&sort='.$sort.'+desc,addtime+desc&start=' . max(0, ($page - 1) * $offset) . '&rows=' . $offset;
        $url    = NEW_SEARCH_URL."/recommend/select?" . $post_string;
        $ret    = self::get($url);
        $list   = json_decode($ret, 1);
        $result = array('count' => 0, 'data' => array());
        if (isset($list['response'])) {
            $result['count'] = $list['response']['numFound'];
            $result['data']  = $list['response']['docs'];
        }
        $this->recordLogs('recommend url:' . $url);
        $this->recordLogs('recommend use:' . (microtime() - $start_time));

        if ($with_count == true) {
            return $result;
        }

        return $result['data'];
    }

    private function getKeywordAlias($keyword)
    {
        $alias = $this->getRedis()->get(md5('keyword:alias:'.$keyword));
        return $alias ? $alias : $keyword;
    }

    public function setSearchWhere($keyword)
    {
        $keyword_info = $this->getRedis()->get(md5('keyword:search_type:'.strtolower($keyword)));

        if(!$keyword_info) {
            return false;
        }

        $keyword_info = json_decode($keyword_info, true);

        $where = false;
        if ($keyword_info['search_type']==1) {
            $where['first_class_id']  = $keyword_info['first_class_id'];
            if ($keyword_info['second_class_id']) {
                $where['second_class_id'] = $keyword_info['second_class_id'];
            }
        }

        return $where;
    }


    public function setSearchCategoryRes($id)
    {
        $categoryRes = $this->getRedis()->get('appTypeRelationClassInfo_' . $id);

        if(! $categoryRes) {
            return false;
        }

        return $categoryRes;
    
    }


    private function getRedis()
    {
        return Data::redis('public.search');
    }

    private static function get($url, $connectTimeout=1, $readTimeout=2)
    {
        $ch      = curl_init();
        $timeout = $connectTimeout + $readTimeout;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, self::USER . ":" . self::PASSWORD);
        curl_setopt($ch, CURLOPT_USERAGENT, 'API PHP5 Client (curl) ' . phpversion());
        for ($i = 0; $i < 3; $i++) {
            $result = curl_exec($ch);
            if ($result) {
                break;
            }
        }
        if (!$result) {
            TcpLog::save('curl_error', curl_error($ch) . '|' . json_encode(curl_getinfo($ch)), 'search.xyzs.com');
        }
        curl_close($ch);
        return $result;
    }


    private static function getClientIp($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }

        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            //客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //浏览当前页面的用户计算机的网关
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }


    public function __destruct()
    {
        if (isset($_REQUEST['log'])) {
            $logs = $this->getLogs();
            if ($logs) {
                echo "<script>(function (c) {if (c && c.groupCollapsed) {";
                foreach ($logs as $log) {
                    echo 'c.log("'.$log.'");'.PHP_EOL;
                }
                echo "}})(console);</script>";
            }
        }
    }
}
